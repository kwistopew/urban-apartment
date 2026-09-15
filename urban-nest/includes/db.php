<?php
date_default_timezone_set('Asia/Manila');

// Load a local .env file when running under XAMPP/local Apache.
// On Vercel, environment variables are supplied by the platform and take precedence.
function urban_nest_load_env_file(string $file): void
{
    if (!is_file($file) || !is_readable($file)) return;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name === '') continue;
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

urban_nest_load_env_file(dirname(__DIR__) . '/.env');

/**
 * PostgreSQL connection for Supabase / Vercel.
 *
 * Supported configuration:
 * 1) DATABASE_URL - Supabase connection string (recommended), or
 * 2) DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD.
 */
class UrbanNestResult
{
    private array $rows;
    private int $position = 0;

    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
    }

    public function fetch_assoc(): ?array
    {
        if ($this->position >= count($this->rows)) return null;
        return $this->rows[$this->position++];
    }

    public function fetch_row(): ?array
    {
        if ($this->position >= count($this->rows)) return null;
        $row = array_values($this->rows[$this->position++]);
        return $row;
    }

    public function data_seek(int $offset): bool
    {
        if ($offset < 0 || $offset > count($this->rows)) return false;
        $this->position = $offset;
        return true;
    }

    public function num_rows(): int
    {
        return count($this->rows);
    }

    public function __get(string $name)
    {
        if ($name === 'num_rows') return count($this->rows);
        return null;
    }
}

class UrbanNestStatement
{
    private PDO $pdo;
    private UrbanNestConnection $connection;
    private string $sql;
    private array $bindings = [];
    private ?UrbanNestResult $result = null;
    public int $affected_rows = 0;
    public int $errno = 0;
    public string $error = '';
    public string|int $insert_id = 0;

    public function __construct(PDO $pdo, UrbanNestConnection $connection, string $sql)
    {
        $this->pdo = $pdo;
        $this->connection = $connection;
        $this->sql = urban_nest_translate_sql($sql);
    }

    public function bind_param(string $types, &...$vars): bool
    {
        $this->bindings = [];
        foreach ($vars as $i => &$var) {
            $this->bindings[$i + 1] = [&$var, $types[$i] ?? 's'];
        }
        return true;
    }

    public function execute(): bool
    {
        try {
            $executeSql = $this->sql;
            $isInsert = (bool)preg_match('/^\s*INSERT\s+INTO\s+/i', $executeSql);
            if ($isInsert && !preg_match('/\bRETURNING\b/i', $executeSql)) {
                $executeSql .= ' RETURNING id';
            }
            $stmt = $this->pdo->prepare($executeSql);
            foreach ($this->bindings as $position => &$binding) {
                [$value, $type] = $binding;
                $pdoType = PDO::PARAM_STR;
                if ($type === 'i') $pdoType = PDO::PARAM_INT;
                elseif ($type === 'b') $pdoType = PDO::PARAM_BOOL;
                elseif ($value === null) $pdoType = PDO::PARAM_NULL;
                $stmt->bindValue($position, $value, $pdoType);
            }
            $ok = $stmt->execute();
            $this->affected_rows = $stmt->rowCount();
            $this->connection->syncLastInsertId();
            $this->insert_id = $this->connection->insert_id;
            $this->result = null;
            if ($isInsert) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows[0]['id'])) {
                    $this->insert_id = $rows[0]['id'];
                    $this->connection->insert_id = $rows[0]['id'];
                }
            } elseif ($this->isResultQuery()) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->result = new UrbanNestResult($rows);
            }
            return $ok;
        } catch (Throwable $e) {
            $this->setError($e);
            return false;
        }
    }

    public function get_result(): UrbanNestResult
    {
        if ($this->result === null) {
            $this->result = new UrbanNestResult();
        }
        return $this->result;
    }

    public function close(): bool
    {
        return true;
    }

    private function isResultQuery(): bool
    {
        return (bool)preg_match('/^\s*(SELECT|WITH|SHOW|EXPLAIN|RETURNING)/i', $this->sql)
            || (bool)preg_match('/\bRETURNING\b/i', $this->sql);
    }

    private function setError(Throwable $e): void
    {
        $this->error = $e->getMessage();
        $this->errno = str_contains($e->getMessage(), '23505') ? 1062 : (int)$e->getCode();
        $this->connection->setError($e);
    }
}

class UrbanNestConnection
{
    private PDO $pdo;
    public string $connect_error = '';
    public string|int $insert_id = 0;
    public int $errno = 0;
    public string $error = '';

    public function __construct(string $dsn, string $user = '', string $password = '')
    {
        try {
            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]);
        } catch (Throwable $e) {
            $this->connect_error = $e->getMessage();
            throw $e;
        }
    }

    public function prepare(string $sql): UrbanNestStatement
    {
        return new UrbanNestStatement($this->pdo, $this, $sql);
    }

    public function query(string $sql): UrbanNestResult|false
    {
        try {
            $sql = urban_nest_translate_sql($sql);
            $stmt = $this->pdo->query($sql);
            $this->syncLastInsertId();
            if ($stmt === false) return false;
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return new UrbanNestResult($rows);
        } catch (Throwable $e) {
            $this->setError($e);
            return false;
        }
    }

    public function begin_transaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->inTransaction() ? $this->pdo->rollBack() : true;
    }

    public function set_charset(string $charset): bool
    {
        return true;
    }

    public function syncLastInsertId(): void
    {
        try {
            $this->insert_id = $this->pdo->lastInsertId();
        } catch (Throwable $e) {
            $this->insert_id = 0;
        }
    }

    public function setError(Throwable $e): void
    {
        $this->error = $e->getMessage();
        $this->errno = str_contains($e->getMessage(), '23505') ? 1062 : (int)$e->getCode();
    }
}

function urban_nest_translate_sql(string $sql): string
{
    // MySQL DATE_ADD() equivalents used by the original project.
    $sql = preg_replace_callback(
        '/DATE_ADD\(\s*([^,]+?)\s*,\s*INTERVAL\s+(\d+)\s+(MONTH|MINUTE|DAY|YEAR)\s*\)/i',
        function ($m) {
            $unit = strtolower($m[3]);
            return '(' . trim($m[1]) . " + INTERVAL '" . $m[2] . ' ' . $unit . "')";
        },
        $sql
    );

    // PostgreSQL has no UTC_TIMESTAMP(); keep comparisons in UTC.
    $sql = preg_replace('/\bUTC_TIMESTAMP\(\)/i', "(CURRENT_TIMESTAMP AT TIME ZONE 'UTC')", $sql);

    // MySQL FIELD(column, 'a','b') ordering -> PostgreSQL CASE ordering.
    $sql = preg_replace_callback(
        '/FIELD\(\s*([^,\)]+?)\s*,\s*([^\)]+)\)/i',
        function ($m) {
            $expr = trim($m[1]);
            $values = array_map('trim', explode(',', $m[2]));
            $parts = [];
            foreach ($values as $i => $value) {
                $parts[] = 'WHEN ' . $expr . ' = ' . $value . ' THEN ' . ($i + 1);
            }
            return '(CASE ' . implode(' ', $parts) . ' ELSE ' . (count($values) + 1) . ' END)';
        },
        $sql
    );

    // MySQL's optional AFTER clause is not supported by PostgreSQL.
    $sql = preg_replace('/\s+AFTER\s+[A-Za-z_][A-Za-z0-9_]*/i', '', $sql);

    return $sql;
}

function urban_nest_connection_from_env(): UrbanNestConnection
{
    $databaseUrl = trim((string)getenv('DATABASE_URL'));
    if ($databaseUrl !== '') {
        $parts = parse_url($databaseUrl);
        if ($parts === false || empty($parts['host'])) {
            throw new RuntimeException('DATABASE_URL is invalid.');
        }
        $host = $parts['host'];
        $port = (int)($parts['port'] ?? 5432);
        $db = ltrim((string)($parts['path'] ?? '/postgres'), '/');
        $user = urldecode((string)($parts['user'] ?? ''));
        $pass = urldecode((string)($parts['pass'] ?? ''));
        $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
        return new UrbanNestConnection($dsn, $user, $pass);
    }

    $host = trim((string)(getenv('DB_HOST') ?: ''));
    $port = (int)(getenv('DB_PORT') ?: 5432);
    $db = trim((string)(getenv('DB_NAME') ?: 'postgres'));
    $user = (string)(getenv('DB_USER') ?: 'postgres');
    $pass = (string)(getenv('DB_PASSWORD') ?: '');
    $sslmode = trim((string)(getenv('DB_SSLMODE') ?: 'require'));

    if ($host === '') {
        throw new RuntimeException('Database is not configured. Set DATABASE_URL or the DB_* environment variables.');
    }

    $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode={$sslmode}";
    return new UrbanNestConnection($dsn, $user, $pass);
}

try {
    $conn = urban_nest_connection_from_env();
} catch (Throwable $e) {
    $message = getenv('APP_ENV') === 'production'
        ? 'Database connection failed. Check the Vercel database environment variables.'
        : 'Database connection failed: ' . $e->getMessage();
    die($message);
}

function next_billing_date(string $date): string {
    $d = new DateTime($date);
    $day = (int)$d->format('j');
    $d->modify('first day of next month');
    $lastDay = (int)$d->format('t');
    $d->setDate((int)$d->format('Y'), (int)$d->format('m'), min($day, $lastDay));
    return $d->format('Y-m-d');
}
