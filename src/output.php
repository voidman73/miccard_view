<?php
/**
 * Bufferizza l'intera risposta e la invia con Content-Length.
 * La rete tra client e server tronca le risposte chunked
 * (ERR_INCOMPLETE_CHUNKED_ENCODING), anche se piccole.
 */

ob_start();

register_shutdown_function(function () {
    $out = '';
    while (ob_get_level() > 0) {
        $out = ob_get_clean() . $out;
    }
    if (!headers_sent()) {
        header('Content-Length: ' . strlen($out));
    }
    echo $out;
});
