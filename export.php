<?php
require_once __DIR__ . '/src/output.php';
/**
 * Export Email to Excel
 */

session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Auth.php';

use App\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Sessione scaduta, effettuare di nuovo il login';
    exit;
}

/**
 * Esporta array di email in file Excel
 * @param array $data Array di email
 * @param string $filename Nome del file
 */
function exportToExcel($data, $filename) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Intestazione
    $sheet->setCellValue('A1', 'Email');
    $sheet->getStyle('A1')->getFont()->setBold(true);
    $sheet->getStyle('A1')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF1E90FF'); // Azzurro
    $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF'); // Bianco
    
    // Dati
    $row = 2;
    foreach ($data as $email) {
        $sheet->setCellValue('A' . $row, $email);
        $row++;
    }
    
    // Auto-size colonna
    $sheet->getColumnDimension('A')->setAutoSize(true);
    
    // Scrive su file temporaneo per inviare Content-Length: le risposte chunked
    // grandi vengono troncate dalla rete (ERR_INCOMPLETE_CHUNKED_ENCODING)
    $tmp = tempnam(sys_get_temp_dir(), 'xls');
    $writer = new Xls($spreadsheet);
    $writer->save($tmp);

    // Headers per download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: max-age=0');

    readfile($tmp);
    unlink($tmp);
    exit;
}

// Gestione richiesta export
// Le date arrivano dalla sessione (ultima ricerca), mai dal POST:
// l'export corrisponde sempre a quanto mostrato a schermo.
$dateFrom = $_SESSION['date_from'] ?? null;
$dateTo = $_SESSION['date_to'] ?? null;
session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'export') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Richiesta non valida';
    exit;
}

if (!$dateFrom || !$dateTo) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Eseguire prima una ricerca';
    exit;
}

$queryType = ($_POST['query_type'] ?? '1') === '2' ? '2' : '1';
$search = is_string($_POST['search'] ?? null) ? trim($_POST['search']) : '';
$search = mb_substr($search, 0, 100);

// Il formato .xls (BIFF8) tronca in silenzio oltre 65.536 righe (intestazione inclusa):
// conta prima, e rifiuta prima di caricare tutte le righe.
$count = countEmails($queryType, $dateFrom, $dateTo, $search);
if (is_array($count)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $count['error'];
    exit;
}
if ($count > 65535) {
    http_response_code(413);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Troppi risultati per un file Excel (max 65.535). Restringere il periodo o usare la ricerca.';
    exit;
}

$data = fetchAllEmails($queryType, $dateFrom, $dateTo, $search);

if (isset($data['error'])) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $data['error'];
    exit;
}

$prefix = $queryType === '2' ? 'email_newsletter_cultura' : 'email_newsletter';
$suffix = $search !== '' ? '_filtrato' : '';
$filename = "{$prefix}_{$dateFrom}_{$dateTo}{$suffix}.xls";

exportToExcel($data, $filename);
