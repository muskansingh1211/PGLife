<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/database_connect.php';

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Please submit the login form.'], 405);
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'message' => 'Please enter a valid email and password.'], 400);
}

$stmt = mysqli_prepare($conn, 'SELECT id, email, password, full_name FROM users WHERE email = ? LIMIT 1');
if (!$stmt) {
    error_log('PGLife login prepare failed: ' . mysqli_error($conn));
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!$result) {
    error_log('PGLife login query failed: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    respond(['success' => false, 'message' => 'Login failed! Invalid email or password.']);
}

$stored_password = (string)$user['password'];
$password_is_valid = password_verify($password, $stored_password);
$is_legacy_sha1 = preg_match('/^[a-f0-9]{40}$/i', $stored_password) === 1;
if (!$password_is_valid && $is_legacy_sha1) {
    // The provided starter SQL uses SHA-1. Accept those existing accounts once,
    // then upgrade their password to password_hash() after a successful login.
    $password_is_valid = hash_equals(strtolower($stored_password), sha1($password));
    if ($password_is_valid) {
        $upgraded_hash = password_hash($password, PASSWORD_DEFAULT);
        $upgrade = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
        if ($upgrade) {
            $user_id_for_update = (int)$user['id'];
            mysqli_stmt_bind_param($upgrade, 'si', $upgraded_hash, $user_id_for_update);
            mysqli_stmt_execute($upgrade);
            mysqli_stmt_close($upgrade);
        }
    }
}

if (!$password_is_valid) {
    respond(['success' => false, 'message' => 'Login failed! Invalid email or password.']);
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['full_name'] = (string)$user['full_name'];
$_SESSION['email'] = (string)$user['email'];

respond(['success' => true, 'message' => 'Login successful!']);
