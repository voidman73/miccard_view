<?php
require_once __DIR__ . '/src/output.php';
/**
 * Endpoint JSON per DataTables (server-side processing)
 * Restituisce una pagina di email alla volta
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Auth.php';

use App\Auth;

header('Content-Type: application/json; charset=utf-8');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Sessione scaduta, effettuare di nuovo il login']);
    exit;
}

$dateFrom = $_SESSION['date_from'] ?? null;
$dateTo = $_SESSION['date_to'] ?? null;
// Rilascia il lock di sessione: le richieste successive non restano in coda
session_write_close();

$draw = intval($_POST['draw'] ?? 0);

if (!$dateFrom || !$dateTo) {
    echo json_encode(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
    exit;
}

$queryType = ($_POST['query_type'] ?? '1') === '2' ? '2' : '1';
$start = max(0, intval($_POST['start'] ?? 0));
$length = intval($_POST['length'] ?? 50);
$length = ($length < 1 || $length > 500) ? 50 : $length;
$search = is_string($_POST['search']['value'] ?? null) ? trim($_POST['search']['value']) : '';
$search = mb_substr($search, 0, 100);
$orderDir = $_POST['order'][0]['dir'] ?? 'asc';

$total = countEmails($queryType, $dateFrom, $dateTo);
$filtered = $search === '' ? $total : countEmails($queryType, $dateFrom, $dateTo, $search);
$emails = fetchEmailPage($queryType, $dateFrom, $dateTo, $search, $start, $length, $orderDir);

foreach ([$total, $filtered, $emails] as $res) {
    if (is_array($res) && isset($res['error'])) {
        http_response_code(500);
        echo json_encode(['draw' => $draw, 'error' => $res['error']]);
        exit;
    }
}

$rows = [];
foreach ($emails as $email) {
    $rows[] = [htmlspecialchars($email)];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $filtered,
    'data' => $rows,
]);
