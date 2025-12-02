<?php
/**
 * Configurazione Database
 * Solo lettura - nessuna modifica ai dati
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'store');
define('DB_USER', 'read');
define('DB_PASS', 'msmild');
define('DB_CHARSET', 'utf8mb4');

/**
 * Configurazione Active Directory
 */
define('AD_HOST', '192.168.0.10'); // IP o Hostname del Domain Controller
define('AD_BASE_DN', 'dc=xxx,dc=xxt');
define('AD_ACCOUNT_SUFFIX', '@xxx.xxx');
define('AD_USE_SSL', false);
define('AD_USE_TLS', false);
define('AD_PORT', 389);
// Opzionale: Utente di servizio per il binding (se anonimo non permesso)
define('AD_ADMIN_USERNAME', 'xxxx@xxxx.xt');
define('AD_ADMIN_PASSWORD', 'xxxxx');


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
 * Esegue query 1: email con newsletter_consent=1
 * @param string $dateFrom
 * @param string $dateTo
 * @return array
 */
function executeQuery1($dateFrom, $dateTo) {
    $conn = getDbConnection();
    if (!$conn) {
        return ['error' => 'Errore di connessione al database'];
    }
    
    $stmt = $conn->prepare("SELECT LCASE(email) as email FROM `store`.`cliente` WHERE creation_date >= ? AND creation_date <= ? AND newsletter_consent=1 ORDER BY email;");
    
    if (!$stmt) {
        return ['error' => 'Errore nella preparazione della query: ' . $conn->error];
    }
    
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }
    
    $stmt->close();
    return $emails;
}

/**
 * Esegue query 2: email con newsletter_consent=1 AND cultural_consent=1
 * @param string $dateFrom
 * @param string $dateTo
 * @return array
 */
function executeQuery2($dateFrom, $dateTo) {
    $conn = getDbConnection();
    if (!$conn) {
        return ['error' => 'Errore di connessione al database'];
    }
    
    $stmt = $conn->prepare("SELECT LCASE(email) as email FROM `store`.`cliente` WHERE creation_date >= ? AND creation_date <= ? AND newsletter_consent=1 AND cultural_consent=1 ORDER BY email;");
    
    if (!$stmt) {
        return ['error' => 'Errore nella preparazione della query: ' . $conn->error];
    }
    
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }
    
    $stmt->close();
    return $emails;
}

