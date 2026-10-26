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

$city_name = trim((string)($_GET['city'] ?? ''));
if (strcasecmp($city_name, 'Bangalore') === 0) {
    $city_name = 'Bengaluru';
}
if ($city_name === '') {
    respond([]);
}

$city_stmt = mysqli_prepare($conn, 'SELECT id FROM cities WHERE name = ? LIMIT 1');
if (!$city_stmt) {
    error_log('PGLife city lookup prepare failed: ' . mysqli_error($conn));
    respond([], 500);
}
mysqli_stmt_bind_param($city_stmt, 's', $city_name);
mysqli_stmt_execute($city_stmt);
$city_result = mysqli_stmt_get_result($city_stmt);
$city = $city_result ? mysqli_fetch_assoc($city_result) : null;
mysqli_stmt_close($city_stmt);
if (!$city) {
    respond([]);
}
$city_id = (int)$city['id'];

$properties_stmt = mysqli_prepare(
    $conn,
    'SELECT id, city_id, name, address, description, gender, rent, rating_clean, rating_food, rating_safety FROM properties WHERE city_id = ? ORDER BY id'
);
if (!$properties_stmt) {
    error_log('PGLife properties prepare failed: ' . mysqli_error($conn));
    respond([], 500);
}
mysqli_stmt_bind_param($properties_stmt, 'i', $city_id);
mysqli_stmt_execute($properties_stmt);
$properties_result = mysqli_stmt_get_result($properties_stmt);
$properties = $properties_result ? mysqli_fetch_all($properties_result, MYSQLI_ASSOC) : [];
mysqli_stmt_close($properties_stmt);

$interested_stmt = mysqli_prepare(
    $conn,
    'SELECT iup.user_id, iup.property_id FROM interested_users_properties iup INNER JOIN properties p ON p.id = iup.property_id WHERE p.city_id = ?'
);
if (!$interested_stmt) {
    error_log('PGLife interested list prepare failed: ' . mysqli_error($conn));
    respond([], 500);
}
mysqli_stmt_bind_param($interested_stmt, 'i', $city_id);
mysqli_stmt_execute($interested_stmt);
$interested_result = mysqli_stmt_get_result($interested_stmt);
$interested_records = $interested_result ? mysqli_fetch_all($interested_result, MYSQLI_ASSOC) : [];
mysqli_stmt_close($interested_stmt);

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$output = [];
foreach ($properties as $property) {
    $property_id = (int)$property['id'];
    $image_files = glob(__DIR__ . '/../img/properties/' . $property_id . '/*') ?: [];
    $image_path = $image_files
        ? 'img/properties/' . $property_id . '/' . basename($image_files[0])
        : 'img/about.jpg';

    $interested_count = 0;
    $is_interested = false;
    foreach ($interested_records as $record) {
        if ((int)$record['property_id'] === $property_id) {
            $interested_count++;
            if ($user_id !== null && (int)$record['user_id'] === $user_id) {
                $is_interested = true;
            }
        }
    }

    $property['id'] = $property_id;
    $property['city_id'] = (int)$property['city_id'];
    $property['rent'] = (int)$property['rent'];
    $property['rating_clean'] = (float)$property['rating_clean'];
    $property['rating_food'] = (float)$property['rating_food'];
    $property['rating_safety'] = (float)$property['rating_safety'];
    $property['interested_users_count'] = $interested_count;
    $property['is_interested'] = $is_interested;
    $property['image'] = $image_path;
    $output[] = $property;
}

respond($output);
