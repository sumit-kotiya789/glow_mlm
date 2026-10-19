<?php

/**
 * One-off upgrade for an EXISTING database (keeps all data): adds the transaction-password columns.
 * Run once from the project folder:  php db/migrate_txn_password.php
 * Members then set their transaction password under Profile (until then withdrawals / transfers ask them to).
 * A fresh `php db/seed.php` already includes these columns and does not need this script.
 */

declare(strict_types=1);

require __DIR__ . '/../app/helpers.php';

use App\Database;

$columns = [
    'txn_password_hash' => 'VARCHAR(255) NULL',
    'txn_fails' => 'INT NOT NULL DEFAULT 0',
    'txn_locked_until' => 'DATETIME NULL',
];
foreach ($columns as $name => $definition) {
    try {
        Database::pdo()->exec("ALTER TABLE users ADD COLUMN $name $definition");
        echo "added users.$name\n";
    } catch (PDOException $e) {
        echo "users.$name already there (skipped)\n";
    }
}
