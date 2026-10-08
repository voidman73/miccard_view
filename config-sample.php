<?php
/**
 * Configurazione Database
 * Solo lettura - nessuna modifica ai dati
 */

define('DB_HOST', 'localhost');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Configurazione Active Directory
 */
define('AD_HOST', ''); // IP o Hostname del Domain Controller
define('AD_BASE_DN', 'dc=xxx,dc=xx');
define('AD_ACCOUNT_SUFFIX', '@xxx.xx');
define('AD_USE_SSL', false);
define('AD_USE_TLS', false);
define('AD_PORT', 389);
// Opzionale: Utente di servizio per il binding (se anonimo non permesso)
define('AD_ADMIN_USERNAME', 'xxxxx@xxxx.xx');
define('AD_ADMIN_PASSWORD', 'xxxxx');


/**
 * Messaggio generico mostrato all'utente in caso di errore query
 * (il dettaglio tecnico va solo nel log)
 */
define('DB_ERROR_MESSAGE', 'Impossibile caricare i dati. Riprovare o contattare l\'assistenza.');

/**
 * Connessione al database MySQL
 * @return mysqli|null
 */
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                error_log("Errore connessione database: " . $conn->connect_error);
                return null;
            }
            
            $conn->set_charset(DB_CHARSET);
            
            // Imposta modalità sola lettura (se supportato)
            $conn->query("SET SESSION TRANSACTION READ ONLY");
            
        } catch (Exception $e) {
            error_log("Eccezione connessione database: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

/**
 * Costruisce la clausola WHERE comune alle query email
 * Date in formato Y-m-d, entrambe incluse
 * @param string $queryType '1' = newsletter, '2' = newsletter + cultural
 * @return array [sql, types, params]
 */
function buildEmailWhere($queryType, $dateFrom, $dateTo, $search = '') {
    $sql = "creation_date >= ? AND creation_date < DATE_ADD(?, INTERVAL 1 DAY) AND newsletter_consent=1";
    if ($queryType === '2') {
        $sql .= " AND cultural_consent=1";
    }
    $types = "ss";
    $params = [$dateFrom, $dateTo];

    if ($search !== '') {
        $sql .= " AND email LIKE ?";
        $types .= "s";
        $params[] = '%' . addcslashes($search, '%_\\') . '%';
    }

    return [$sql, $types, $params];
}

/**
 * Conta le email per tipo query
 * @return int|array Numero di record o ['error' => ...]
 */
function countEmails($queryType, $dateFrom, $dateTo, $search = '') {
    $conn = getDbConnection();
    if (!$conn) {
        return ['error' => DB_ERROR_MESSAGE];
    }

    [$where, $types, $params] = buildEmailWhere($queryType, $dateFrom, $dateTo, $search);
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM `store`.`cliente` WHERE $where");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $count = (int)$stmt->get_result()->fetch_assoc()['n'];
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        return ['error' => DB_ERROR_MESSAGE];
    }
    return $count;
}

/**
 * Restituisce una pagina di email per tipo query
 * @return array Lista email o ['error' => ...]
 */
function fetchEmailPage($queryType, $dateFrom, $dateTo, $search, $offset, $limit, $orderDir = 'ASC') {
    $conn = getDbConnection();
    if (!$conn) {
        return ['error' => DB_ERROR_MESSAGE];
    }

    $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
    [$where, $types, $params] = buildEmailWhere($queryType, $dateFrom, $dateTo, $search);
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;

    try {
        $stmt = $conn->prepare("SELECT LCASE(email) AS email FROM `store`.`cliente` WHERE $where ORDER BY email $orderDir LIMIT ? OFFSET ?");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $emails = [];
        while ($row = $result->fetch_assoc()) {
            $emails[] = $row['email'];
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        return ['error' => DB_ERROR_MESSAGE];
    }
    return $emails;
}

/**
 * Restituisce tutte le email per tipo query (per l'export), ordinate A-Z
 * @return array Lista email o ['error' => ...]
 */
function fetchAllEmails($queryType, $dateFrom, $dateTo, $search = '') {
    $conn = getDbConnection();
    if (!$conn) {
        return ['error' => DB_ERROR_MESSAGE];
    }

    [$where, $types, $params] = buildEmailWhere($queryType, $dateFrom, $dateTo, $search);
    try {
        $stmt = $conn->prepare("SELECT LCASE(email) AS email FROM `store`.`cliente` WHERE $where ORDER BY email ASC");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $emails = [];
        while ($row = $result->fetch_assoc()) {
            $emails[] = $row['email'];
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        return ['error' => DB_ERROR_MESSAGE];
    }
    return $emails;
}
