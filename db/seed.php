<?php

/**
 * GLOW seeder. Drops and recreates every table, then builds  data by running the real
 * engine (deposits, investments, daily payouts), so every number on screen is consistent.
 *
 * Run: php db/seed.php            full demo data
 *      php db/seed.php --fresh    empty start (admin + settings + one top member)
 */

declare(strict_types=1);

use App\Database;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\LuckyDraw;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Payout;

/** Schema + default settings + plan terms + admin account. No members. */
function install_base(PDO $pdo): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $autoId = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $schema = str_replace('{{AUTO_ID}}', $autoId, (string) file_get_contents(__DIR__ . '/schema.sql'));
    $schema = (string) preg_replace('/^\s*--.*$/m', '', $schema);

    if ($driver === 'mysql') {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    }
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
        $pdo->exec($statement);
    }
    if ($driver === 'mysql') {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    foreach (Setting::DEFAULTS as $name => $value) {
        Database::insert('settings', ['name' => $name, 'value' => $value]);
    }
    foreach ([25, 50, 100] as $days) {
        Database::insert('plan_terms', ['days' => $days, 'principal_rate' => 4, 'profit_rate' => 1, 'is_active' => 1]);
    }
    Database::insert('admins', [
        'username' => 'admin',
        'name' => 'Demo Admin',
        'password_hash' => password_hash('admin1234', PASSWORD_DEFAULT),
        'created_at' => now(),
    ]);
}

/** Full demo: referral tree over 10 levels, real investments and 4 days of payouts. */
function seed_database(PDO $pdo): void
{
    install_base($pdo);
    $hash = password_hash('demo1234', PASSWORD_DEFAULT);
    $txnHash = password_hash('txn1234', PASSWORD_DEFAULT); // demo transaction password for every seeded member
    $countries = ['India', 'Nigeria', 'Indonesia', 'Brazil', 'Vietnam', 'Philippines', 'Kenya', 'Mexico'];
    $day = static fn (int $offset, string $time): string => date('Y-m-d', strtotime("$offset days")) . ' ' . $time;
    $tx = static fn (): string => '0x' . bin2hex(random_bytes(32));
    $n = 0;

    $member = static function (string $code, string $name, ?int $sponsorId, string $at) use ($hash, $txnHash, $countries, &$n): int {
        freeze_clock($at);
        $i = $n++;
        User::create([
            'user_code' => $code,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'mobile' => sprintf('+1000000%04d', $i),
            'country' => $countries[$i % count($countries)],
            'wallet' => sprintf('0x%040x', $i), // unique placeholder wallets: 0x000…0, 0x000…1, …
            'password_hash' => $hash,
            'txn_password_hash' => $txnHash,
        ], $sponsorId);
        return (int) User::findByCode($code)['id'];
    };
    $fund = static function (int $userId, float $amount, string $at) use ($tx): void {
        freeze_clock($at);
        Deposit::approve(Deposit::submit($userId, $amount, $tx()));
    };
    $invest = static function (int $userId, string $plan, int $days, float $amount, string $at): void {
        freeze_clock($at);
        Investment::open($userId, $plan, $days, $amount);
    };

    Database::transaction(static function () use ($member, $fund, $invest, $day, $tx): void {
        // Tree: Demo User -> 6 directs -> 12 level-2 -> a 2-wide chain down to level 10.
        $root = $member('GL0000001', 'Demo User', null, $day(-6, '09:00:00'));
        $level1 = [];
        for ($i = 1; $i <= 6; $i++) {
            $level1[] = $member(sprintf('GL%07d', 100 + $i), sprintf('Demo Member %02d', $i), $root, $day(-6, '10:00:00'));
        }
        $level2 = [];
        foreach ($level1 as $i => $sponsor) {
            for ($j = 1; $j <= 2; $j++) {
                $k = $i * 2 + $j;
                $level2[] = $member(sprintf('GL%07d', 200 + $k), sprintf('Demo Partner %02d', $k), $sponsor, $day(-6, '12:00:00'));
            }
        }
        $deep = [];
        $sponsor = $level2[0];
        for ($level = 3, $k = 1; $level <= 10; $level++) {
            $first = $member(sprintf('GL%07d', 300 + $k), sprintf('Demo Leader %02d', $k++), $sponsor, $day(-6, '14:00:00'));
            $deep[] = $member(sprintf('GL%07d', 300 + $k), sprintf('Demo Leader %02d', $k++), $sponsor, $day(-6, '14:00:00'));
            $deep[] = $first;
            $sponsor = $first;
        }

        // Maturity tester: own account (no sponsor, so its old dates don't touch the tree), plans bought
        // on day -25 reach day 24 of 25 below; today's payout matures both.
        $tester = $member('GL0000002', 'Maturity Tester', null, $day(-26, '09:00:00'));
        $fund($tester, 200, $day(-26, '09:30:00'));
        $invest($tester, 'compound', 25, 100, $day(-25, '10:00:00'));
        $invest($tester, 'fixed', 25, 100, $day(-25, '10:05:00'));

        // Deposits (approved by "admin") then investments, all on day -5.
        $fund($root, 500, $day(-5, '09:00:00'));
        $invest($root, 'compound', 25, 100, $day(-5, '10:00:00'));
        $invest($root, 'fixed', 50, 50, $day(-5, '10:05:00'));
        $plans = [['compound', 25, 100], ['fixed', 25, 50], ['compound', 50, 200], ['fixed', 100, 75], ['compound', 100, 50]];
        foreach (array_slice($level1, 0, 5) as $i => $userId) {          // 5 of 6 directs active
            [$plan, $days, $amount] = $plans[$i];
            $fund($userId, $amount + 50, $day(-5, '11:00:00'));
            $invest($userId, $plan, $days, $amount, $day(-5, '11:30:00'));
        }
        foreach (array_slice($level2, 0, 8) as $i => $userId) {          // 8 of 12 level-2 active
            $fund($userId, 100, $day(-5, '12:00:00'));
            $invest($userId, $i % 2 ? 'fixed' : 'compound', 25, [20, 50, 100][$i % 3], $day(-5, '12:30:00'));
        }
        foreach ($deep as $i => $userId) {                               // levels 3-10 all active
            $fund($userId, 60, $day(-5, '13:00:00'));
            $invest($userId, 'compound', 25, [25, 40, 60][$i % 3], $day(-5, '13:30:00'));
        }
        // 100 extra investors (GL0000401..500) under the 8 active level-2 members, so a 10 + 90 lucky draw
        // can fill. They sit at level 3+ of the root, so the root's salary counts stay unchanged.
        // #91..#100 go one level deeper, under directs of GL0000203 (#2, #10 .. #74): that gives
        // GL0000203 12 active directs + 10 active level-2, so it qualifies for salary.
        $extraPlans = [['compound', 25], ['fixed', 25], ['compound', 50], ['fixed', 100]];
        $extras = [];
        for ($i = 1; $i <= 100; $i++) {
            $sponsor = $i > 90 ? $extras[($i - 90) * 8 - 6] : $level2[$i % 8];
            $userId = $extras[$i] = $member(sprintf('GL%07d', 400 + $i), sprintf('Demo Investor %03d', $i), $sponsor, $day(-6, '15:00:00'));
            $fund($userId, 100, $day(-5, '14:00:00'));
            [$plan, $days] = $extraPlans[$i % 4];
            $invest($userId, $plan, $days, 10 * ($i % 10 + 1), $day(-5, '14:30:00'));
        }
        // GL0000209 + GL0000210: funded, not invested. Investing both completes the root's salary (8 -> 10 level-2).
        $fund($level2[8], 100, $day(-1, '15:00:00'));
        $fund($level2[9], 100, $day(-1, '15:00:00'));
        freeze_clock(null);
        User::setBlocked($level2[11], true);                             // GL0000212: login refused

        // Payouts for days -24 .. -1 (the tree's plans start on day -5, so they get 4 of them).
        // Today's payout is left for the admin / cron.
        for ($offset = -24; $offset <= -1; $offset++) {
            Payout::run(date('Y-m-d', strtotime("$offset days")));
        }

        // A finished lucky draw for the winners page.
        freeze_clock($day(-2, '18:00:00'));
        $draw = LuckyDraw::create('Weekly Lucky Draw #1', 10, 5);
        LuckyDraw::addWinner($draw, $root);
        LuckyDraw::fillRandom($draw);
        LuckyDraw::distribute($draw);

        // Open items for the admin panel.
        freeze_clock($day(0, '08:00:00'));
        Deposit::submit($root, 50, $tx());
        Deposit::reject(Deposit::submit($level1[5], 30, $tx()), 'Hash not found on chain (demo).');
        Deposit::submit($level1[5], 40, $tx());
        Withdrawal::request($level1[0], 'income', 5);
        $planId = (int) Database::value("SELECT id FROM investments WHERE user_id = ? AND plan = 'compound'", [$level1[0]]);
        Investment::release($level1[0], $planId, 8);                       // pending -> Compound ROI wallet
        Withdrawal::request($root, 'roi', 2);
        Withdrawal::markPaid(Withdrawal::request($level1[2], 'roi', 2), $tx(), 'Sent manually (demo).');
        LuckyDraw::create('Weekly Lucky Draw #2', 10, 100);                 // draft: pick 10, fill 90, distribute
        Ticket::create($root, 'Deposit not showing', 'I sent $50 this morning, still pending.');
        Ticket::reply(
            Ticket::create($root, 'How does compounding work?', 'Can I compound older days?'),
            'Only the latest day\'s part can be compounded; older parts can be withdrawn.'
        );
        freeze_clock(null);
    });

    seed_trade_pairs();
}

/** Sample "AI trading" rows (display only, random every seed). */
function seed_trade_pairs(): void
{
    $bases = [
        'BTC' => 64000, 'ETH' => 2400, 'BNB' => 600, 'SOL' => 140, 'XRP' => 0.52, 'ADA' => 0.38,
        'DOGE' => 0.12, 'TRX' => 0.13, 'DOT' => 6.2, 'LINK' => 14.5, 'MATIC' => 0.7, 'LTC' => 72,
        'AVAX' => 28, 'UNI' => 7.4, 'ATOM' => 8.1, 'XLM' => 0.11, 'FIL' => 4.6, 'ICP' => 9.3,
        'VET' => 0.025, 'ETC' => 22, 'NEAR' => 5.1, 'APT' => 8.7, 'ARB' => 0.9, 'OP' => 1.8,
        'SHIB' => 0.000018, 'INJ' => 24, 'SUI' => 1.1, 'AAVE' => 98, 'ALGO' => 0.17, 'SAND' => 0.42,
    ];
    foreach ($bases as $coin => $base) {
        $side = random_int(1, 100) <= 70 ? 'Long' : 'Short';
        $leverage = [5, 10, 15, 20][random_int(0, 3)];
        $entry = $base * random_int(80, 120) / 100;
        $latest = $entry * (1 + random_int(-150, 450) / 1000);
        Database::insert('trade_pairs', [
            'pair' => $coin . '/USDT',
            'side' => $side,
            'leverage' => $leverage,
            'entry_price' => round($entry, 6),
            'latest_price' => round($latest, 6),
            'pnl' => round(($latest - $entry) / $entry * $leverage * 100 * ($side === 'Long' ? 1 : -1), 2),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-' . random_int(1, 90) . ' minutes')),
        ]);
    }
}

/**
 * Clean start for manual testing: no activity at all, just the admin, default settings,
 * plan terms and one empty top member (GL0000001) so new sign-ups have a sponsor.
 */
function seed_fresh(PDO $pdo): void
{
    install_base($pdo);
    User::create([
        'user_code' => 'GL0000001',
        'name' => 'Top Member',
        'email' => 'top@example.com',
        'mobile' => '+10000000000',
        'country' => 'India',
        'wallet' => '0x0000000000000000000000000000000000000000',
        'password' => 'demo1234',
        'txn_password' => 'txn1234',
    ], null);
    seed_trade_pairs();
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['argv'][0] ?? '') === __FILE__) {
    require __DIR__ . '/../app/helpers.php';
    if (in_array('--fresh', $_SERVER['argv'], true)) {
        seed_fresh(Database::pdo());
        echo "Fresh start: all activity wiped, settings reset to defaults.
"
            . "Top member (sponsor for sign-ups): GL0000001 / demo1234 (transaction password txn1234), empty wallets
"
            . "Admin login: admin / admin1234
";
        exit;
    }
    seed_database(Database::pdo());
    $users = Database::value('SELECT COUNT(*) FROM users');
    $investments = Database::value('SELECT COUNT(*) FROM investments');
    echo <<<TXT
Seeded: $users users, $investments investments, payouts up to yesterday (today's is due).
Admin: admin / admin1234    Every member: demo1234

Test accounts:
  GL0000001  Top of tree. Salary 5/5 directs, 8/10 level-2. Pending: \$50 deposit, \$2 ROI withdrawal. 2 tickets.
  GL0000002  Maturity Tester: compound + FD at day 24/25. Admin > Run Due Payouts matures both,
             then withdrawal limit is \$0 until it invests again.
  GL0000209  \$100 in Deposit wallet, not invested. Invest with both 209 and 210 and
  GL0000210  GL0000001 qualifies for salary (set salary pay day to tomorrow, then +1 to pay).
  GL0000101  Compound plan, 4 days pending. Pending withdrawals: \$5 income, \$8 plan (reject = refund).
  GL0000106  First deposit pending, one rejected.
  GL0000211  Registered, never deposited: full flow from zero.
  GL0000212  Blocked: login refused, unblock in Admin > Users.
Admin: Weekly Lucky Draw #2 draft (100 slots, 131 eligible), 1 open ticket.
  GL0000203  Salary achiever (12 active directs, 10 active level-2): trophy in the Level Team tree.
  GL0000401..GL0000500  Extra active investors (level 3-4 of GL0000001) for the lucky draw pool.

TXT;
}
