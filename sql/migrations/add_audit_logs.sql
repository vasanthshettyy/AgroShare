-- ============================================================
-- Migration: Add audit_logs table
-- Purpose: Track security-critical events (login attempts, etc.)
-- ============================================================

CREATE TABLE IF NOT EXISTS audit_logs (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    actor_user_id   INT UNSIGNED        NULL DEFAULT NULL COMMENT 'User performing action (NULL for unauthenticated)',
    action_type     VARCHAR(50)         NOT NULL COMMENT 'e.g. login_failed, login_success, profile_update',
    target_type     VARCHAR(50)         NULL DEFAULT NULL COMMENT 'Table affected (e.g. users, equipment)',
    target_id       INT UNSIGNED        NULL DEFAULT NULL COMMENT 'ID of record affected',
    description     TEXT                NOT NULL COMMENT 'Human-readable summary of the event',
    ip_address      VARCHAR(45)         NULL DEFAULT NULL COMMENT 'IPv4 or IPv6 address',
    user_agent      TEXT                NULL DEFAULT NULL COMMENT 'Browser/Client identity',
    metadata_json   JSON                NULL DEFAULT NULL COMMENT 'Contextual data (mask sensitive info!)',
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_audit_action (action_type, created_at),
    INDEX idx_audit_actor (actor_user_id, created_at),

    CONSTRAINT fk_audit_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;