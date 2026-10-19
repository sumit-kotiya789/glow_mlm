<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use DomainException;

/**
 * Internal fund moves. Rules: docs/RULES.md section 9a.
 *  - P2P: Deposit wallet -> another member's Deposit wallet, by user ID.
 *    Only members with at least one approved deposit may send (blocks signup-bonus farming).
 *  - Wallet move: own ROI / Income wallet -> own Deposit wallet.
 * No fee. Every move is two ledger rows written in one transaction.
 */
final class Transfer
{
    public const SOURCES = ['roi' => 'ROI Wallet', 'income' => 'Income Wallet'];

    /** Ledger types written by this class (the Transfer History page). */
    public const TYPES = ['p2p_out', 'p2p_in', 'wallet_out', 'wallet_in'];

    /** True once the member has at least one admin-approved deposit. */
    public static function canSend(int $userId): bool
    {
        return Database::value("SELECT 1 FROM deposits WHERE user_id = ? AND status = 'Approved' LIMIT 1", [$userId]) !== null;
    }

    /**
     * Signup bonus still sitting in the Deposit wallet. It can be invested but never sent.
     * Investments spend the bonus first, so: bonus received - amount invested from the Deposit wallet.
     */
    public static function lockedBonus(int $userId): float
    {
        $bonus = (float) Database::value(
            "SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE user_id = ? AND wallet = 'deposit' AND type = 'signup_bonus'",
            [$userId]
        );
        if ($bonus <= 0) {
            return 0.0;
        }
        $invested = -(float) Database::value(
            "SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE user_id = ? AND wallet = 'deposit' AND type = 'invest'",
            [$userId]
        );
        return round(max(0.0, $bonus - max(0.0, $invested)), 2);
    }

    /** What the member may send right now: Deposit wallet minus the unspent signup bonus. */
    public static function sendable(int $userId): float
    {
        $balance = (float) Database::value('SELECT deposit_balance FROM users WHERE id = ?', [$userId]);
        return round(max(0.0, $balance - self::lockedBonus($userId)), 2);
    }

    /**
     * Validate a receiver ID. Exceptions carry code 1 so the controller can show them on the ID field.
     *
     * @return array<string, mixed> the receiver
     * @throws DomainException
     */
    public static function receiver(int $senderId, string $code): array
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z0-9]{3,12}$/', $code)) {
            throw new DomainException('Enter a valid User ID, like GL0000001.', 1);
        }
        $to = User::findByCode($code);
        if ($to === null) {
            throw new DomainException('User ID not found.', 1);
        }
        if ((int) $to['id'] === $senderId) {
            throw new DomainException('You cannot send to your own ID.', 1);
        }
        if ((int) $to['is_blocked'] === 1) {
            throw new DomainException('This user cannot receive transfers.', 1);
        }
        return $to;
    }

    /** "Rahul Kumar" -> "Ra*** Ku***" (enough to recognise the person, not enough to leak the name). */
    public static function maskName(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name)) ?: [];
        return implode(' ', array_map(
            static fn (string $w): string => mb_substr($w, 0, 2) . '***',
            array_filter($words, static fn (string $w): bool => $w !== '')
        ));
    }

    /**
     * Send from the sender's Deposit wallet to the receiver's Deposit wallet.
     *
     * @return array<string, mixed> the receiver
     * @throws DomainException with a user-facing message
     */
    public static function p2p(int $senderId, string $code, float $amount): array
    {
        $amount = round($amount, 2);
        $min = Setting::num('min_transfer');
        if ($amount < $min) {
            throw new DomainException('Minimum transfer is ' . money($min) . '.');
        }
        $to = self::receiver($senderId, $code);
        if (!self::canSend($senderId)) {
            throw new DomainException('P2P transfer unlocks after your first approved deposit.');
        }
        $from = User::find($senderId);

        Database::transaction(static function () use ($senderId, $to, $from, $amount): void {
            $sendable = self::sendable($senderId);
            if ($amount > $sendable + 0.001) {
                throw new DomainException('You can send up to ' . money($sendable) . '. The signup bonus can be invested but not sent.');
            }
            Ledger::post($senderId, 'deposit', 'p2p_out', -$amount, ['note' => 'Sent to ' . $to['user_code']]);
            Ledger::post((int) $to['id'], 'deposit', 'p2p_in', $amount, [
                'from_user_id' => $senderId,
                'note' => 'Received from ' . $from['user_code'],
            ]);
        });
        return $to;
    }

    /**
     * Move money from the member's own ROI or Income wallet into their own Deposit wallet.
     *
     * @throws DomainException with a user-facing message
     */
    public static function toDeposit(int $userId, string $source, float $amount): void
    {
        if (!isset(self::SOURCES[$source])) {
            throw new DomainException('Choose a wallet.');
        }
        $amount = round($amount, 2);
        $min = Setting::num('min_transfer');
        if ($amount < $min) {
            throw new DomainException('Minimum transfer is ' . money($min) . '.');
        }
        Database::transaction(static function () use ($userId, $source, $amount): void {
            Ledger::post($userId, $source, 'wallet_out', -$amount, ['note' => 'Moved to Deposit wallet']);
            Ledger::post($userId, 'deposit', 'wallet_in', $amount, ['note' => 'Moved from ' . self::SOURCES[$source]]);
        });
    }

    /** @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int} */
    public static function history(int $userId, int $page, ?int $perPage = null): array
    {
        return Database::paginate(
            'SELECT l.*, f.user_code AS from_code FROM ledger l LEFT JOIN users f ON f.id = l.from_user_id
             WHERE l.user_id = ? AND l.type IN (' . Database::placeholders(self::TYPES) . ')
             ORDER BY l.id DESC',
            array_merge([$userId], self::TYPES),
            $page,
            $perPage
        );
    }
}
