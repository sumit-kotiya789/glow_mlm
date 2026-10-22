<?php

/**
 * Engine self-check against docs/RULES.md, on a throwaway in-memory SQLite DB.
 * Run: php tests/engine_check.php   (exit code 1 on any failure)
 */

declare(strict_types=1);

putenv('GLOW_DSN=sqlite::memory:');
require __DIR__ . '/../app/helpers.php';
require APP_ROOT . '/db/seed.php';

use App\Database;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\Ledger;
use App\Models\LuckyDraw;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Payout;

$failures = 0;
function check(string $label, mixed $expected, mixed $actual): void
{
    global $failures;
    $ok = is_float($expected) || is_float($actual)
        ? abs((float) $expected - (float) $actual) < 0.001
        : $expected === $actual;
    if (!$ok) {
        $failures++;
    }
    printf("%s  %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $ok ? '' : '  expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}
function throws(string $label, callable $fn): void
{
    try {
        $fn();
        check($label . ' (throws)', true, false);
    } catch (DomainException) {
        check($label . ' (throws)', true, true);
    }
}
function bal(int $id, string $wallet): float
{
    return (float) Database::value("SELECT {$wallet}_balance FROM users WHERE id = ?", [$id]);
}
function inv(int $id): array
{
    return (array) Database::one('SELECT * FROM investments WHERE id = ?', [$id]);
}
function member(string $code, ?int $sponsor): int
{
    $n = (int) substr($code, 2);
    User::create(['user_code' => $code, 'name' => $code, 'email' => "$code@example.com", 'mobile' => sprintf('+1000000%04d', $n),
        'country' => 'India', 'wallet' => sprintf('0x%040x', $n), 'password' => 'demo1234', 'txn_password' => 'txn1234'], $sponsor);
    return (int) User::findByCode($code)['id'];
}
function fund(int $userId, float $amount): void
{
    Deposit::approve(Deposit::submit($userId, $amount, '0x' . bin2hex(random_bytes(32))));
}
function day(int $n): string
{
    return Payout::addDays('2026-01-10', $n);
}

install_base(Database::pdo());
Setting::set('salary_directs', '1');   // small tree is enough to test salary
Setting::set('salary_level2', '1');

// Tree: A -> B -> C -> D, A -> E
freeze_clock(day(0) . ' 09:00:00');
$a = member('GL0000001', null);
$b = member('GL0000002', $a);
$c = member('GL0000003', $b);
$d = member('GL0000004', $c);
$e = member('GL0000005', $a);



echo "-- Deposits + signup bonus\n";
fund($b, 300);
check('first approved deposit pays no bonus', 300.0, bal($b, 'deposit'));
check('B bonus at registration', true, User::grantSignupBonus($b));
check('B bonus in deposit wallet', 305.0, bal($b, 'deposit'));
check('bonus is not income', 0.0, bal($b, 'income'));
check('no second bonus', false, User::grantSignupBonus($b));
check('B min invest 10 with bonus', 10.0, User::minInvest($b));
fund($b, 10);
throws('approve twice', fn () => Deposit::approve(1));
Setting::set('signup_bonus_enabled', '0');
check('bonus OFF at registration: none', false, User::grantSignupBonus($c));
fund($c, 300);
check('C deposit wallet, no bonus', 300.0, bal($c, 'deposit'));
check('C min invest 5 without bonus', 5.0, User::minInvest($c));
Setting::set('signup_bonus_enabled', '1');
fund($a, 200);
fund($d, 200);
fund($e, 200);

echo "-- Investing: direct 10% + level 1%, only to uplines with a running plan and N active directs for level N\n";
freeze_clock(day(0) . ' 10:00:00');
$bPlan = Investment::open($b, 'compound', 25, 100);
check('B deposit wallet after invest', 215.0, bal($b, 'deposit'));
check('A has no running plan: no direct income from B', 0.0, bal($a, 'income'));
$cPlan = Investment::open($c, 'compound', 25, 100);
check('B direct income from C (10%)', 10.0, bal($b, 'income'));
$dPlan = Investment::open($d, 'compound', 25, 100);
check('C direct income from D', 10.0, bal($c, 'income'));
check('B level-2 from D skipped: 1 active direct, needs 2', 10.0, bal($b, 'income'));
$ePlan = Investment::open($e, 'fixed', 25, 100);
check('A still inactive: nothing from E', 0.0, bal($a, 'income'));
throws('below minimum', fn () => Investment::open($b, 'compound', 25, 4));
throws('above maximum', fn () => Investment::open($b, 'compound', 25, 1001));
throws('more than deposit wallet', fn () => Investment::open($b, 'compound', 25, 500));
check('B activated', true, User::find($b)['activated_at'] !== null);

echo "-- Salary qualification (self >= 50, 1 direct, 1 level-2 for this test)\n";
Investment::open($a, 'fixed', 100, 50);
check('A qualified', true, User::find($a)['salary_qualified_at'] !== null);

echo "-- A active now with 2 active directs (B, E): level-2 income\n";
$aIncome = bal($a, 'income');
Investment::open($c, 'fixed', 25, 10);
check('A level-2 1% of C 10', $aIncome + 0.1, bal($a, 'income'));
freeze_clock(null);

echo "-- Day 1: 5% = 4% part + 1% profit, ROI-on-ROI\n";
$aIncome = bal($a, 'income');
Payout::run(day(1));
check('B profit to Compound ROI wallet', 1.0, bal($b, 'roi'));
check('B pending', 4.0, (float) inv($bPlan)['pending']);
check('B latest part', 4.0, (float) inv($bPlan)['latest_part']);
check('A ROI-on-ROI: L1 5% of B 4 + L2 1% of C 4, L3 from D skipped (2 active directs)', $aIncome + 0.20 + 0.04, bal($a, 'income'));
check('same date pays once', null, Payout::run(day(1)));

echo "-- Compound -> 104 (example from rules)\n";
Investment::compound($b, $bPlan);
check('B principal', 104.0, (float) inv($bPlan)['principal']);
check('B pending', 0.0, (float) inv($bPlan)['pending']);
throws('compound twice', fn () => Investment::compound($b, $bPlan));

echo "-- Day 2: move the part to the Compound ROI wallet -> 99.84\n";
Payout::run(day(2));
check('B part on 104', 4.16, (float) inv($bPlan)['latest_part']);
check('B profit on 104', 1.0 + 1.04, bal($b, 'roi'));
freeze_clock(day(2) . ' 12:00:00');
Investment::release($b, $bPlan, 4.16);
check('B principal after move', 99.84, (float) inv($bPlan)['principal']);
check('B pending after move', 0.0, (float) inv($bPlan)['pending']);
check('B part lands in Compound ROI wallet', 2.04 + 4.16, bal($b, 'roi'));
check('no withdrawal request created', 0, (int) Database::value('SELECT COUNT(*) FROM withdrawals WHERE user_id = ?', [$b]));
throws('more than pending', fn () => Investment::release($b, $bPlan, 1));
throws('plan source no longer requestable', fn () => Withdrawal::request($b, 'plan', 1));
freeze_clock(null);

echo "-- D does nothing for 3 days: pending 12, only latest 4 compoundable\n";
Payout::run(day(3));
check('D pending after 3 days', 12.0, (float) inv($dPlan)['pending']);
check('D latest part', 4.0, (float) inv($dPlan)['latest_part']);
freeze_clock(day(3) . ' 12:00:00');
Investment::release($d, $dPlan, 12);
check('D move 12 -> principal 88', 88.0, (float) inv($dPlan)['principal']);
freeze_clock(null);
check('C pending 12 before compound', 12.0, (float) inv($cPlan)['pending']);
Investment::compound($c, $cPlan);
check('C compound latest only -> 104', 104.0, (float) inv($cPlan)['principal']);
check('C keeps 8 withdraw-only', 8.0, (float) inv($cPlan)['pending']);
check('B day-3 part on 99.84', 3.99, (float) inv($bPlan)['latest_part']);

echo "-- Plan history rebuilds principal + pending from the ledger\n";
foreach (['B' => $bPlan, 'C' => $cPlan, 'D' => $dPlan] as $who => $planId) {
    $last = Investment::history(inv($planId))[count(Investment::history(inv($planId))) - 1];
    check("$who history principal", (float) inv($planId)['principal'], $last['principal']);
    check("$who history pending", (float) inv($planId)['pending'], $last['held']);
}
$bEvents = array_column(Investment::history(inv($bPlan)), 'event');
check('B history events', ['Invested', 'Payout day 1/25', 'Compounded', 'Payout day 2/25', 'Moved to Compound ROI wallet',
    'Payout day 3/25'], $bEvents);

echo "-- Withdrawal limits\n";
freeze_clock(day(3) . ' 13:00:00');
throws('below $1', fn () => Withdrawal::request($b, 'roi', 0.5));
throws('more than wallet', fn () => Withdrawal::request($b, 'roi', 50));
Setting::set('max_withdraw', '1000');
fund($e, 500);
Investment::open($e, 'compound', 25, 5);  // E active investment = 105 -> daily limit 105
check('E roi wallet before', 0.0, bal($e, 'roi'));
check('E income', 0.0, bal($e, 'income'));
throws('over daily limit', fn () => Withdrawal::request($e, 'income', 106));
freeze_clock(null);

echo "-- Run to day 25: maturity + salary pay day (1 Feb = day 22)\n";
for ($n = 4; $n <= 25; $n++) {
    Payout::run(day($n));
}
check('E fixed matured', 'Matured', inv($ePlan)['status']);
check('E fixed pays 100 + 25 x 5 to FD ROI wallet', 225.0, bal($e, 'fd'));
check('B compound matured', 'Matured', inv($bPlan)['status']);
check('B pending cleared at maturity', 0.0, (float) inv($bPlan)['pending']);
$maturity = fn (int $planId) => (float) Database::value("SELECT amount FROM ledger WHERE type = 'maturity' AND ref_id = ?", [$planId]);
check('D compound maturity: invested 100 not returned, only pending 22 x 3.52', 77.44, $maturity($dPlan));
check('C compound maturity: pending 8 + 22 x 4.16 + compounded 4', 103.52, $maturity($cPlan));
check('compound maturity goes to Compound ROI wallet', 'roi', Database::value("SELECT wallet FROM ledger WHERE type = 'maturity' AND ref_id = ?", [$cPlan]));
freeze_clock(day(25) . ' 12:00:00');
$fdW = Withdrawal::request($e, 'fd', 1);
check('withdraw from FD ROI wallet', 224.0, bal($e, 'fd'));
Withdrawal::reject($fdW, 'test');
check('rejected FD withdrawal refunds FD ROI wallet', 225.0, bal($e, 'fd'));
freeze_clock(null);
$eHistory = Investment::history(inv($ePlan));
check('E fixed history: invest + 25 payouts', 26, count($eHistory));
check('E fixed history paid to ROI', 225.0, array_sum(array_column($eHistory, 'roi')));
check('A salary month 1 paid', 1, (int) User::find($a)['salary_months_paid']);
check('A salary amount', 100.0, (float) Database::value("SELECT SUM(amount) FROM ledger WHERE user_id = ? AND type = 'salary'", [$a]));

echo "-- Lucky draw\n";
$draw = LuckyDraw::create('Test draw', 100, 3);
LuckyDraw::addWinner($draw, $a);
throws('duplicate winner', fn () => LuckyDraw::addWinner($draw, $a));
$added = LuckyDraw::fillRandom($draw);
check('random fill only from active investors (B/C/D matured, so only E)', 1, $added);
$before = bal($a, 'income');
LuckyDraw::distribute($draw);
check('A got prize', $before + 100, bal($a, 'income'));
throws('distribute twice', fn () => LuckyDraw::distribute($draw));

echo "-- Hour mode (testing): each day of a plan becomes one hour\n";
Setting::set('payout_period', 'hour');
$f = member('GL0000006', $a);
fund($f, 100);
freeze_clock('2026-03-01 12:08:00');
$fPlan = Investment::open($f, 'compound', 25, 100);
freeze_clock(null);
Payout::run('2026-03-01 12:00');
check('bought 12:08: not paid in the 12:00 hour', 0, (int) inv($fPlan)['days_paid']);
Payout::run('2026-03-01 13:00');
check('first payout at 13:00', 1, (int) inv($fPlan)['days_paid']);
check('hourly 1% profit', 1.0, bal($f, 'roi'));
check('next period is the next hour', '2026-03-01 14:00', Payout::next('2026-03-01 13:00'));
check('simulate 24 more hours', 24, count(Payout::simulate(24)));
check('25-"day" plan matured after 25 hours', 'Matured', inv($fPlan)['status']);
check('first payout label', '01-Mar-2026 13:00', Investment::firstPayout(inv($fPlan)));
Setting::set('payout_period', 'day');
check('day mode: next day', '2026-03-02', Payout::next('2026-03-01 13:00'));

echo "-- Transfers (P2P + wallet to Deposit)\n";
$p = member('GL0000010', null);   // has an approved deposit
$q = member('GL0000011', null);   // never deposited
$r = member('GL0000012', null);
fund($p, 100);
User::grantSignupBonus($q);       // bonus only, no approved deposit
$qBefore = bal($q, 'deposit');
throws('no approved deposit cannot P2P', fn () => Transfer::p2p($q, 'GL0000010', 1));
check('sender untouched after refused P2P', $qBefore, bal($q, 'deposit'));
throws('below $1', fn () => Transfer::p2p($p, 'GL0000011', 0.5));
throws('to self', fn () => Transfer::p2p($p, 'GL0000010', 5));
throws('unknown ID', fn () => Transfer::p2p($p, 'GL9999999', 5));
throws('more than deposit wallet', fn () => Transfer::p2p($p, 'GL0000011', 500));
Transfer::p2p($p, 'gl0000011', 40);   // lower-case ID is accepted
check('sender deposit debited', 60.0, bal($p, 'deposit'));
check('receiver deposit credited', $qBefore + 40, bal($q, 'deposit'));
check('receiver ledger from sender', $p, (int) Database::value("SELECT from_user_id FROM ledger WHERE user_id = ? AND type = 'p2p_in'", [$q]));
User::setBlocked($r, true);
throws('blocked receiver', fn () => Transfer::p2p($p, 'GL0000012', 5));
User::setBlocked($r, false);
Database::run('UPDATE users SET roi_balance = 30, fd_balance = 15, income_balance = 20 WHERE id = ?', [$p]);
Transfer::toDeposit($p, 'roi', 30);
Transfer::toDeposit($p, 'fd', 15);
Transfer::toDeposit($p, 'income', 20);
check('roi emptied', 0.0, bal($p, 'roi'));
check('fd emptied', 0.0, bal($p, 'fd'));
check('income emptied', 0.0, bal($p, 'income'));
check('deposit after moves', 125.0, bal($p, 'deposit'));
throws('roi overdraw', fn () => Transfer::toDeposit($p, 'roi', 1));
throws('bad source', fn () => Transfer::toDeposit($p, 'plan', 1));
check('transfer history rows (p)', 7, Transfer::history($p, 1)['total']);

echo "-- Signup bonus cannot be sent\n";
Setting::set('signup_bonus_enabled', '1');
$s = member('GL0000013', null);
User::grantSignupBonus($s);
fund($s, 50);                                   // deposit 50 + bonus 5 = 55 in wallet
check('bonus locked', 5.0, Transfer::lockedBonus($s));
check('sendable = deposit only', 50.0, Transfer::sendable($s));
throws('cannot send the bonus part', fn () => Transfer::p2p($s, 'GL0000010', 51));
Transfer::p2p($s, 'GL0000010', 50);
check('bonus stays in wallet', 5.0, bal($s, 'deposit'));
check('nothing more sendable', 0.0, Transfer::sendable($s));
Database::run('UPDATE users SET income_balance = 20 WHERE id = ?', [$s]);
Transfer::toDeposit($s, 'income', 20);          // earned money moved in is sendable
check('moved income is sendable', 20.0, Transfer::sendable($s));
Transfer::p2p($s, 'GL0000010', 20);
check('bonus still locked', 5.0, Transfer::lockedBonus($s));
Database::run('UPDATE users SET deposit_balance = deposit_balance + 100 WHERE id = ?', [$s]);
Investment::open($s, 'compound', 25, 10);       // investing spends the bonus first
check('bonus unlocked after investing >= bonus', 0.0, Transfer::lockedBonus($s));

echo "-- Transaction password\n";
$t = member('GL0000014', null);
User::verifyTxnPassword($t, 'txn1234');
check('right password passes', true, true);
throws('wrong password', fn () => User::verifyTxnPassword($t, 'nope'));
throws('empty password', fn () => User::verifyTxnPassword($t, ''));
throws('login password is not the transaction password', fn () => User::verifyTxnPassword($t, 'demo1234'));
throws('4th wrong try', fn () => User::verifyTxnPassword($t, 'x4'));
throws('5th wrong try locks', fn () => User::verifyTxnPassword($t, 'x5'));
throws('locked: even the right password fails', fn () => User::verifyTxnPassword($t, 'txn1234'));
$previousClock = freeze_clock(date('Y-m-d H:i:s', strtotime(now() . ' +20 minutes')));
User::verifyTxnPassword($t, 'txn1234');
check('unlocked after 15 minutes', 0, (int) Database::value('SELECT txn_fails FROM users WHERE id = ?', [$t]));
freeze_clock($previousClock);
User::setTxnPassword($t, 'new-secret');
throws('old password after change', fn () => User::verifyTxnPassword($t, 'txn1234'));
User::verifyTxnPassword($t, 'new-secret');
$noTxn = member('GL0000015', null);
Database::run('UPDATE users SET txn_password_hash = NULL WHERE id = ?', [$noTxn]);
throws('no transaction password set yet', fn () => User::verifyTxnPassword($noTxn, 'anything'));

echo "-- Daily withdrawal limit after maturity\n";
$m = member('GL0000017', null);
check('never invested = limit 0', 0.0, User::withdrawLimit($m));
Database::run('UPDATE users SET deposit_balance = 500 WHERE id = ?', [$m]);
$m1 = Investment::open($m, 'fixed', 25, 100);
$m2 = Investment::open($m, 'compound', 25, 50);
check('running plans = limit', 150.0, User::withdrawLimit($m));
Database::run("UPDATE investments SET status = 'Matured', matured_at = '2026-03-01 00:00:00' WHERE id IN (?, ?)", [$m1, $m2]);
check('matured: limit stays at the last matured plans', 150.0, User::withdrawLimit($m));
$m3 = Investment::open($m, 'compound', 25, 20);
check('a new running plan takes over', 20.0, User::withdrawLimit($m));

echo "-- ROI returns split (Fixed / Compound)\n";
$u = member('GL0000016', null);
Database::run('UPDATE users SET deposit_balance = 100 WHERE id = ?', [$u]);
$cid = Investment::open($u, 'compound', 25, 10);
$fid = Investment::open($u, 'fixed', 25, 10);
Ledger::post($u, 'roi', 'profit', 0.1, ['ref_id' => $cid]);
Ledger::post($u, 'roi', 'profit', 0.1, ['ref_id' => $cid]);
Ledger::post($u, 'roi', 'maturity', 20.0, ['ref_id' => $cid]);
Ledger::post($u, 'fd', 'maturity', 22.5, ['ref_id' => $fid]);
$split = Ledger::roiReturns($u);
check('compound returns = profit + maturity', 20.2, $split['compound']);
check('fixed returns = maturity incl. principal', 22.5, $split['fixed']);
$before = inv($cid);
$old = Database::insert('withdrawals', ['user_id' => $u, 'source' => 'plan', 'investment_id' => $cid, 'amount' => 2, 'fee' => 0,
    'net_amount' => 2, 'wallet' => '0x0', 'status' => 'Pending', 'created_at' => now()]);
Withdrawal::reject($old, 'test');
check('old plan withdrawal rejected: back into pending', (float) $before['pending'] + 2, (float) inv($cid)['pending']);
check('old plan withdrawal rejected: back into principal', (float) $before['principal'] + 2, (float) inv($cid)['principal']);
$cards = Ledger::returnCards($u);
check('2 separate boxes, no combined', 2, count($cards));
check('compound box', '$20.20', $cards[0]['value']);
check('FD box', '$22.50', $cards[1]['value']);

check('BscScan link for a valid hash', 'https://bscscan.com/tx/0x' . str_repeat('a', 64), bscscan_cell('0x' . str_repeat('a', 64))[2]);
check('no BscScan link without a hash', '—', bscscan_cell(null));
check('no BscScan link for a bad hash', '—', bscscan_cell('0x123'));

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit($failures ? 1 : 0);
