-- GLOW demo schema. Plain SQL that runs on MySQL/MariaDB and SQLite.
-- {{AUTO_ID}} is replaced by seed.php with the driver's auto-increment primary key.
-- Business rules: docs/RULES.md

DROP TABLE IF EXISTS payout_runs;
DROP TABLE IF EXISTS lucky_draw_winners;
DROP TABLE IF EXISTS lucky_draws;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS ledger;
DROP TABLE IF EXISTS withdrawals;
DROP TABLE IF EXISTS investments;
DROP TABLE IF EXISTS deposits;
DROP TABLE IF EXISTS plan_terms;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS trade_pairs;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS users;
-- tables from the first UI-only version
DROP TABLE IF EXISTS salary_achievers;
DROP TABLE IF EXISTS lucky_winners;
DROP TABLE IF EXISTS team_members;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS user_packages;
DROP TABLE IF EXISTS packages;

CREATE TABLE users (
    id {{AUTO_ID}},
    user_code VARCHAR(12) NOT NULL UNIQUE,
    sponsor_id INT NULL,
    name VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    country VARCHAR(60) NOT NULL,
    wallet VARCHAR(42) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    deposit_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
    roi_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
    income_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
    activated_at DATETIME NULL,
    signup_bonus_paid INT NOT NULL DEFAULT 0,
    salary_qualified_at DATETIME NULL,
    salary_months_paid INT NOT NULL DEFAULT 0,
    salary_last_month VARCHAR(7) NULL,
    is_blocked INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (sponsor_id) REFERENCES users (id)
);

CREATE TABLE admins (
    id {{AUTO_ID}},
    username VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
);

-- Every admin-configurable number lives here (see Setting::DEFAULTS).
CREATE TABLE settings (
    name VARCHAR(60) NOT NULL PRIMARY KEY,
    value VARCHAR(255) NOT NULL
);

-- 25 / 50 / 100 day terms. Daily rate = principal_rate + profit_rate (in %).
CREATE TABLE plan_terms (
    id {{AUTO_ID}},
    days INT NOT NULL UNIQUE,
    principal_rate DECIMAL(6,3) NOT NULL,
    profit_rate DECIMAL(6,3) NOT NULL,
    is_active INT NOT NULL DEFAULT 1
);

CREATE TABLE deposits (
    id {{AUTO_ID}},
    user_id INT NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    tx_hash VARCHAR(100) NOT NULL UNIQUE,
    status VARCHAR(12) NOT NULL DEFAULT 'Pending',
    admin_note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users (id)
);

-- plan: 'compound' or 'fixed'.
-- compound: principal moves with compound/withdraw; pending = unclaimed 4% parts;
--           latest_part = the only part that may still be compounded.
-- fixed:    principal never moves; accrued = locked ROI paid at maturity.
CREATE TABLE investments (
    id {{AUTO_ID}},
    user_id INT NOT NULL,
    plan VARCHAR(10) NOT NULL,
    term_days INT NOT NULL,
    principal_rate DECIMAL(6,3) NOT NULL,
    profit_rate DECIMAL(6,3) NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    principal DECIMAL(14,2) NOT NULL,
    pending DECIMAL(14,2) NOT NULL DEFAULT 0,
    latest_part DECIMAL(14,2) NOT NULL DEFAULT 0,
    accrued DECIMAL(14,2) NOT NULL DEFAULT 0,
    days_paid INT NOT NULL DEFAULT 0,
    status VARCHAR(12) NOT NULL DEFAULT 'Active',
    started_at DATETIME NOT NULL,
    last_paid_on VARCHAR(16) NULL,
    matured_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users (id)
);

-- source: 'roi', 'income' or 'plan' (pending principal of investment_id).
CREATE TABLE withdrawals (
    id {{AUTO_ID}},
    user_id INT NOT NULL,
    source VARCHAR(10) NOT NULL,
    investment_id INT NULL,
    amount DECIMAL(14,2) NOT NULL,
    fee DECIMAL(14,2) NOT NULL DEFAULT 0,
    net_amount DECIMAL(14,2) NOT NULL,
    wallet VARCHAR(42) NOT NULL,
    status VARCHAR(12) NOT NULL DEFAULT 'Pending',
    admin_note VARCHAR(255) NULL,
    payout_hash VARCHAR(100) NULL,
    created_at DATETIME NOT NULL,
    processed_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users (id),
    FOREIGN KEY (investment_id) REFERENCES investments (id)
);

-- Every balance movement. wallet: deposit | roi | income | plan (plan = no wallet balance).
-- amount is signed: + credit, - debit.
CREATE TABLE ledger (
    id {{AUTO_ID}},
    user_id INT NOT NULL,
    wallet VARCHAR(10) NOT NULL,
    type VARCHAR(20) NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    from_user_id INT NULL,
    level INT NULL,
    ref_id INT NULL,
    note VARCHAR(160) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id)
);

CREATE TABLE lucky_draws (
    id {{AUTO_ID}},
    title VARCHAR(80) NOT NULL,
    prize DECIMAL(14,2) NOT NULL,
    winners_count INT NOT NULL,
    status VARCHAR(12) NOT NULL DEFAULT 'Draft',
    created_at DATETIME NOT NULL,
    distributed_at DATETIME NULL
);

CREATE TABLE lucky_draw_winners (
    id {{AUTO_ID}},
    draw_id INT NOT NULL,
    user_id INT NOT NULL,
    picked_by VARCHAR(10) NOT NULL,
    UNIQUE (draw_id, user_id),
    FOREIGN KEY (draw_id) REFERENCES lucky_draws (id),
    FOREIGN KEY (user_id) REFERENCES users (id)
);

-- One row per paid period ('2026-10-15' or, in hour mode, '2026-10-15 13:00'): never paid twice.
CREATE TABLE payout_runs (
    run_date VARCHAR(16) NOT NULL PRIMARY KEY,
    investments_paid INT NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE tickets (
    id {{AUTO_ID}},
    user_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL,
    admin_reply TEXT NULL,
    created_at DATETIME NOT NULL,
    replied_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users (id)
);

-- Sample "AI trading" table on the dashboard (display only).
CREATE TABLE trade_pairs (
    id {{AUTO_ID}},
    pair VARCHAR(20) NOT NULL,
    side VARCHAR(10) NOT NULL,
    leverage INT NOT NULL,
    entry_price DECIMAL(20,6) NOT NULL,
    latest_price DECIMAL(20,6) NOT NULL,
    pnl DECIMAL(10,2) NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE INDEX idx_users_sponsor ON users (sponsor_id);
CREATE INDEX idx_ledger_user_type ON ledger (user_id, type);
CREATE INDEX idx_investments_user ON investments (user_id, status);
CREATE INDEX idx_withdrawals_user ON withdrawals (user_id, created_at);
CREATE INDEX idx_deposits_status ON deposits (status);
