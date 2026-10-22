# GLOW — Mobile-first Investment Network Demo

> **DEMO ONLY.** No real payments, blockchain calls or emails. The deposit address is a placeholder,
> transaction hashes are stored but never verified, and withdrawals are "paid" by an admin clicking a button.

Plain PHP 8.1+ (no framework), PDO (MySQL/MariaDB or SQLite), vanilla JS, CSS custom properties, Google Font Inter.
Mobile-first: designed at 390px, scaled up at 768px and 1024px.

Business rules (plans, incomes, salary, lucky draw, withdrawals): **[docs/RULES.md](docs/RULES.md)**.

## Run

Requirements: PHP 8.1+ with `pdo_mysql`, XAMPP MySQL running. Database **`glow`** on `127.0.0.1`, user `root`,
empty password (see `config.php`).

```bash
mysql -u root -e "CREATE DATABASE glow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"   # once
php db/seed.php                      # tables + demo data (re-run any time to reset)
php db/seed.php --fresh              # OR empty start: admin + settings + one top member GL0000001
php -S localhost:8000 -t public      # open http://localhost:8000
```

XAMPP / Apache alternative: `http://localhost/MLM/GLOW/public/` (needs `mod_rewrite`).

**Upgrading an existing database** (keeps your data): `php db/migrate.php` adds any missing columns
(transaction password, FD ROI wallet). Safe to run again.

## Demo logins

| Who    | Login       | Password                                  |
| ------ | ----------- | ----------------------------------------- |
| Member | `GL0000001` | `demo1234`                                |
| Admin  | `admin`     | `admin1234` → http://localhost:8000/admin |

Every seeded member (`GL0000101`…) uses `demo1234`. **Transaction password** (withdrawal, P2P, transfer) for every seeded member is `txn1234`; members set or change it under Profile. The seeder builds a 35-member tree over 10 levels (plus 100 extra investors at levels 3-4) by running
the real engine: approved deposits, investments, 4 days of payouts, one lucky draw, and some pending
deposits/withdrawals for the admin. Today's payout is left due.

Test accounts (also printed by `php db/seed.php`):

| Account                  | Ready to test                                                                                                    |
| ------------------------ | ---------------------------------------------------------------------------------------------------------------- |
| `GL0000001`              | Salary at 5/5 directs, 8/10 level-2; pending $50 deposit (no bonus) and $2 ROI withdrawal; 2 tickets             |
| `GL0000002`              | Maturity Tester (no sponsor): compound + FD at day 24/25. **Run Due Payouts** matures both, then limit $0        |
| `GL0000209`, `GL0000210` | $100 in Deposit wallet. Invest with both and GL0000001 qualifies; set salary pay day to tomorrow, **+1** pays it |
| `GL0000101`              | Compound plan, 4 days pending, $8 of it moved to Compound ROI wallet; pending $5 income withdrawal             |
| `GL0000106`              | First deposit pending (approve pays $5 signup bonus), one rejected                                               |
| `GL0000211`              | Registered, never deposited                                                                                      |
| `GL0000212`              | Blocked                                                                                                          |
| `GL0000203`              | Salary achiever (12 active directs, 10 active level-2), shown with a trophy in the Level Team tree               |
| `GL0000401`…`GL0000500`  | Extra active investors (level 3-4 of GL0000001), so a 10 + 90 lucky draw fills                                   |
| Admin                    | Weekly Lucky Draw #2 draft (100 slots, 131 eligible), 1 open ticket                                              |

## Payout (daily, or hourly for testing)

```bash
php cron/payout.php              # pays every due period up to now
php cron/payout.php --next       # testing: pay the next period right now
php cron/payout.php --next=25    # testing: pay the next 25 periods right now
```

Admin → Settings → **Payout period**: `Day` (live) or `Hour` (testing: every "day" of the rules lasts one hour,
so a 25-day plan matures after 25 hours). Salary window/pay day and the daily withdrawal limit stay on calendar days.
Schedule `cron/payout.php` every 15 minutes in Windows Task Scheduler. A period is never paid twice.

## Engine check

```bash
php tests/engine_check.php       # runs the rules on an in-memory SQLite DB, exit 1 on failure
```

Covers the $100 → $104 / $99.84 / $88 / $225 examples, incomes, salary, limits, refunds, lucky draw.

## Folder map

```
config.php              DB DSN, timezone (Asia/Kolkata), page size
docs/RULES.md           Agreed business rules (the spec)
db/schema.sql           Portable SQL ({{AUTO_ID}} swapped per driver)
db/seed.php             install_base() + seed_database()
cron/payout.php         Daily payout runner
tests/engine_check.php  Engine self-check
app/
  helpers.php           Autoloader, config(), e(), view(), flash/toast, money(), pct(), icon(), now()/freeze_clock()
  Router.php            Exact-match routes, auth/guest guard, CSRF check on every POST
  Database.php          PDO wrapper (nestable transactions, paginate)
  Auth.php / Csrf.php   Session login (blocked users rejected) / CSRF token
  AdminAuth.php         Admin session login
  models/               User (tree), Setting, PlanTerm, Ledger (every balance move), Deposit, Investment,
                        Withdrawal, LuckyDraw, Ticket, TradePair
  services/             Commission (direct, level, ROI-on-ROI), Salary, Payout
  controllers/          Auth, Dashboard, Feed, Package (invest/plans), Profile, Team, Wallet
  controllers/admin/    Auth, Dashboard, Finance, User, LuckyDraw, Ticket, Settings
  views/                layouts (app, admin, guest), partials, pages, pages/admin
public/
  index.php             Front controller + route table
  assets/css            base (tokens) · layout · components · pages
  assets/js             app · sidebar · countdown · table-search · toast · modal · vendor/qrcode.js (MIT)
```

## Member routes

`/dashboard` `/package-details` (invest) `/my-packages` `/compound-packages` `/deposit` `/deposit-history`
`/withdrawal` `/withdrawal-history` `/income` `/direct-team` `/level-team` `/team-business` `/salary-achievers`
`/lucky-winners` `/live-feed` `/live-trading` `/profile` `/calculator` `/support`

## Admin panel (`/admin`)

Separate login (own session key, so admin and member can be logged in together). Sidebar docks on desktop.

| Page        | What the admin does                                                                                                                        |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Dashboard   | Totals, pending queues, **Run Due Payouts**, testing buttons **+1 / +5 / +25** days (or hours)                                             |
| Deposits    | Approve (credits Deposit wallet, pays signup bonus on first deposit) or reject with reason                                                 |
| Withdrawals | Send manually, then **Mark Paid** (optional payout hash) or reject (refunds the source)                                                    |
| Ledger      | Every wallet movement, filter by type or `?user=GL…`                                                                                       |
| Users       | Search, member detail (wallets, salary progress, uplines, team per level, plans, ledger), block/unblock, manual wallet adjustment (logged) |
| Investments | All plans, Active / Matured                                                                                                                |
| Lucky Draws | Create, hand-pick favourites by search, fill the rest at random (active investors only), distribute                                        |
| Tickets     | Reply; the member sees it on their Support page                                                                                            |
| Settings    | Every number from docs/RULES.md + plan terms (days and rates per term)                                                                     |

## Switching to SQLite (no MySQL needed)

Set `dsn` in `config.php` to `'sqlite:' . __DIR__ . '/db/demo.sqlite'` and `user`/`pass` to `null`.
The file is built automatically on the first request.

## Security notes

- `password_hash`, session id regenerated on login, blocked users logged out.
- CSRF token on every POST, checked centrally in the router. All output escaped with `e()`; prepared statements only.
- Every balance change goes through `Ledger::post()`, which refuses negative balances; approve/reject/pay
  actions are atomic (`WHERE status = 'Pending'`), so double clicks can't pay twice.
