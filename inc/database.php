<?php
declare(strict_types=1);
/** PDO-Kern mit gepufferten Ergebnissen für die bestehenden Vereinsmodule.
 * Alle Abfragen werden über PDO ausgeführt. Neue Funktionen sollten db_query($sql, $params)
 * verwenden; db_escape() ist nur für die noch vorhandenen historischen Query-Builder da.
 */

final class VSResult
{
    private array $rows;
    private int $position = 0;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function count(): int { return count($this->rows); }
    public function assoc(): array|false { return $this->rows[$this->position++] ?? false; }
    public function row(): array|false {
        $r = $this->assoc();
        return $r === false ? false : array_values($r);
    }
    public function seek(int $n): bool {
        if ($n < 0 || $n >= count($this->rows)) return false;
        $this->position = $n;
        return true;
    }
}

function db_pdo(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    if (!defined('VS_INTERNAL')) define('VS_INTERNAL', true);
    $configFile = __DIR__ . '/config.php';
    if (!is_file($configFile)) throw new RuntimeException('Noch nicht installiert: install.php aufrufen.');
    $c = require $configFile;
    if (!is_array($c)) throw new RuntimeException('Ungültige Datenbankkonfiguration.');
    $port = (int) ($c['port'] ?? 3306);
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $port, $c['database']);
    $pdo = new PDO($dsn, (string) $c['username'], (string) $c['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ]);
    // Kompatibilität der vorhandenen Query-Builder aus 2013: fehlende Spaltenwerte
    // und historische GROUP BY-Ausdrücke. Dies betrifft NUR diese Verbindung.
    // Langfristig sollten die alten INSERT-/GROUP BY-Statements modernisiert werden.
    $pdo->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
    return $pdo;
}

function db_query(string $sql, array $params = []): VSResult|bool
{
    $GLOBALS['vs_db_error'] = '';
    $GLOBALS['vs_db_errno'] = 0;
    try {
        $stmt = db_pdo()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->columnCount() > 0) return new VSResult($stmt->fetchAll(PDO::FETCH_ASSOC));
        return true;
    } catch (PDOException $e) {
        $GLOBALS['vs_db_error'] = $e->getMessage();
        $GLOBALS['vs_db_errno'] = (int) ($e->errorInfo[1] ?? 0);
        error_log('[Vereinssystem/PDO] SQL fehlgeschlagen: ' . $e->getMessage());
        return false;
    }
}

function db_fetch_assoc(mixed $result): array|false { return $result instanceof VSResult ? $result->assoc() : false; }
function db_fetch_row(mixed $result): array|false { return $result instanceof VSResult ? $result->row() : false; }
function db_num_rows(mixed $result): int { return $result instanceof VSResult ? $result->count() : 0; }
function db_data_seek(mixed $result, int $offset): bool { return $result instanceof VSResult && $result->seek($offset); }
function db_insert_id(): int { return (int) db_pdo()->lastInsertId(); }
function db_error(): string { return (string) ($GLOBALS['vs_db_error'] ?? ''); }
function db_errno(): int { return (int) ($GLOBALS['vs_db_errno'] ?? 0); }
function db_escape(mixed $value): string
{
    $quoted = db_pdo()->quote((string) $value);
    return substr($quoted, 1, -1);
}

function vs_strftime(string $format, int|string|null $timestamp = null): string
{
    // Deutsches Datumsformat auch ohne das ab PHP 8.1 veraltete strftime().
    $time = is_numeric($timestamp) ? (int) $timestamp : (($timestamp !== null && $timestamp !== '') ? (strtotime((string)$timestamp) ?: time()) : time());
    $months = [1=>'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    $days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
    $m = [
        '%A'=>$days[(int)date('w', $time)], '%a'=>substr($days[(int)date('w', $time)], 0, 2),
        '%B'=>$months[(int)date('n', $time)], '%b'=>substr($months[(int)date('n', $time)], 0, 3),
        '%d'=>date('d',$time), '%m'=>date('m',$time), '%Y'=>date('Y',$time),
        '%y'=>date('y',$time), '%H'=>date('H',$time), '%M'=>date('i',$time),
        '%S'=>date('s',$time), '%%'=>'%',
    ];
    return preg_replace_callback('/%[A-Za-z%]/', static fn(array $hit) => $m[$hit[0]] ?? $hit[0], $format);
}

/** Kurzformular statt zustandsändernder GET-Links (Dienste). */
function vs_action_form(array $values, string $caption): string
{
    $html = '<form class="vs-inline-action" method="post" action="dienste_process.php">';
    $values['vs_csrf'] = (string)($_SESSION['vs_csrf'] ?? '');
    foreach ($values as $key=>$value) {
        $html .= '<input type="hidden" name="'.htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'">';
    }
    return $html.'<button type="submit">'.htmlspecialchars($caption,ENT_QUOTES,'UTF-8').'</button></form>';
}

/** Ersatz für das in PHP 8.1 veraltete FILTER_SANITIZE_STRING. */
function vs_sanitize_text(mixed $value): string
{
    $str = strip_tags((string)$value);
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $str) ?? '';
}