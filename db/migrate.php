<?php

/**
 * Upgrade for an EXISTING database (keeps all data): adds any missing users columns.
 * Safe to run again: columns already there are skipped. Run from the project folder:  php db/migrate.php
 *  - txn_password_hash / txn_fails / txn_locked_until: transaction password. Members then set it under Profile.
 *  - fd_balance: FD ROI wallet. Fixed Deposit maturities paid before this upgrade stay in the Compound ROI wallet.
 * A fresh `php db/seed.php` already includes these columns and does not need this script.
 */

declare(strict_types=1);

require __DIR__ . '/../app/helpers.php';

use App\Database;

$columns = [
    'txn_password_hash' => 'VARCHAR(255) NULL',
    'txn_fails' => 'INT NOT NULL DEFAULT 0',
    'txn_locked_until' => 'DATETIME NULL',
    'fd_balance' => 'DECIMAL(14,2) NOT NULL DEFAULT 0',
];
foreach ($columns as $name => $definition) {
    try {
        Database::pdo()->exec("ALTER TABLE users ADD COLUMN $name $definition");
        echo "added users.$name\n";
    } catch (PDOException $e) {
        echo "users.$name already there (skipped)\n";
    }
}
