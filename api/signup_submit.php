<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/database_connect.php';

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Please submit the signup form.'], 405);
}

$full_name = trim((string)($_POST['full_name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$college_name = trim((string)($_POST['college_name'] ?? ''));
$gender = strtolower(trim((string)($_POST['gender'] ?? '')));

if ($full_name === '' || $phone === '' || $college_name === '' || $email === '' || $password === '') {
    respond(['success' => false, 'message' => 'Please fill in all required fields.'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'message' => 'Please enter a valid email address.'], 400);
}
if (strlen($password) < 6) {
    respond(['success' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
}
if (!in_array($gender, ['male', 'female'], true)) {
    respond(['success' => false, 'message' => 'Please select your gender.'], 400);
}
if (!preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) {
    respond(['success' => false, 'message' => 'Please enter a valid phone number.'], 400);
}

$check = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
if (!$check) {
    error_log('PGLife signup prepare failed: ' . mysqli_error($conn));
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
mysqli_stmt_bind_param($check, 's', $email);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
$already_registered = mysqli_stmt_num_rows($check) > 0;
mysqli_stmt_close($check);

if ($already_registered) {
    respond(['success' => false, 'message' => 'This email address is already registered.']);
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$insert = mysqli_prepare(
    $conn,
    'INSERT INTO users (email, password, full_name, phone, gender, college_name) VALUES (?, ?, ?, ?, ?, ?)'
);
if (!$insert) {
    error_log('PGLife signup insert prepare failed: ' . mysqli_error($conn));
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
mysqli_stmt_bind_param($insert, 'ssssss', $email, $password_hash, $full_name, $phone, $gender, $college_name);

if (!mysqli_stmt_execute($insert)) {
    $errno = mysqli_stmt_errno($insert);
    error_log('PGLife signup insert failed with MySQL error ' . $errno);
    mysqli_stmt_close($insert);
    if ($errno === 1062) {
        respond(['success' => false, 'message' => 'This email address is already registered.']);
    }
    respond(['success' => false, 'message' => 'Unable to create your account. Please try again.'], 500);
}

mysqli_stmt_close($insert);
respond(['success' => true, 'message' => 'Your account has been created successfully!']);
