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

if (!isset($_SESSION['user_id'])) {
    respond(['success' => false, 'is_logged_in' => false, 'message' => 'Please log in first.'], 401);
}

$property_id = filter_input(INPUT_GET, 'property_id', FILTER_VALIDATE_INT);
if (!$property_id || $property_id < 1) {
    respond(['success' => false, 'message' => 'Invalid property.'], 400);
}
$user_id = (int)$_SESSION['user_id'];

$check = mysqli_prepare($conn, 'SELECT id FROM interested_users_properties WHERE user_id = ? AND property_id = ? LIMIT 1');
if (!$check) {
    error_log('PGLife interest check prepare failed: ' . mysqli_error($conn));
    respond(['success' => false, 'message' => 'Unable to update interested properties.'], 500);
}
mysqli_stmt_bind_param($check, 'ii', $user_id, $property_id);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);
$existing = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($check);

if ($existing) {
    $stmt = mysqli_prepare($conn, 'DELETE FROM interested_users_properties WHERE user_id = ? AND property_id = ?');
    if (!$stmt) {
        respond(['success' => false, 'message' => 'Unable to update interested properties.'], 500);
    }
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $property_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$ok) {
        respond(['success' => false, 'message' => 'Unable to update interested properties.'], 500);
    }
    respond(['success' => true, 'is_interested' => false, 'property_id' => (int)$property_id]);
}

$insert = mysqli_prepare($conn, 'INSERT INTO interested_users_properties (user_id, property_id) VALUES (?, ?)');
if (!$insert) {
    error_log('PGLife interest insert prepare failed: ' . mysqli_error($conn));
    respond(['success' => false, 'message' => 'Unable to update interested properties.'], 500);
}
mysqli_stmt_bind_param($insert, 'ii', $user_id, $property_id);
$ok = mysqli_stmt_execute($insert);
$errno = mysqli_stmt_errno($insert);
mysqli_stmt_close($insert);
if (!$ok) {
    error_log('PGLife interest insert failed with MySQL error ' . $errno);
    respond(['success' => false, 'message' => 'Unable to update interested properties.'], 500);
}
respond(['success' => true, 'is_interested' => true, 'property_id' => (int)$property_id]);
