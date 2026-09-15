<?php
require_once __DIR__ . '/db.php';

function create_password_reset(UrbanNestConnection $conn, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresUtc = gmdate('Y-m-d H:i:s', time() + 3600);

    $stmt = $conn->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $userId, $tokenHash, $expiresUtc);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Could not create password reset request.');
    }
    $stmt->close();

    return $token;
}

function find_valid_password_reset(UrbanNestConnection $conn, string $token): ?array
{
    $token = trim($token);
    if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
        return null;
    }

    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare(
        'SELECT pr.id AS reset_id, pr.user_id, u.username, u.email
         FROM password_resets pr
         INNER JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at >= UTC_TIMESTAMP()
         LIMIT 1'
    );
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result ?: null;
}

function consume_password_reset(UrbanNestConnection $conn, int $resetId, int $userId, string $newPasswordHash): bool
{
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT id FROM password_resets WHERE id = ? AND user_id = ? AND used_at IS NULL AND expires_at >= UTC_TIMESTAMP() FOR UPDATE');
        $stmt->bind_param('ii', $resetId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $conn->rollback();
            return false;
        }

        $stmt = $conn->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
        $stmt->bind_param('si', $newPasswordHash, $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('UPDATE password_resets SET used_at = UTC_TIMESTAMP() WHERE id = ?');
        $stmt->bind_param('i', $resetId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
