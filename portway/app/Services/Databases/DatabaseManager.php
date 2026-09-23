<?php

namespace App\Services\Databases;

use App\Models\Database;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * A native, browser-friendly database manager — the same job as
 * phpMyAdmin (browse, insert, edit, delete rows, run raw SQL, import
 * and export) but scoped to exactly one account's one database, behind
 * DatabasePolicy, in Portway's own UI.
 *
 * Opens a real PDO connection: to the account's SQLite file when
 * running on the "local" provisioner driver, or to the real
 * MySQL/MariaDB schema (as the schema's own restricted database user,
 * never as an admin account) when running on "ssh". Every identifier
 * (table/column name) is validated against a strict allowlist pattern
 * before being interpolated into SQL — only bound parameters ever
 * carry user data.
 */
class DatabaseManager
{
    private ?PDO $pdo = null;

    public function __construct(private Database $database)
    {
    }

    public function connection(): PDO
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        if (config('portway.provisioner') === 'local') {
            $path = Storage::disk('hosting')->path("_databases/{$this->database->name}.sqlite");
            $this->pdo = new PDO('sqlite:'.$path);
        } else {
            $credentials = $this->database->databaseUsers()->first();

            if (! $credentials) {
                throw new RuntimeException('No database user exists for this database yet.');
            }

            $dsn = "mysql:host={$this->database->host};port={$this->database->port};dbname={$this->database->name};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $credentials->username, $credentials->password);
        }

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $this->pdo;
    }

    private function isSqlite(): bool
    {
        return $this->connection()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    public function listTables(): array
    {
        if ($this->isSqlite()) {
            $rows = $this->connection()->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
            )->fetchAll();
        } else {
            $rows = $this->connection()->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
            $rows = array_map(fn ($r) => ['name' => $r[0]], $rows);
        }

        return array_map(fn ($r) => $r['name'], $rows);
    }

    public function tableColumns(string $table): array
    {
        $table = $this->assertValidIdentifier($table);

        if ($this->isSqlite()) {
            $rows = $this->connection()->query("PRAGMA table_info(\"{$table}\")")->fetchAll();

            return array_map(fn ($r) => [
                'name' => $r['name'],
                'type' => $r['type'],
                'nullable' => ! $r['notnull'],
                'primary_key' => (bool) $r['pk'],
                'default' => $r['dflt_value'],
            ], $rows);
        }

        $rows = $this->connection()->query("DESCRIBE `{$table}`")->fetchAll();

        return array_map(fn ($r) => [
            'name' => $r['Field'],
            'type' => $r['Type'],
            'nullable' => $r['Null'] === 'YES',
            'primary_key' => $r['Key'] === 'PRI',
            'default' => $r['Default'],
        ], $rows);
    }

    public function rowCount(string $table): int
    {
        $table = $this->assertValidIdentifier($table);

        return (int) $this->connection()->query("SELECT COUNT(*) AS c FROM \"{$table}\"")->fetch()['c'];
    }

    public function browseRows(string $table, int $page = 1, int $perPage = 25, ?string $search = null): array
    {
        $table = $this->assertValidIdentifier($table);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM \"{$table}\"";
        $bindings = [];

        if ($search !== null && $search !== '') {
            $columns = array_column($this->tableColumns($table), 'name');
            $likeClauses = array_map(fn ($c) => "\"{$c}\" LIKE ?", $columns);
            $sql .= ' WHERE '.implode(' OR ', $likeClauses);
            $bindings = array_fill(0, count($columns), "%{$search}%");
        }

        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        $statement = $this->connection()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function insertRow(string $table, array $data): void
    {
        $table = $this->assertValidIdentifier($table);
        $columns = array_map($this->assertValidIdentifier(...), array_keys($data));

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList = implode(', ', array_map(fn ($c) => "\"{$c}\"", $columns));

        $statement = $this->connection()->prepare("INSERT INTO \"{$table}\" ({$columnList}) VALUES ({$placeholders})");
        $statement->execute(array_values($data));
    }

    public function updateRow(string $table, array $primaryKey, array $data): void
    {
        $table = $this->assertValidIdentifier($table);

        $setClause = implode(', ', array_map(fn ($c) => "\"{$this->assertValidIdentifier($c)}\" = ?", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn ($c) => "\"{$this->assertValidIdentifier($c)}\" = ?", array_keys($primaryKey)));

        $statement = $this->connection()->prepare("UPDATE \"{$table}\" SET {$setClause} WHERE {$whereClause}");
        $statement->execute([...array_values($data), ...array_values($primaryKey)]);
    }

    public function deleteRow(string $table, array $primaryKey): void
    {
        $table = $this->assertValidIdentifier($table);
        $whereClause = implode(' AND ', array_map(fn ($c) => "\"{$this->assertValidIdentifier($c)}\" = ?", array_keys($primaryKey)));

        $statement = $this->connection()->prepare("DELETE FROM \"{$table}\" WHERE {$whereClause}");
        $statement->execute(array_values($primaryKey));
    }

    /**
     * Runs arbitrary SQL exactly like phpMyAdmin's "SQL" tab would —
     * this is the account's own database, so DDL/DML is allowed. The
     * caller must already have passed DatabasePolicy::runQuery.
     */
    public function runQuery(string $sql): array
    {
        $statement = $this->connection()->query($sql);

        if ($statement === false) {
            return ['rows' => [], 'affected' => 0];
        }

        if (str_starts_with(trim(strtoupper($sql)), 'SELECT') || str_starts_with(trim(strtoupper($sql)), 'PRAGMA')) {
            return ['rows' => $statement->fetchAll(), 'affected' => $statement->rowCount()];
        }

        return ['rows' => [], 'affected' => $statement->rowCount()];
    }

    public function exportSql(): string
    {
        $output = "-- Portway export of {$this->database->name}\n-- Generated ".now()->toIso8601String()."\n\n";

        foreach ($this->listTables() as $table) {
            $rows = $this->connection()->query("SELECT * FROM \"{$table}\"")->fetchAll();

            foreach ($rows as $row) {
                $columns = implode(', ', array_map(fn ($c) => "\"{$c}\"", array_keys($row)));
                $values = implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $this->connection()->quote((string) $v), array_values($row)));
                $output .= "INSERT INTO \"{$table}\" ({$columns}) VALUES ({$values});\n";
            }
        }

        return $output;
    }

    public function importSql(string $sql): void
    {
        foreach ($this->splitStatements($sql) as $statement) {
            if (trim($statement) !== '') {
                $this->connection()->exec($statement);
            }
        }
    }

    private function splitStatements(string $sql): array
    {
        return array_filter(array_map('trim', explode(";\n", str_replace(";\r\n", ";\n", $sql))));
    }

    private function assertValidIdentifier(string $identifier): string
    {
        if (! preg_match('/^[A-Za-z0-9_]{1,64}$/', $identifier)) {
            throw new InvalidArgumentException("Invalid identifier: {$identifier}");
        }

        return $identifier;
    }
}
