<?php
/**
 * audit.php — Global audit logging helper for security and activity tracking.
 * 
 * Part of Module 2.7 (Security Audit) & Module 8.4 (Logging & Monitoring)
 */

/**
 * Log a security or business event into the audit_logs table.
 * 
 * Safety: This function swallows all internal exceptions. It will NEVER
 * break the application flow if the database or table is missing.
 * 
 * @param mysqli $conn The database connection.
 * @param array $event {
 *   actor_user_id: int|null,
 *   action_type: string (required),
 *   target_type: string|null,
 *   target_id: int|null,
 *   description: string (required),
 *   ip_address: string|null,
 *   user_agent: string|null,
 *   metadata: array|null (will be JSON-encoded)
 * }
 */
function logAuditEvent(mysqli $conn, array $event): void
{
    try {
        $sql = "INSERT INTO audit_logs 
                (actor_user_id, action_type, target_type, target_id, description, ip_address, user_agent, metadata_json)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return; // Fail silently
        }

        $actorId     = $event['actor_user_id'] ?? null;
        $actionType  = $event['action_type'];
        $targetType  = $event['target_type'] ?? null;
        $targetId    = $event['target_id'] ?? null;
        $description = $event['description'];
        $ipAddress   = $event['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent   = $event['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null;
        $metadata    = !empty($event['metadata']) ? json_encode($event['metadata']) : null;

        $stmt->bind_param(
            'ississss',
            $actorId, $actionType, $targetType, $targetId, $description, $ipAddress, $userAgent, $metadata
        );

        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Swallow exception, only log to PHP error log
        error_log("Audit log failed: " . $e->getMessage());
    } catch (Error $e) {
        // Handle database-level errors (like missing table) gracefully
        error_log("Audit log error: " . $e->getMessage());
    }
}