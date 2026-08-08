<?php

require_once __DIR__ . '/config.php';


// ==========================================
// AUDIT / ACTIVITY LOGGER
// ==========================================

function log_activity(
    PDO $pdo,
    ?int $userId,
    string $action,
    ?string $description = null
) {

    try {

        $ipAddress =
            $_SERVER['REMOTE_ADDR'] ?? null;


        $stmt = $pdo->prepare(
            "INSERT INTO WBO_AuditLogs
            (
                user_id,
                action,
                description,
                ip_address
            )
                        VALUES (?, ?, ?, ?)"
        );


        $stmt->execute([
            $userId,
            $action,
            $description,
            $ipAddress
        ]);

    }

    catch (PDOException $e) {

        // Audit failure should not crash the whole system
        error_log(
            'Audit log error: ' .
            $e->getMessage()
        );
    }
}