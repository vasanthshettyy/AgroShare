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
 * @param int|null $targetId  Optional target ID (e.g. user ID).
 * @param string $description Detailed description.
 * @param int|null $adminId   Optional admin ID performing the action.
 */
function logAuditEvent(mysqli $conn, string $actionType, ?int $targetId, string $description, ?int $adminId = null): void
{
    try {
        $sql = "INSERT INTO audit_logs 
                (admin_id, action_type, target_id, description)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return; // Fail silently
        }

        $stmt->bind_param(
            'isis',
            $adminId, $actionType, $targetId, $description
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