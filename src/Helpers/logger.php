<?php
/**
 * logger.php — Centralized error logging.
 */

/**
 * Log an error message with context to the logs/error.log file.
 *
 * @param string $message The error message to log.
 * @param array $context Additional context data (optional).
 */
function logError(string $message, array $context = []): void
{
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logFile = $logDir . '/error.log';
    
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'] ?? 'Guest';
    $uri = $_SERVER['REQUEST_URI'] ?? 'CLI';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    
    $formattedMessage = "[$timestamp] [IP: $ip] [User: $userId] [URI: $uri] $message$contextStr" . PHP_EOL;
    
    // Use error_log with message type 3 to write to a specific file
    error_log($formattedMessage, 3, $logFile);
}
