<?php
/**
 * Registro estructurado de eventos de aplicación con fines de trazabilidad forense.
 *
 * Cada llamada añade una línea en formato JSON (JSON Lines) al fichero de log,
 * incluyendo: marca de tiempo, tipo de evento, resultado, IP de origen,
 * usuario autenticado (si lo hay) y un contexto libre con detalles del evento.
 *
 * El formato JSON Lines permite cargar el log completo en un DataFrame de
 * pandas con una sola instrucción (pandas.read_json(path, lines=True)),
 * tal y como se describe en el apartado 2.4.2 del TFM.
 */
 
if (!defined('APP_LOG_PATH')) {
    define('APP_LOG_PATH', '/var/log/vulnapp/app_events.log'); 
}
 
/**
 * Registra un evento de aplicación.
 *
 * @param string $eventType Identificador corto del evento (ej. 'login', 'register',
 *                           'purchase_created', 'payment_completed', 'access_denied').
 * @param string $status    Resultado del evento: 'success', 'failure' o 'info'.
 * @param array  $context   Datos adicionales relevantes para ese evento concreto
 *                           (ej. ['username_attempted' => $username]).
 */
function logEvent(string $eventType, string $status, array $context = []): void {
    $entry = [
        'timestamp' => date('c'), // ISO 8601 con zona horaria, formato estándar y ordenable
        'event'     => $eventType,
        'status'    => $status,
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'uuid_user' => $_SESSION['uuid'] ?? null,
        'username'  => $_SESSION['username'] ?? null,
        'uri'       => $_SERVER['REQUEST_URI'] ?? null,
        'context'   => $context,
    ];
 
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
 
    // error_log con destino a fichero (message_type 3) escribe la línea de forma
    // atómica sin necesidad de gestionar manualmente el descriptor ni el bloqueo.
    error_log($line, 3, APP_LOG_PATH);
}
?>