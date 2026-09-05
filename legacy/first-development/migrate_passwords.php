<?php

require_once __DIR__ . '/config.php';

if (!$pdo instanceof PDO) {
    die('Database connection failed.');
}

try {

    $stmt = $pdo->query(
        "SELECT user_id, email, password_hash
         FROM WBO_Users"
    );

    $users = $stmt->fetchAll();

    $converted = 0;
    $alreadyHashed = 0;

    foreach ($users as $user) {

        $storedPassword = $user['password_hash'];

        // Check if password is already hashed
        $info = password_get_info($storedPassword);

        if (!empty($info['algo'])) {

            $alreadyHashed++;

            continue;
        }

        // Current value is plain text
        // Convert it into a secure password hash
        $hashedPassword = password_hash(
            $storedPassword,
            PASSWORD_DEFAULT
        );

        $update = $pdo->prepare(
            "UPDATE WBO_Users
             SET password_hash = ?
             WHERE user_id = ?"
        );

        $update->execute([
            $hashedPassword,
            $user['user_id']
        ]);

        $converted++;
    }

    echo "
        <h2>Password Migration Complete</h2>

        <p>
            Passwords converted:
            <strong>{$converted}</strong>
        </p>

        <p>
            Already hashed:
            <strong>{$alreadyHashed}</strong>
        </p>

        <p>
            You may now delete
            <strong>migrate_passwords.php</strong>.
        </p>
    ";

} catch (PDOException $e) {

    echo "Database error: " .
        htmlspecialchars($e->getMessage());
}