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
 * @param mysqli $conn        The database connection.
 * @param string $actionType  Type of action (e.g. 'login_failed').
 * @param int|null $actorId   The user ID performing the action (if applicable).
 * @param string $description Detailed description.
 * @param array|null $metadata Optional array of extra context (will be JSON encoded).
 * @param int|null $adminId   Optional admin ID performing the action.
 */
function logAuditEvent(mysqli $conn, string $actionType, ?int $actorId, string $description, ?array $metadata = null, ?int $adminId = null): void
{
    try {
        $metaJson = $metadata ? json_encode($metadata) : null;

        // Try inserting into the modern schema first
        $sql = "INSERT INTO audit_logs 
                (admin_id, action_type, actor_user_id, description, metadata_json)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            // Fallback for older schema (target_id instead of actor_user_id, no metadata)
            $sqlFallback = "INSERT INTO audit_logs (admin_id, action_type, target_id, description) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sqlFallback);
            if (!$stmt) return;
            $stmt->bind_param('isis', $adminId, $actionType, $actorId, $description);
        } else {
            $stmt->bind_param(
                'isiss',
                $adminId, $actionType, $actorId, $description, $metaJson
            );
        }

        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    } catch (Error $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}