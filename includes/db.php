<?php
/**
 * includes/db.php — Conexión a Supabase Postgres con capa de compatibilidad mysqli.
 *
 * El resto del proyecto sigue usando $conn->prepare()/bind_param()/get_result()
 * como si fuera mysqli: este shim traduce las llamadas a PDO+pgsql y reescribe
 * las diferencias de SQL entre MySQL y Postgres en runtime.
 *
 * Credenciales: includes/config.php (gitignored).
 */

class LtConn {
    public ?PDO $pdo = null;
    public ?string $connect_error = null;
    public int $insert_id = 0;
    public int $affected_rows = 0;
    public string $error = '';

    public function __construct() {
        $cfgPath = __DIR__ . '/config.php';
        if (!file_exists($cfgPath)) {
            $this->connect_error = 'Falta includes/config.php';
            return;
        }
        $cfg = require $cfgPath;
        $db  = $cfg['db'] ?? null;
        if (!$db) { $this->connect_error = 'config.php sin sección db'; return; }

        $dsn = "pgsql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};sslmode={$db['sslmode']}";
        try {
            $this->pdo = new PDO($dsn, $db['user'], $db['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Throwable $e) {
            $this->connect_error = $e->getMessage();
        }
    }

    /* ──────────── API mysqli emulada ──────────── */

    public function prepare(string $sql) {
        $sql = self::translate($sql);
        if (self::isSwallowedDdl($sql)) {
            return new LtNoopStmt($this);
        }
        try {
            return new LtStmt($this, $sql);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function query(string $sql) {
        $sql = self::translate($sql);
        if (self::isSwallowedDdl($sql)) return true;
        try {
            if (preg_match('/^\s*(SELECT|WITH|SHOW)/i', $sql)) {
                $pst = $this->pdo->query($sql);
                return new LtResult($pst);
            }
            // INSERT/UPDATE/DELETE/etc.
            $this->affected_rows = $this->pdo->exec($sql);
            return true;
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function real_escape_string(string $s): string {
        $q = $this->pdo ? $this->pdo->quote($s) : "'" . str_replace("'", "''", $s) . "'";
        return substr($q, 1, -1);
    }

    public function close(): bool {
        $this->pdo = null;
        return true;
    }

    public function ping(): bool {
        // mysqli reconnect comprueba la conexión; con PDO no es necesario.
        return $this->pdo !== null;
    }

    public function begin_transaction(): bool {
        try { return $this->pdo->beginTransaction(); }
        catch (Throwable $e) { $this->error = $e->getMessage(); return false; }
    }

    public function commit(): bool {
        try { return $this->pdo->commit(); }
        catch (Throwable $e) { $this->error = $e->getMessage(); return false; }
    }

    public function rollback(): bool {
        try { return $this->pdo->rollBack(); }
        catch (Throwable $e) { $this->error = $e->getMessage(); return false; }
    }

    public function setInsertId(int $id): void {
        $this->insert_id = $id;
    }

    /* ──────────── Detección y traducción SQL ──────────── */

    /**
     * Devuelve true para CREATE TABLE / ALTER TABLE: el código heredado
     * los lanza para auto-migrar en MySQL, pero las tablas en Supabase
     * ya están creadas y la sintaxis MySQL (TINYINT, ENUM, AUTO_INCREMENT,
     * IF NOT EXISTS para ADD COLUMN) reventaría en Postgres. Los tragamos.
     */
    private static function isSwallowedDdl(string $sql): bool {
        return (bool)preg_match('/^\s*(CREATE\s+TABLE|ALTER\s+TABLE)\b/i', $sql);
    }

    /**
     * Reescribe SQL de MySQL a Postgres preservando ? como placeholder.
     */
    public static function translate(string $sql): string {
        // Identificadores entre backticks → double quotes (case-sensitive en pg pero
        // las columnas en este esquema están en minúsculas, así que el match funciona).
        $sql = str_replace('`', '"', $sql);

        // INSERT IGNORE INTO foo (...) VALUES (...)  →  INSERT INTO foo (...) VALUES (...) ON CONFLICT DO NOTHING
        if (preg_match('/^\s*INSERT\s+IGNORE\s+INTO\b/i', $sql)) {
            $sql = preg_replace('/^\s*INSERT\s+IGNORE\s+INTO\b/i', 'INSERT INTO', $sql);
            // Solo añadir si no tiene ya un ON CONFLICT
            if (!preg_match('/\bON\s+CONFLICT\b/i', $sql)) {
                $sql = rtrim(rtrim($sql), ';') . ' ON CONFLICT DO NOTHING';
            }
        }

        // LIMIT offset, count  →  LIMIT count OFFSET offset
        $sql = preg_replace('/\bLIMIT\s+(\d+)\s*,\s*(\d+)\b/i', 'LIMIT $2 OFFSET $1', $sql);

        // DATE_SUB(CURDATE(), INTERVAL N UNIT)  →  (CURRENT_DATE - INTERVAL 'N UNITs')
        $sql = preg_replace_callback(
            '/DATE_SUB\s*\(\s*CURDATE\s*\(\s*\)\s*,\s*INTERVAL\s+(\d+)\s+(\w+)\s*\)/i',
            function ($m) {
                $unit = strtolower($m[2]);
                if (substr($unit, -1) !== 's') $unit .= 's'; // MONTH → months, DAY → days
                return "(CURRENT_DATE - INTERVAL '{$m[1]} {$unit}')";
            },
            $sql
        );

        // CURDATE() → CURRENT_DATE  (por si aparece suelto)
        $sql = preg_replace('/\bCURDATE\s*\(\s*\)/i', 'CURRENT_DATE', $sql);

        // IFNULL(x, y) → COALESCE(x, y)
        $sql = preg_replace('/\bIFNULL\s*\(/i', 'COALESCE(', $sql);

        // FIELD(col, v1, v2, v3, ...) → array_position(ARRAY[v1,v2,v3,...]::text[], col::text)
        // (usado en ORDER BY FIELD(...) para ordenar por enum)
        $sql = preg_replace_callback(
            '/\bFIELD\s*\(\s*([^,]+?)\s*,\s*((?:[^()]|\([^()]*\))+)\)/i',
            function ($m) {
                return "array_position(ARRAY[" . $m[2] . "]::text[], (" . $m[1] . ")::text)";
            },
            $sql
        );

        return $sql;
    }
}

class LtStmt {
    private LtConn $conn;
    private ?PDOStatement $pst = null;
    private array $bound = [];
    private string $sql;
    private bool $isInsertReturning = false;
    public int $affected_rows = 0;
    public int $insert_id = 0;
    public string $error = '';

    public function __construct(LtConn $conn, string $sql) {
        $this->conn = $conn;
        // INSERT sin RETURNING → añadir RETURNING id para emular insert_id
        if (preg_match('/^\s*INSERT\b/i', $sql) && !preg_match('/\bRETURNING\b/i', $sql)) {
            // No funciona con ON CONFLICT DO NOTHING si hay conflicto (no devuelve fila),
            // pero entonces insert_id queda en 0 — comportamiento equivalente a INSERT IGNORE.
            $sql = rtrim(rtrim($sql), ';') . ' RETURNING id';
            $this->isInsertReturning = true;
        }
        $this->sql = $sql;
        $this->pst = $conn->pdo->prepare($sql);
    }

    public function bind_param(string $types, ...$args): bool {
        // mysqli técnicamente exige refs, pero el código de este proyecto siempre
        // hace bind+execute sin modificar las variables intermedias, y además
        // pasa expresiones como (int)$x que NO son referenciables. Aceptamos por valor.
        $this->bound = $args;
        return true;
    }

    public function execute(?array $params = null): bool {
        try {
            $vals = $params ?? array_map(fn($v) => $v, $this->bound);
            // Coerciones defensivas: '' a NULL para evitar "invalid input syntax for integer"
            // cuando se reciben campos opcionales vacíos.
            foreach ($vals as $k => $v) {
                if ($v === '') $vals[$k] = null;
            }
            $this->pst->execute($vals);
            $this->affected_rows = $this->pst->rowCount();
            if ($this->isInsertReturning) {
                $row = $this->pst->fetch(PDO::FETCH_NUM);
                $this->insert_id = $row ? (int)$row[0] : 0;
                $this->conn->setInsertId($this->insert_id);
                $this->conn->affected_rows = $this->affected_rows;
            } else {
                $this->conn->affected_rows = $this->affected_rows;
            }
            return true;
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            $this->conn->error = $e->getMessage();
            return false;
        }
    }

    public function get_result() {
        return new LtResult($this->pst);
    }

    public function store_result(): bool {
        // mysqli buffea resultados al cliente; PDO ya los tiene tras execute(). No-op.
        return true;
    }

    public function close(): bool {
        $this->pst = null;
        return true;
    }
}

/**
 * Statement no-op para tragar CREATE TABLE / ALTER TABLE sin reventar.
 */
class LtNoopStmt {
    public int $affected_rows = 0;
    public int $insert_id = 0;
    public string $error = '';
    public function __construct(LtConn $conn) {}
    public function bind_param(string $types, &...$args): bool { return true; }
    public function execute(?array $params = null): bool { return true; }
    public function get_result() { return new LtResultEmpty(); }
    public function close(): bool { return true; }
}

class LtResult {
    private array $rows;
    public int $num_rows = 0;

    public function __construct(?PDOStatement $pst) {
        $this->rows = $pst ? $pst->fetchAll(PDO::FETCH_ASSOC) : [];
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc(): ?array {
        return array_shift($this->rows) ?: null;
    }

    public function fetch_row(): ?array {
        $row = array_shift($this->rows);
        return $row ? array_values($row) : null;
    }

    public function fetch_array(int $mode = MYSQLI_BOTH): ?array {
        $row = array_shift($this->rows);
        if (!$row) return null;
        if ($mode === MYSQLI_ASSOC) return $row;
        if ($mode === MYSQLI_NUM)   return array_values($row);
        return array_merge(array_values($row), $row);
    }

    public function fetch_all(int $mode = MYSQLI_ASSOC): array {
        $out = $this->rows;
        $this->rows = [];
        if ($mode === MYSQLI_NUM) return array_map('array_values', $out);
        return $out;
    }

    public function free(): void { $this->rows = []; }
    public function free_result(): void { $this->rows = []; }
}

class LtResultEmpty extends LtResult {
    public function __construct() { parent::__construct(null); }
}

/* ──────────── Conexión global $conn (compat con código existente) ──────────── */

$conn = new LtConn();
if ($conn->connect_error) {
    error_log("DB connection error: " . $conn->connect_error);
    http_response_code(500);
    die(json_encode(['ok' => false, 'message' => 'Error de servidor. Inténtalo más tarde.']));
}
