<?php
namespace App;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use Adldap\Adldap;
use Adldap\Schemas\ActiveDirectory;

class Auth {
    protected $ad;
    protected $config;

    public function __construct() {
        // Configurazione per Adldap2 v10
        $this->config = [
            'hosts' => [AD_HOST],
            'base_dn' => AD_BASE_DN,
            'username' => AD_ADMIN_USERNAME,
            'password' => AD_ADMIN_PASSWORD,
            'use_ssl' => AD_USE_SSL,
            'use_tls' => AD_USE_TLS,
            'port' => AD_PORT,
        ];
    }

    /**
     * Tenta il login con username e password
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return false;
        }

        try {
            // Inizializza Adldap
            $ad = new Adldap();
            
            // Aggiungi provider
            $ad->addProvider($this->config);
            
            // Connetti al provider di default
            $provider = $ad->connect();
            
            // Tenta autenticazione
            // Nota: Adldap2 v10 usa $provider->auth()->attempt($username, $password)
            // Se $username non ha il suffisso, potremmo doverlo aggiungere o usare account_suffix nella config (che però non c'è in v10 nativamente come in v4)
            // Ma v10 gestisce il binding.
            
            // Se l'username non è un'email o DN completo, potrebbe servire il suffisso.
            // Proviamo ad aggiungere il suffisso se manca e se definito
            if (defined('AD_ACCOUNT_SUFFIX') && !empty(AD_ACCOUNT_SUFFIX) && strpos($username, '@') === false) {
                $usernameWithSuffix = $username . AD_ACCOUNT_SUFFIX;
            } else {
                $usernameWithSuffix = $username;
            }

            if ($provider->auth()->attempt($usernameWithSuffix, $password)) {
                // Login riuscito
                // Recupera info utente se necessario
                // $user = $provider->search()->users()->find($username);
                
                $_SESSION['user'] = $username;
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Errore AD Auth: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se l'utente è loggato
     * @return bool
     */
    public function check() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Logout utente
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Ottieni username corrente
     */
    public function user() {
        return $_SESSION['user'] ?? null;
    }
}
