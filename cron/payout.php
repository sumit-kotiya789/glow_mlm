<?php

/**
 * Payout runner.
 *
 *   php cron/payout.php             pay every due period up to now (day mode waits for the payout time)
 *   php cron/payout.php --next      testing: pay the next period (day, or hour in hour mode) right now
 *   php cron/payout.php --next=25   testing: pay the next 25 periods right now
 *
 * Windows Task Scheduler: run every 15 minutes -> C:\xamppppppp\php\php.exe C:\...\GLOW\cron\payout.php
 * Each period is paid at most once, so running it often is safe.
 */

declare(strict_types=1);

use App\Services\Payout;

require __DIR__ . '/../app/helpers.php';

$simulate = null;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--next(?:-day)?(?:=(\d+))?$/', $arg, $m)) {
        $simulate = max(1, (int) ($m[1] ?? 1));
    }
}
$results = $simulate !== null ? Payout::simulate($simulate) : Payout::runDue();

if (!$results) {
    echo 'Nothing due (' . Payout::unit() . ' mode). Last paid: ' . (Payout::lastRunDate() ?? 'never') . "\n";
}
foreach ($results as $r) {
    echo "Paid {$r['date']}: {$r['investments']} plans, {$r['salaries']} salaries\n";
}
