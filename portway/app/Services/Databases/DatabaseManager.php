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

    /**
     * Quotes an identifier for the connected engine: "name" for SQLite,
     * `name` for MySQL/MariaDB (where "name" would be a string literal).
     */
    private function quoteIdentifier(string $identifier): string
    {
        return $this->isSqlite()
            ? '"'.str_replace('"', '""', $identifier).'"'
            : '`'.str_replace('`', '``', $identifier).'`';
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
            $rows = $this->connection()->query('PRAGMA table_info('.$this->quoteIdentifier($table).')')->fetchAll();

            return array_map(fn ($r) => [
                'name' => $r['name'],
                'type' => $r['type'],
                'nullable' => ! $r['notnull'],
                'primary_key' => (bool) $r['pk'],
                'default' => $r['dflt_value'],
            ], $rows);
        }

        $rows = $this->connection()->query('DESCRIBE '.$this->quoteIdentifier($table))->fetchAll();

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

        return (int) $this->connection()->query('SELECT COUNT(*) AS c FROM '.$this->quoteIdentifier($table))->fetch()['c'];
    }

    public function browseRows(string $table, int $page = 1, int $perPage = 25, ?string $search = null): array
    {
        $table = $this->assertValidIdentifier($table);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT * FROM '.$this->quoteIdentifier($table);
        $bindings = [];

        if ($search !== null && $search !== '') {
            $columns = array_column($this->tableColumns($table), 'name');
            $likeClauses = array_map(fn ($c) => $this->quoteIdentifier($c).' LIKE ?', $columns);
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

        if ($data === []) {
            $this->connection()->exec('INSERT INTO '.$this->quoteIdentifier($table).($this->isSqlite() ? ' DEFAULT VALUES' : ' () VALUES ()'));

            return;
        }

        $columns = array_map($this->assertValidIdentifier(...), array_keys($data));

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList = implode(', ', array_map($this->quoteIdentifier(...), $columns));

        $statement = $this->connection()->prepare('INSERT INTO '.$this->quoteIdentifier($table)." ({$columnList}) VALUES ({$placeholders})");
        $statement->execute(array_values($data));
    }

    public function updateRow(string $table, array $primaryKey, array $data): void
    {
        if ($primaryKey === []) {
            throw new InvalidArgumentException('Rows can only be changed in tables that have a primary key.');
        }

        $table = $this->assertValidIdentifier($table);

        $setClause = implode(', ', array_map(fn ($c) => $this->quoteIdentifier($this->assertValidIdentifier($c)).' = ?', array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn ($c) => $this->quoteIdentifier($this->assertValidIdentifier($c)).' = ?', array_keys($primaryKey)));

        $statement = $this->connection()->prepare('UPDATE '.$this->quoteIdentifier($table)." SET {$setClause} WHERE {$whereClause}");
        $statement->execute([...array_values($data), ...array_values($primaryKey)]);
    }

    public function deleteRow(string $table, array $primaryKey): void
    {
        if ($primaryKey === []) {
            throw new InvalidArgumentException('Rows can only be changed in tables that have a primary key.');
        }

        $table = $this->assertValidIdentifier($table);
        $whereClause = implode(' AND ', array_map(fn ($c) => $this->quoteIdentifier($this->assertValidIdentifier($c)).' = ?', array_keys($primaryKey)));

        $statement = $this->connection()->prepare('DELETE FROM '.$this->quoteIdentifier($table)." WHERE {$whereClause}");
        $statement->execute(array_values($primaryKey));
    }

    /**
     * Runs arbitrary SQL exactly like phpMyAdmin's "SQL" tab would —
     * this is the account's own database, so DDL/DML is allowed. The
     * caller must already have passed DatabasePolicy::runQuery.
     */
    public function runQuery(string $sql): array
    {
        $this->assertStatementAllowed($sql);

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
            $quotedTable = $this->quoteIdentifier($table);

            // Schema first, so the dump can be imported into an empty database.
            if ($this->isSqlite()) {
                $statement = $this->connection()->prepare("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?");
                $statement->execute([$table]);
                $create = $statement->fetchColumn();
            } else {
                $create = $this->connection()->query('SHOW CREATE TABLE '.$quotedTable)->fetch(PDO::FETCH_NUM)[1] ?? null;
            }

            if ($create) {
                $output .= "{$create};\n";
            }

            $rows = $this->connection()->query('SELECT * FROM '.$quotedTable)->fetchAll();

            foreach ($rows as $row) {
                $columns = implode(', ', array_map($this->quoteIdentifier(...), array_keys($row)));
                $values = implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $this->connection()->quote((string) $v), array_values($row)));
                $output .= "INSERT INTO {$quotedTable} ({$columns}) VALUES ({$values});\n";
            }

            $output .= "\n";
        }

        return $output;
    }

    public function importSql(string $sql): void
    {
        $this->assertStatementAllowed($sql);

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

    /**
     * On the "local" driver every account's database is a SQLite file on
     * the panel's own disk, and SQLite can open or write *other* files:
     * ATTACH would expose the panel's database (every account, password
     * hashes, 2FA secrets) and VACUUM INTO / ATTACH can create files
     * anywhere the PHP process can write. Neither has a legitimate use
     * inside a single hosted database, so both are refused outright.
     */
    private function assertStatementAllowed(string $sql): void
    {
        if ($this->isSqlite() && preg_match('/\b(ATTACH|DETACH|VACUUM|load_extension)\b/i', $sql, $matches)) {
            throw new InvalidArgumentException("{$matches[1]} statements are not allowed.");
        }
    }

    private function assertValidIdentifier(string $identifier): string
    {
        if (! preg_match('/^[A-Za-z0-9_]{1,64}$/', $identifier)) {
            throw new InvalidArgumentException("Invalid identifier: {$identifier}");
        }

        return $identifier;
    }
}
