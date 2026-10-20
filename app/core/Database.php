<?php

/**
 * Conexão PDO única. Todas as consultas usam prepared statements.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Config::get('db.host'),
                Config::get('db.port'),
                Config::get('db.name')
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            if (Config::get('db.ssl')) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = Config::get('db.ssl_ca');
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = Config::get('db.ssl_verify');
            }

            self::$pdo = new PDO($dsn, Config::get('db.user'), Config::get('db.pass'), $options);
            self::$pdo->exec("SET time_zone = '-03:00'");
            // Mesmo modo do MariaDB local (o MySQL 8 liga ONLY_FULL_GROUP_BY por padrão).
            self::$pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        }

        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', array_map(static fn ($c) => ':' . $c, $columns))
        );
        self::query($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(static fn ($c) => "{$c} = :set_{$c}", array_keys($data)));
        $params = [];
        foreach ($data as $column => $value) {
            $params['set_' . $column] = $value;
        }
        return self::query("UPDATE {$table} SET {$set} WHERE {$where}", $params + $whereParams)->rowCount();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
