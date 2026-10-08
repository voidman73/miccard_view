<?php
require_once __DIR__ . '/src/output.php';
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

// Data odierna come default
$today = date('Y-m-d');
$dateFrom = $_POST['date_from'] ?? ($_SESSION['date_from'] ?? $today);
$dateTo = $_POST['date_to'] ?? ($_SESSION['date_to'] ?? $today);
$error = null;

// Pulisci eventuali risultati completi salvati da versioni precedenti
unset($_SESSION['query1_data'], $_SESSION['query2_data']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reset') {
        // Reset: pulisci sessione e torna alle date di default (oggi)
        unset($_SESSION['date_from'], $_SESSION['date_to'], $_SESSION['query1_count'], $_SESSION['query2_count']);
        $dateFrom = $today;
        $dateTo = $today;
    } elseif ($_POST['action'] === 'query') {
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        unset($_SESSION['query1_count'], $_SESSION['query2_count']);

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
                // I dati vengono caricati a pagine da data.php: qui salviamo solo date e conteggi
                $count1 = countEmails('1', $dateFrom, $dateTo);
                $count2 = countEmails('2', $dateFrom, $dateTo);

                if (is_array($count1)) {
                    $error = $count1['error'];
                } elseif (is_array($count2)) {
                    $error = $count2['error'];
                } else {
                    $_SESSION['date_from'] = $dateFrom;
                    $_SESSION['date_to'] = $dateTo;
                    $_SESSION['query1_count'] = $count1;
                    $_SESSION['query2_count'] = $count2;
                }
            }
        }
    }
}

$query1Count = $_SESSION['query1_count'] ?? null;
$query2Count = $_SESSION['query2_count'] ?? null;
$hasResults = $query1Count !== null && $query2Count !== null;

/** Formato numerico italiano: 1.624 */
function fmtCount($n) {
    return number_format((int) $n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esportazione email MiC Card</title>
    <link rel="stylesheet" href="assets/vendor/jquery.dataTables-1.13.7.min.css">
    <link rel="stylesheet" href="assets/vendor/flatpickr-4.6.13.min.css">
    <link rel="stylesheet" href="assets/vendor/flatpickr-4.6.13-dark.css" media="(prefers-color-scheme: dark)">
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="app-bar">
        <h1 class="app-title">Esportazione email MiC Card</h1>
        <span>Utente: <strong><?php echo htmlspecialchars($auth->user()); ?></strong> · <a href="?logout=1">Esci</a></span>
    </header>

    <main class="page">
        <section class="panel" aria-labelledby="searchTitle">
            <h2 id="searchTitle">Periodo di registrazione</h2>
            <form method="POST" action="" id="queryForm">
                <input type="hidden" name="action" value="query">
                <div class="form-row">
                    <div class="field">
                        <label for="date_from">Registrati dal</label>
                        <input type="text" id="date_from" name="date_from" autocomplete="off" aria-describedby="dateHint"
                               value="<?php echo htmlspecialchars($dateFrom); ?>" required>
                    </div>
                    <div class="field">
                        <label for="date_to">al (incluso)</label>
                        <input type="text" id="date_to" name="date_to" autocomplete="off" aria-describedby="dateHint"
                               value="<?php echo htmlspecialchars($dateTo); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary" id="searchBtn">Cerca</button>
                    <button type="submit" class="btn btn-secondary" formnovalidate name="action" value="reset">Azzera</button>
                </div>
                <p class="hint" id="dateHint">Data in cui il cliente si è registrato (gg/mm/aaaa). Entrambe le date sono incluse.</p>
            </form>
        </section>

        <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!$hasResults && !$error): ?>
        <p class="empty-state">Scegli un periodo e premi Cerca.</p>
        <?php endif; ?>

        <?php if ($hasResults): ?>
        <section class="panel" aria-labelledby="resultsTitle">
            <h2 id="resultsTitle">Risultati</h2>
            <div class="results-head">
                <fieldset class="choice">
                    <legend>Consenso</legend>
                    <label><input type="radio" name="consent" value="1" checked> Solo newsletter <span class="count"><?php echo htmlspecialchars(fmtCount($query1Count)); ?></span></label>
                    <label><input type="radio" name="consent" value="2"> Newsletter + iniziative culturali <span class="count"><?php echo htmlspecialchars(fmtCount($query2Count)); ?></span></label>
                </fieldset>
                <form method="POST" action="export.php" id="exportForm">
                    <input type="hidden" name="action" value="export">
                    <input type="hidden" name="query_type" value="1">
                    <input type="hidden" name="search" value="">
                    <button type="submit" class="btn btn-primary" id="exportBtn"<?php echo (int) $query1Count === 0 ? ' disabled' : ''; ?>>
                        <span id="exportIdle">Scarica <span id="exportCount"><?php echo htmlspecialchars(fmtCount($query1Count)); ?></span> email (.xls)</span>
                        <span id="exportBusy" hidden>Preparazione file…</span>
                    </button>
                </form>
            </div>
            <div class="alert alert-error" role="alert" id="resultsError"></div>
            <table id="emails" aria-labelledby="resultsTitle">
                <thead><tr><th scope="col">Email</th></tr></thead>
                <tbody></tbody>
            </table>
        </section>
        <?php endif; ?>
    </main>

    <script src="assets/vendor/jquery-3.7.0.min.js"></script>
    <script src="assets/vendor/jquery.dataTables-1.13.7.min.js"></script>
    <script src="assets/vendor/flatpickr-4.6.13.min.js"></script>
    <script src="assets/vendor/flatpickr-4.6.13-it.js"></script>
    <script>
        // Date: digitabili (gg/mm/aaaa) o scelte dal calendario; il server riceve aaaa-mm-gg
        ['date_from', 'date_to'].forEach(function (id) {
            const fp = flatpickr('#' + id, {
                locale: 'it', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y',
                allowInput: true, maxDate: 'today'
            });
            // L'etichetta deve puntare al campo visibile, non a quello nascosto
            if (fp.altInput) {
                fp.input.removeAttribute('id');
                fp.altInput.id = id;
                fp.altInput.setAttribute('aria-describedby', 'dateHint');
                fp.altInput.setAttribute('autocomplete', 'off');
            }
        });

        document.getElementById('queryForm').addEventListener('submit', function (e) {
            const btn = document.getElementById('searchBtn');
            if (e.submitter && e.submitter !== btn) return;
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.textContent = 'Ricerca in corso…';
        });
        // Ritorno con "Indietro" (bfcache): il pulsante non deve restare bloccato
        window.addEventListener('pageshow', function (e) {
            if (!e.persisted) return;
            const btn = document.getElementById('searchBtn');
            btn.disabled = false;
            btn.removeAttribute('aria-busy');
            btn.textContent = 'Cerca';
        });

        <?php if ($hasResults): ?>
        $.fn.dataTable.ext.errMode = 'none';
        const consent = () => $('input[name=consent]:checked').val();
        const errorBox = document.getElementById('resultsError');
        const clearError = () => { errorBox.textContent = ''; };
        // Svuota e poi riscrive: anche un messaggio ripetuto viene riletto dagli screen reader
        const showError = (msg) => { clearError(); requestAnimationFrame(() => { errorBox.textContent = msg; }); };
        const exportBtn = document.getElementById('exportBtn');
        let filteredCount = <?php echo (int) $query1Count; ?>;
        let loading = false;
        let exporting = false;
        const syncExportBtn = () => { exportBtn.disabled = loading || exporting || filteredCount === 0; };

        const table = $('#emails').DataTable({
            serverSide: true, processing: true, searchDelay: 400,
            ajax: { url: 'data.php', type: 'POST', data: d => { d.query_type = consent(); } },
            pageLength: 50, lengthMenu: [25, 50, 100, 500],
            order: [[0, 'asc']],
            language: { url: 'assets/vendor/datatables-it-IT-1.13.7.json',
                        emptyTable: 'Nessuna email per questo periodo.',
                        processing: 'Caricamento…' }
        });

        // Durante il caricamento il conteggio non è ancora quello giusto: niente export
        table.on('preXhr.dt', () => { loading = true; syncExportBtn(); });
        table.on('xhr.dt', (e, s, json, xhr) => {
            if (xhr && xhr.status === 401) { location = 'login.php'; return true; }
            if (json && json.draw != s.iDraw) return; // risposta superata da una più recente
            loading = false;
            if (json) {
                clearError();
                filteredCount = json.recordsFiltered;
                $('#exportCount').text(filteredCount.toLocaleString('it-IT'));
            }
            syncExportBtn();
        });
        table.on('error.dt', () => {
            showError('Impossibile caricare le email. Riprovare o contattare l\'assistenza.');
        });
        $('input[name=consent]').on('change', () => table.ajax.reload());

        // Export via fetch: un errore (es. troppe righe) appare qui invece di sostituire la pagina
        document.getElementById('exportForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            if (exportBtn.getAttribute('aria-busy') === 'true') return;
            this.query_type.value = consent();
            this.search.value = table.search();
            const body = new FormData(this);

            exporting = true;
            syncExportBtn();
            exportBtn.setAttribute('aria-busy', 'true');
            document.getElementById('exportIdle').hidden = true;
            document.getElementById('exportBusy').hidden = false;
            clearError();

            try {
                const res = await fetch(this.getAttribute('action'), { method: 'POST', body: body, credentials: 'same-origin' });
                if (res.status === 401) { location = 'login.php'; return; }
                if (!res.ok) {
                    const type = res.headers.get('Content-Type') || '';
                    const text = type.startsWith('text/plain') ? (await res.text()).trim() : '';
                    showError(text || 'Impossibile preparare il file. Riprovare o contattare l\'assistenza.');
                    return;
                }
                const blob = await res.blob();
                const cd = res.headers.get('Content-Disposition') || '';
                const m = cd.match(/filename="?([^";]+)"?/i);
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = m ? m[1] : 'email.xls';
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            } catch (err) {
                showError('Impossibile scaricare il file. Controllare la connessione e riprovare.');
            } finally {
                exporting = false;
                exportBtn.removeAttribute('aria-busy');
                syncExportBtn();
                document.getElementById('exportIdle').hidden = false;
                document.getElementById('exportBusy').hidden = true;
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
