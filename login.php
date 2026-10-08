<?php
require_once __DIR__ . '/src/output.php';
session_start();
require_once 'src/Auth.php';

use App\Auth;

$auth = new Auth();
$error = '';

// Se già loggato, vai alla home
if ($auth->check()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Credenziali non valide o errore di connessione.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso · Esportazione email MiC Card</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <main class="page page--narrow">
        <h1>Esportazione email MiC Card</h1>
        <p class="hint">Accedi con le credenziali che usi per il PC aziendale.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="login-form">
            <div class="field">
                <label for="username">Nome utente</label>
                <input type="text" id="username" name="username" autocomplete="username" required autofocus>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary">Accedi</button>
        </form>
    </main>
    <script>
        document.getElementById('login-form').addEventListener('submit', function () {
            var b = this.querySelector('button[type="submit"]');
            b.disabled = true; b.setAttribute('aria-busy', 'true'); b.textContent = 'Accesso in corso…';
        });
    </script>
</body>
</html>
