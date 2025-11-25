<?php
/**
 * Interfaccia Web per Interrogazione Database Store
 * Sistema di query e export email clienti
 */

session_start();
require_once 'config.php';
require_once 'src/Auth.php';

use App\Auth;

$auth = new Auth();

// Logout handler
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: login.php');
    exit;
}

// Check authentication
if (!$auth->check()) {
    header('Location: login.php');
    exit;
}

// Costanti paginazione

// Costanti paginazione
define('RECORDS_PER_PAGE', 50);

// Gestione paginazione
$page1 = isset($_GET['page1']) ? max(1, intval($_GET['page1'])) : 1;
$page2 = isset($_GET['page2']) ? max(1, intval($_GET['page2'])) : 1;

// Data odierna come default
$today = date('Y-m-d');
$dateFrom = $_POST['date_from'] ?? ($_SESSION['date_from'] ?? $today);
$dateTo = $_POST['date_to'] ?? ($_SESSION['date_to'] ?? $today);
$query1Data = [];
$query2Data = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reset') {
        // Reset: pulisci sessione e resetta variabili
        unset($_SESSION['query1_data']);
        unset($_SESSION['query2_data']);
        unset($_SESSION['date_from']);
        unset($_SESSION['date_to']);
        $query1Data = [];
        $query2Data = [];
        $page1 = 1;
        $page2 = 1;
        // Mantieni le date di default (oggi)
        $dateFrom = $today;
        $dateTo = $today;
    } elseif ($_POST['action'] === 'query') {
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        
        if (empty($dateFrom) || empty($dateTo)) {
            $error = 'Inserire entrambe le date';
        } else {
            // Validazione date
            $dateFromObj = DateTime::createFromFormat('Y-m-d', $dateFrom);
            $dateToObj = DateTime::createFromFormat('Y-m-d', $dateTo);
            
            if (!$dateFromObj || !$dateToObj) {
                $error = 'Formato date non valido';
            } elseif ($dateFromObj > $dateToObj) {
                $error = 'La data di inizio deve essere precedente alla data di fine';
            } else {
                // Salva date in sessione
                $_SESSION['date_from'] = $dateFrom;
                $_SESSION['date_to'] = $dateTo;
                
                // Aggiungi ora per la query
                $dateFromQuery = $dateFrom . ' 00:00:00.000000';
                $dateToQuery = $dateTo . ' 00:00:00.000000';
                
                $query1Data = executeQuery1($dateFromQuery, $dateToQuery);
                $query2Data = executeQuery2($dateFromQuery, $dateToQuery);
                
                // Salva risultati in sessione
                $_SESSION['query1_data'] = $query1Data;
                $_SESSION['query2_data'] = $query2Data;
                
                // Reset pagine quando si esegue nuova query
                $page1 = 1;
                $page2 = 1;
                
                if (isset($query1Data['error'])) {
                    $error = $query1Data['error'];
                } elseif (isset($query2Data['error'])) {
                    $error = $query2Data['error'];
                }
            }
        }
    }
} else {
    // Carica dati dalla sessione se disponibili
    if (isset($_SESSION['query1_data'])) {
        $query1Data = $_SESSION['query1_data'];
    }
    if (isset($_SESSION['query2_data'])) {
        $query2Data = $_SESSION['query2_data'];
    }
}

/**
 * Funzione helper per paginare array
 */
function paginateArray($data, $page, $perPage) {
    if (isset($data['error'])) {
        return ['data' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
    }
    
    $total = count($data);
    $pages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    $paginatedData = array_slice($data, $offset, $perPage);
    
    return [
        'data' => $paginatedData,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page,
        'offset' => $offset
    ];
}

// Per DataTables carichiamo tutti i dati (non paginati lato server)
// Manteniamo la funzione per compatibilità ma non la usiamo per la visualizzazione
$paged1 = paginateArray($query1Data, $page1, RECORDS_PER_PAGE);
$paged2 = paginateArray($query2Data, $page2, RECORDS_PER_PAGE);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query Database Store MiCCard- Email Export</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Query Database Store</h1>
            <p class="subtitle">Interrogazione email clienti con consenso newsletter</p>
            <div style="text-align: right; margin-top: -30px;">
                <span style="margin-right: 10px;">Utente: <strong><?php echo htmlspecialchars($auth->user()); ?></strong></span>
                <a href="?logout=1" class="btn btn-reset" style="text-decoration: none; font-size: 0.9rem; padding: 5px 10px;">Esci</a>
            </div>
        </header>

        <div class="card">
            <h2>Selezione Periodo</h2>
            <form method="POST" action="" id="queryForm">
                <input type="hidden" name="action" value="query">
                <div class="form-group">
                    <label for="date_from">Data Inizio:</label>
                    <input type="text" id="date_from" name="date_from" class="date-picker" 
                           value="<?php echo htmlspecialchars($dateFrom); ?>" required>
                </div>
                <div class="form-group">
                    <label for="date_to">Data Fine:</label>
                    <input type="text" id="date_to" name="date_to" class="date-picker" 
                           value="<?php echo htmlspecialchars($dateTo); ?>" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Esegui Query</button>
                    <button type="submit" formnovalidate name="action" value="reset" class="btn btn-reset">Reset</button>
                </div>
            </form>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>Errore:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ((!empty($query1Data) && !isset($query1Data['error'])) || (!empty($query2Data) && !isset($query2Data['error']))): ?>
        <div class="tables-wrapper">
            <?php if (!empty($query1Data) && !isset($query1Data['error'])): ?>
            <div class="card table-card">
                <div class="table-header">
                    <h2>Query 1: Newsletter Consent</h2>
                    <form method="POST" action="export.php" style="display: inline-block;">
                        <input type="hidden" name="action" value="export">
                        <input type="hidden" name="query_type" value="1">
                        <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                        <button type="submit" class="btn btn-export">📥 Esporta Excel</button>
                    </form>
                </div>
                <div class="pagination-info">
                    <span class="badge"><?php echo count($query1Data); ?> risultati totali</span>
                </div>
                <div class="table-container">
                    <table id="table1" class="data-table table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($query1Data) > 0): ?>
                                <?php foreach ($query1Data as $index => $email): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($email); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="no-data">Nessun risultato trovato</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($query2Data) && !isset($query2Data['error'])): ?>
            <div class="card table-card">
                <div class="table-header">
                    <h2>Query 2: Newsletter + Cultural</h2>
                    <form method="POST" action="export.php" style="display: inline-block;">
                        <input type="hidden" name="action" value="export">
                        <input type="hidden" name="query_type" value="2">
                        <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                        <button type="submit" class="btn btn-export">📥 Esporta Excel</button>
                    </form>
                </div>
                <div class="pagination-info">
                    <span class="badge"><?php echo count($query2Data); ?> risultati totali</span>
                </div>
                <div class="table-container">
                    <table id="table2" class="data-table table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($query2Data) > 0): ?>
                                <?php foreach ($query2Data as $index => $email): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($email); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="no-data">Nessun risultato trovato</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        // Inizializza date picker
        flatpickr("#date_from", {
            dateFormat: "Y-m-d",
            locale: "it",
            maxDate: new Date(),
            defaultDate: "<?php echo htmlspecialchars($dateFrom); ?>"
        });
        
        flatpickr("#date_to", {
            dateFormat: "Y-m-d",
            locale: "it",
            maxDate: new Date(),
            defaultDate: "<?php echo htmlspecialchars($dateTo); ?>"
        });

        // Inizializza DataTables
        <?php if (!empty($query1Data) && !isset($query1Data['error']) && count($query1Data) > 0): ?>
        $(document).ready(function() {
            $('#table1').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json'
                },
                pageLength: 50,
                lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "Tutti"]],
                order: [[0, 'asc']],
                responsive: true,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                columnDefs: [
                    { orderable: true, targets: [0, 1] },
                    { searchable: true, targets: [1] }
                ]
            });
        });
        <?php endif; ?>

        <?php if (!empty($query2Data) && !isset($query2Data['error']) && count($query2Data) > 0): ?>
        $(document).ready(function() {
            $('#table2').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json'
                },
                pageLength: 50,
                lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "Tutti"]],
                order: [[0, 'asc']],
                responsive: true,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                columnDefs: [
                    { orderable: true, targets: [0, 1] },
                    { searchable: true, targets: [1] }
                ]
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>

