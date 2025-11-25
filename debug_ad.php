<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

echo "--- Raw PHP LDAP Debug ---\n";
$host = AD_HOST;
$port = AD_PORT;
$user = AD_ADMIN_USERNAME;
$pass = AD_ADMIN_PASSWORD;

echo "Connecting to $host:$port...\n";
$conn = ldap_connect($host, $port);

if (!$conn) {
    echo "FATAL: Could not connect to LDAP server.\n";
    exit;
}

ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

echo "Binding with $user...\n";
// Try bind
try {
    $bind = @ldap_bind($conn, $user, $pass);
    if ($bind) {
        echo "SUCCESS: Bind successful!\n";
    } else {
        echo "FAILED: Bind failed.\n";
        echo "Error #" . ldap_errno($conn) . ": " . ldap_error($conn) . "\n";
        
        $extended_error = '';
        ldap_get_option($conn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
        echo "Extended Error: $extended_error\n";
        
        // Try DN patterns
        $baseDn = 'dc=zetema,dc=it';
        $users = ['generalservice'];
        
        foreach ($users as $u) {
            $patterns = [
                "CN=$u,CN=Users,$baseDn",
                "CN=$u,$baseDn",
                "UID=$u,CN=Users,$baseDn",
                "$u@zetema.it",
                "ZETEMA\\$u"
            ];
            
            foreach ($patterns as $dn) {
                echo "Trying bind with: $dn ...\n";
                $bindAttempt = @ldap_bind($conn, $dn, $pass);
                if ($bindAttempt) {
                    echo "SUCCESS! Valid DN found: $dn\n";
                    exit;
                } else {
                    // echo "Failed.\n";
                }
            }
        }
        echo "All attempts failed.\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
