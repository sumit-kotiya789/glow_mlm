<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use App\Services\Commission;
use App\Services\Payout;
use App\Services\Salary;
use DomainException;

/**
 * Compound and Fixed Deposit plans. Rules: docs/RULES.md sections 3-5.
 */
final class Investment
{
    public const PLANS = ['compound' => 'Compound', 'fixed' => 'Fixed Deposit'];

    /**
     * Invest from the deposit wallet. Pays direct + level income and re-checks salary.
     *
     * @throws DomainException with a user-facing message
     */
    public static function open(int $userId, string $plan, int $days, float $amount): int
    {
        if (!isset(self::PLANS[$plan])) {
            throw new DomainException('Choose Compound or Fixed Deposit.');
        }
        $term = PlanTerm::findActive($days);
        if ($term === null) {
            throw new DomainException('Choose a valid term.');
        }
        $min = User::minInvest($userId);
        $max = Setting::num('max_invest');
        if ($amount < $min || $amount > $max) {
            throw new DomainException('Amount must be ' . money($min) . ' to ' . money($max) . '.');
        }

        return Database::transaction(static function () use ($userId, $plan, $term, $amount): int {
            $id = Database::insert('investments', [
                'user_id' => $userId,
                'plan' => $plan,
                'term_days' => (int) $term['days'],
                'principal_rate' => $term['principal_rate'],
                'profit_rate' => $term['profit_rate'],
                'amount' => round($amount, 2),
                'principal' => round($amount, 2),
                'status' => 'Active',
                'started_at' => now(),
            ]);
            $label = self::PLANS[$plan] . ' ' . $term['days'] . 'd #' . $id;
            Ledger::post($userId, 'deposit', 'invest', -$amount, ['ref_id' => $id, 'note' => $label]);
            Database::run('UPDATE users SET activated_at = ? WHERE id = ? AND activated_at IS NULL', [now(), $userId]);

            Commission::onInvest($userId, $amount, $id);
            // New investment can complete salary conditions for the user, their sponsor and level-2 sponsor.
            Salary::check($userId);
            foreach (User::uplines($userId, 2) as $upline) {
                Salary::check((int) $upline['id']);
            }
            return $id;
        });
    }

    /**
     * Compound the latest day's principal part into principal.
     *
     * @throws DomainException
     */
    public static function compound(int $userId, int $id): float
    {
        $inv = self::findForUser($userId, $id);
        if ($inv === null || $inv['plan'] !== 'compound' || $inv['status'] !== 'Active') {
            throw new DomainException('Plan not found or not active.');
        }
        $part = (float) $inv['latest_part'];
        if ($part <= 0) {
            throw new DomainException('Nothing to compound. Only the latest day\'s part can be compounded, once.');
        }
        Database::transaction(static function () use ($inv, $part, $userId): void {
            $changed = Database::run(
                'UPDATE investments SET principal = ?, pending = ?, latest_part = 0 WHERE id = ? AND latest_part = ?',
                [round($inv['principal'] + $part, 2), round($inv['pending'] - $part, 2), $inv['id'], $inv['latest_part']]
            )->rowCount();
            if ($changed !== 1) {
                throw new DomainException('Plan changed, please try again.');
            }
            Ledger::post($userId, 'plan', 'compound', $part, ['ref_id' => (int) $inv['id'], 'note' => self::label($inv)]);
        });
        return $part;
    }

    /**
     * Move pending principal parts into the Compound ROI wallet (withdraw from there). Principal drops $1 for $1.
     *
     * @throws DomainException
     */
    public static function release(int $userId, int $id, float $amount): void
    {
        $amount = round($amount, 2);
        $inv = self::findForUser($userId, $id);
        if ($inv === null || $inv['plan'] !== 'compound' || $inv['status'] !== 'Active') {
            throw new DomainException('Plan not found or not active.');
        }
        if ($amount <= 0 || $amount > (float) $inv['pending'] + 0.001) {
            throw new DomainException('Amount must be up to the pending ' . money($inv['pending']) . '.');
        }
        Database::transaction(static function () use ($inv, $amount, $userId): void {
            $pending = round($inv['pending'] - $amount, 2);
            $changed = Database::run(
                'UPDATE investments SET pending = ?, principal = ?, latest_part = ? WHERE id = ? AND pending = ?',
                [
                    $pending,
                    max(0, round($inv['principal'] - $amount, 2)),
                    min((float) $inv['latest_part'], $pending), // older pending is used up first
                    $inv['id'],
                    $inv['pending'],
                ]
            )->rowCount();
            if ($changed !== 1) {
                throw new DomainException('Plan changed, please try again.');
            }
            $meta = ['ref_id' => (int) $inv['id'], 'note' => self::label($inv)];
            Ledger::post($userId, 'plan', 'release', -$amount, $meta);
            Ledger::post($userId, 'roi', 'release', $amount, $meta);
        });
    }

    /** Pay one day. Called by Payout::run() inside its transaction with the clock frozen. */
    public static function payDay(array $inv, string $date): void
    {
        $id = (int) $inv['id'];
        $userId = (int) $inv['user_id'];
        $principal = (float) $inv['principal'];
        $day = (int) $inv['days_paid'] + 1;
        $final = $day >= (int) $inv['term_days'];
        $note = self::label($inv) . ' · day ' . $day . '/' . $inv['term_days'];

        if ($inv['plan'] === 'compound') {
            $part = round($principal * (float) $inv['principal_rate'] / 100, 2);
            $profit = round($principal * (float) $inv['profit_rate'] / 100, 2);
            Ledger::post($userId, 'roi', 'profit', $profit, ['ref_id' => $id, 'note' => $note]);
            Ledger::post($userId, 'plan', 'plan_part', $part, ['ref_id' => $id, 'note' => $note]);
            Commission::onDailyPart($userId, $part, $id);
            $pending = round((float) $inv['pending'] + $part, 2);
            if ($final) {
                // The invested amount is not returned: only pending parts + anything compounded on top of it.
                $release = $pending + max(0.0, $principal - (float) $inv['amount']);
                Ledger::post($userId, 'roi', 'maturity', $release, ['ref_id' => $id, 'note' => $note]);
                self::update($id, ['pending' => 0, 'latest_part' => 0], $day, $date, true);
            } else {
                self::update($id, ['pending' => $pending, 'latest_part' => $part], $day, $date, false);
            }
            return;
        }

        $dayRoi = round($principal * PlanTerm::dailyRate($inv) / 100, 2);
        $accrued = round((float) $inv['accrued'] + $dayRoi, 2);
        Ledger::post($userId, 'plan', 'locked_roi', $dayRoi, ['ref_id' => $id, 'note' => $note]);
        if ($final) {
            Ledger::post($userId, 'fd', 'maturity', $principal + $accrued, ['ref_id' => $id, 'note' => $note]);
        }
        self::update($id, ['accrued' => $accrued], $day, $date, $final);
    }

    /**
     * Plan timeline from the ledger, oldest first, with principal and pending (or locked ROI) after
     * each event. One payout day's rows (profit, part, maturity) share a note and merge into one row.
     *
     * @return list<array{date: string, event: string, detail: string, roi: float, principal: float, held: float}>
     */
    public static function history(array $inv): array
    {
        $id = (int) $inv['id'];
        $rows = Database::all(
            "SELECT * FROM ledger WHERE user_id = ? AND (
                (ref_id = ? AND type IN ('invest', 'profit', 'plan_part', 'locked_roi', 'maturity', 'compound', 'withdrawal'))
                OR (ref_id = ? AND type = 'release' AND wallet = 'plan')
                OR (type = 'refund' AND ref_id IN (SELECT id FROM withdrawals WHERE investment_id = ?))
             ) ORDER BY id", // insert order = the order the engine applied them (payout stamps can be backdated)
            [$inv['user_id'], $id, $id, $id]
        );
        // Plan withdrawal ledger rows and withdrawal requests are written 1:1, in the same order.
        $requests = Database::all('SELECT id, status FROM withdrawals WHERE investment_id = ? ORDER BY id', [$id]);

        $principal = 0.0;
        $held = 0.0;
        $out = [];
        foreach ($rows as $r) {
            $a = (float) $r['amount'];
            $payout = in_array($r['type'], ['profit', 'plan_part', 'locked_roi', 'maturity'], true);
            $key = $payout ? 'day:' . $r['note'] : 'id:' . $r['id'];
            $out[$key] ??= ['date' => $r['created_at'], 'event' => '', 'detail' => [], 'roi' => 0.0];
            $row = &$out[$key];
            switch ($r['type']) {
                case 'invest':
                    $principal = -$a;
                    $row['event'] = 'Invested';
                    $row['detail'][] = money(-$a) . ' from Deposit wallet';
                    break;
                case 'profit':
                    $row['roi'] += $a;
                    break;
                case 'plan_part':
                    $held += $a;
                    $row['detail'][] = '+' . money($a) . ' principal part to pending';
                    break;
                case 'locked_roi':
                    $held += $a;
                    $row['detail'][] = '+' . money($a) . ' locked ROI';
                    break;
                case 'maturity':
                    $row['roi'] += $a;
                    $principal = $held = 0.0;
                    $row['detail'][] = $inv['plan'] === 'compound'
                        ? 'matured, invested principal closed, ' . money($a) . ' released to Compound ROI wallet'
                        : 'matured, ' . money($a) . ' released to FD ROI wallet';
                    break;
                case 'compound':
                    $principal += $a;
                    $held -= $a;
                    $row['event'] = 'Compounded';
                    $row['detail'][] = money($a) . ' moved from pending into principal';
                    break;
                case 'release':
                    $principal = max(0, $principal + $a);
                    $held += $a;
                    $row['roi'] -= $a;
                    $row['event'] = 'Moved to Compound ROI wallet';
                    $row['detail'][] = money(-$a) . ' taken from pending, principal down by the same';
                    break;
                case 'withdrawal': // requests made before pending moved to the wallet
                    $principal = max(0, $principal + $a);
                    $held += $a;
                    $w = array_shift($requests);
                    $row['event'] = 'Withdrawal' . ($w ? ' #' . $w['id'] . ' · ' . $w['status'] : '');
                    $row['detail'][] = money(-$a) . ' taken from pending, principal down by the same';
                    break;
                case 'refund':
                    $row['event'] = 'Withdrawal refund';
                    if ($r['wallet'] === 'plan') {
                        $principal += $a;
                        $held += $a;
                        $row['detail'][] = $r['note'] . ', ' . money($a) . ' back into plan';
                    } else {
                        $row['roi'] += $a;
                        $row['detail'][] = $r['note'] . ', plan had matured: ' . money($a) . ' to Compound ROI wallet';
                    }
                    break;
            }
            if ($payout) {
                $row['event'] = 'Payout ' . trim(substr($r['note'], (int) strrpos($r['note'], '·') + 2));
            }
            $row['principal'] = $principal;
            $row['held'] = $held;
        }
        unset($row);

        return array_values(array_map(static fn ($row) => ['detail' => implode(', ', $row['detail'])] + $row, $out));
    }

    /** @return array<string, mixed>|null */
    public static function findForUser(int $userId, int $id): ?array
    {
        return Database::one('SELECT * FROM investments WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    /** @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int} */
    public static function paginateForUser(int $userId, int $page, ?string $plan = null): array
    {
        $sql = 'SELECT * FROM investments WHERE user_id = ?';
        $params = [$userId];
        if ($plan !== null) {
            $sql .= ' AND plan = ?';
            $params[] = $plan;
        }
        return Database::paginate($sql . " ORDER BY CASE WHEN status = 'Active' THEN 0 ELSE 1 END, started_at DESC", $params, $page);
    }

    /**
     * Admin list of all plans.
     *
     * @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int}
     */
    public static function paginateAll(?string $status, ?string $plan, int $page): array
    {
        $where = [];
        $params = [];
        if ($status !== null) {
            $where[] = 'i.status = ?';
            $params[] = $status;
        }
        if ($plan !== null) {
            $where[] = 'i.plan = ?';
            $params[] = $plan;
        }
        return Database::paginate(
            'SELECT i.*, u.user_code, u.name FROM investments i JOIN users u ON u.id = i.user_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY i.started_at DESC, i.id DESC',
            $params,
            $page
        );
    }

    /** @return array{active: int, invested: float, compound: float, fixed: float, pending: float, compoundable: float, locked: float} */
    public static function summary(int $userId): array
    {
        $row = Database::one(
            "SELECT COUNT(*) AS active, COALESCE(SUM(amount), 0) AS invested,
                    COALESCE(SUM(CASE WHEN plan = 'compound' THEN amount ELSE 0 END), 0) AS compound,
                    COALESCE(SUM(pending), 0) AS pending,
                    COALESCE(SUM(latest_part), 0) AS compoundable, COALESCE(SUM(accrued), 0) AS locked
             FROM investments WHERE user_id = ? AND status = 'Active'",
            [$userId]
        );
        return [
            'active' => (int) $row['active'],
            'invested' => (float) $row['invested'],
            'compound' => (float) $row['compound'],
            'fixed' => (float) $row['invested'] - (float) $row['compound'],
            'pending' => (float) $row['pending'],
            'compoundable' => (float) $row['compoundable'],
            'locked' => (float) $row['locked'],
        ];
    }

    public static function label(array $inv): string
    {
        return self::PLANS[$inv['plan']] . ' ' . $inv['term_days'] . 'd #' . $inv['id'];
    }

    /** Formatted maturity moment: start period + term periods (days, or hours in testing mode). */
    public static function maturesOn(array $inv): string
    {
        return self::periodAfter($inv['started_at'], (int) $inv['term_days']);
    }

    /** Formatted moment of the first payout (the period after the start). */
    public static function firstPayout(array $inv): string
    {
        return self::periodAfter($inv['started_at'], 1);
    }

    /** "Day 3 of 25" / "Hour 3 of 25". */
    public static function progressLabel(array $inv): string
    {
        return ucfirst(Payout::unit()) . ' ' . $inv['days_paid'] . ' of ' . $inv['term_days'];
    }

    private static function periodAfter(string $start, int $periods): string
    {
        $ts = (int) strtotime($start);
        return Payout::hourly()
            ? date('d-M-Y H:00', (int) strtotime(date('Y-m-d H:00:00', $ts) . " +$periods hours"))
            : date('d-M-Y', (int) strtotime(date('Y-m-d', $ts) . " +$periods days"));
    }

    private static function update(int $id, array $values, int $day, string $date, bool $final): void
    {
        $values += ['days_paid' => $day, 'last_paid_on' => $date];
        if ($final) {
            $values += ['status' => 'Matured', 'matured_at' => now()];
        }
        $set = implode(', ', array_map(static fn ($col) => "$col = ?", array_keys($values)));
        Database::run("UPDATE investments SET $set WHERE id = ?", [...array_values($values), $id]);
    }
}
