<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOStatement;

/**
 * Thin PDO wrapper. One shared connection, prepared statements only.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO((string) config('db.dsn'), config('db.user'), config('db.pass'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            if (self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                self::$pdo->exec('PRAGMA foreign_keys = ON');
            }
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** @return list<array<string, mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @return list<mixed> first column of every row */
    public static function column(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    /** "?, ?, ?" for an IN (...) list. */
    public static function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, max(1, count($values)), '?'));
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function insert(string $table, array $row): int
    {
        $cols = array_keys($row);
        self::run(
            sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $table,
                implode(', ', $cols),
                implode(', ', array_map(static fn ($c) => ':' . $c, $cols))
            ),
            $row
        );
        return (int) self::pdo()->lastInsertId();
    }

    /** Run $fn inside a DB transaction (joins the outer one when already inside a transaction). */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Paginate a SELECT (must not contain LIMIT).
     *
     * @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int}
     */
    public static function paginate(string $sql, array $params, int $page, ?int $perPage = null): array
    {
        $perPage ??= (int) config('per_page', 10);
        $total = (int) self::value("SELECT COUNT(*) FROM ($sql) sub", $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $rows = self::all($sql . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params);
        return ['rows' => $rows, 'page' => $page, 'pages' => $pages, 'total' => $total];
    }
}
