<?php
/**
 * Export Email to Excel
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

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
    
    // Headers per download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xls($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Gestione richiesta export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
    $dateFrom = $_POST['date_from'] ?? '';
    $dateTo = $_POST['date_to'] ?? '';
    $queryType = $_POST['query_type'] ?? '1';
    
    if (empty($dateFrom) || empty($dateTo)) {
        die('Date non valide');
    }
    
    // Formatta date per il nome file (YYYY-MM-DD)
    $dateFromFormatted = date('Y-m-d', strtotime($dateFrom));
    $dateToFormatted = date('Y-m-d', strtotime($dateTo));
    
    // Aggiungi ora per la query
    $dateFromQuery = $dateFrom . ' 00:00:00.000000';
    $dateToQuery = $dateTo . ' 00:00:00.000000';
    
    if ($queryType === '1') {
        $data = executeQuery1($dateFromQuery, $dateToQuery);
        $filename = "ExportEmail_normal_{$dateFromFormatted}-{$dateToFormatted}.xls";
    } else {
        $data = executeQuery2($dateFromQuery, $dateToQuery);
        $filename = "ExportEmail_with_culture_{$dateFromFormatted}-{$dateToFormatted}.xls";
    }
    
    if (isset($data['error'])) {
        die($data['error']);
    }
    
    exportToExcel($data, $filename);
}

