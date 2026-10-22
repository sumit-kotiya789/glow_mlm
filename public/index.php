<?php

/**
 * Front controller: bootstrap, auto-seed, session, routes.
 */

declare(strict_types=1);

use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\FinanceController as AdminFinanceController;
use App\Controllers\Admin\LuckyDrawController as AdminLuckyDrawController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Controllers\Admin\TicketController as AdminTicketController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\FeedController;
use App\Controllers\PackageController;
use App\Controllers\ProfileController;
use App\Controllers\TeamController;
use App\Controllers\WalletController;
use App\Database;
use App\Router;

require dirname(__DIR__) . '/app/helpers.php';

// Zero-config: build the SQLite  DB on first run.
$dsn = (string) config('db.dsn');
if (str_starts_with($dsn, 'sqlite:') && !is_file(substr($dsn, 7))) {
    require APP_ROOT . '/db/seed.php';
    seed_database(Database::pdo());
}

ini_set('session.gc_maxlifetime', (string) (60 * 60 * 24 * 30));
session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

// Flash data lives for exactly one request.
$GLOBALS['__flash'] = $_SESSION['_flash'] ?? [];
unset($_SESSION['_flash']);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$router = new Router();

$router->get('/', [DashboardController::class, 'home'], 'any');
$router->get('/login', [AuthController::class, 'showLogin'], 'guest');
$router->post('/login', [AuthController::class, 'login'], 'guest');
$router->get('/register', [AuthController::class, 'showRegister'], 'guest');
$router->post('/register', [AuthController::class, 'register'], 'guest');
$router->get('/register/sponsor', [AuthController::class, 'sponsorLookup'], 'guest');
$router->get('/forgot-password', [AuthController::class, 'forgot'], 'guest');
$router->post('/logout', [AuthController::class, 'logout'], 'any');

$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/live-feed', [FeedController::class, 'liveFeed']);
$router->get('/live-trading', [FeedController::class, 'liveTrading']);
$router->get('/profile', [ProfileController::class, 'show']);
$router->post('/profile/txn-password', [ProfileController::class, 'saveTxnPassword']);
$router->get('/support', [ProfileController::class, 'support']);
$router->post('/support', [ProfileController::class, 'createTicket']);

// Investing (package-details = invest page, my-packages = all plans, compound-packages = compound only)
$router->get('/package-details', [PackageController::class, 'details']);
$router->post('/invest', [PackageController::class, 'invest']);
$router->get('/my-packages', [PackageController::class, 'mine']);
$router->get('/compound-packages', [PackageController::class, 'compound']);
$router->get('/investment', [PackageController::class, 'history']);
$router->post('/investments/compound',[PackageController::class, 'compoundPart']);
$router->post('/investments/release', [PackageController::class, 'releasePending']);
$router->get('/calculator', [PackageController::class, 'calculator']);

$router->get('/direct-team', [TeamController::class, 'direct']);
$router->get('/level-team', [TeamController::class, 'level']);
$router->get('/team-business', [TeamController::class, 'business']);
$router->get('/salary-achievers', [TeamController::class, 'salaryAchievers']);
$router->get('/lucky-winners', [TeamController::class, 'luckyWinners']);

$router->get('/wallet', [WalletController::class, 'wallet']);
$router->get('/deposit', [WalletController::class, 'deposit']);
$router->post('/deposit', [WalletController::class, 'submitDeposit']);
$router->get('/deposit-history', [WalletController::class, 'depositHistory']);
$router->get('/withdrawal', [WalletController::class, 'withdrawal']);
$router->post('/withdrawal', [WalletController::class, 'submitWithdrawal']);
$router->get('/withdrawal-history', [WalletController::class, 'withdrawalHistory']);
$router->get('/income', [WalletController::class, 'income']);
$router->get('/transfer', [WalletController::class, 'transfer']);
$router->post('/transfer/p2p', [WalletController::class, 'submitP2p']);
$router->post('/transfer/wallet', [WalletController::class, 'submitWalletMove']);
$router->get('/transfer/lookup', [WalletController::class, 'lookupUser']);
$router->get('/transfer-history', [WalletController::class, 'transferHistory']);

// ---------- Admin panel ----------
$router->get('/admin/login', [AdminAuthController::class, 'showLogin'], 'admin-guest');
$router->post('/admin/login', [AdminAuthController::class, 'login'], 'admin-guest');
$router->post('/admin/logout', [AdminAuthController::class, 'logout'], 'any');

$router->get('/admin', [AdminDashboardController::class, 'index'], 'admin');
$router->post('/admin/payout/run', [AdminDashboardController::class, 'runPayout'], 'admin');
$router->post('/admin/payout/simulate', [AdminDashboardController::class, 'simulate'], 'admin');

$router->get('/admin/deposits', [AdminFinanceController::class, 'deposits'], 'admin');
$router->post('/admin/deposits/approve', [AdminFinanceController::class, 'approveDeposit'], 'admin');
$router->post('/admin/deposits/reject', [AdminFinanceController::class, 'rejectDeposit'], 'admin');
$router->get('/admin/withdrawals', [AdminFinanceController::class, 'withdrawals'], 'admin');
$router->post('/admin/withdrawals/paid', [AdminFinanceController::class, 'payWithdrawal'], 'admin');
$router->post('/admin/withdrawals/reject', [AdminFinanceController::class, 'rejectWithdrawal'], 'admin');
$router->get('/admin/ledger', [AdminFinanceController::class, 'ledger'], 'admin');

$router->get('/admin/users', [AdminUserController::class, 'index'], 'admin');
$router->get('/admin/user', [AdminUserController::class, 'show'], 'admin');
$router->post('/admin/user/block', [AdminUserController::class, 'toggleBlock'], 'admin');
$router->post('/admin/user/adjust', [AdminUserController::class, 'adjust'], 'admin');
$router->get('/admin/investments', [AdminUserController::class, 'investments'], 'admin');

$router->get('/admin/lucky-draws', [AdminLuckyDrawController::class, 'index'], 'admin');
$router->post('/admin/lucky-draws', [AdminLuckyDrawController::class, 'create'], 'admin');
$router->get('/admin/lucky-draw', [AdminLuckyDrawController::class, 'show'], 'admin');
$router->post('/admin/lucky-draw/add', [AdminLuckyDrawController::class, 'add'], 'admin');
$router->post('/admin/lucky-draw/remove', [AdminLuckyDrawController::class, 'remove'], 'admin');
$router->post('/admin/lucky-draw/fill', [AdminLuckyDrawController::class, 'fill'], 'admin');
$router->post('/admin/lucky-draw/distribute', [AdminLuckyDrawController::class, 'distribute'], 'admin');

$router->get('/admin/tickets', [AdminTicketController::class, 'index'], 'admin');
$router->post('/admin/tickets/reply', [AdminTicketController::class, 'reply'], 'admin');

$router->get('/admin/settings', [AdminSettingsController::class, 'edit'], 'admin');
$router->post('/admin/settings', [AdminSettingsController::class, 'update'], 'admin');

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', current_path());
