<?php
/**
 * PGLife database connection.
 * Local XAMPP defaults: host 127.0.0.1, user root, empty password, database pglife.
 * If your MySQL root account has a password, set it in $db_password below.
 * On hosting, use the database credentials supplied by your hosting provider.
 */
mysqli_report(MYSQLI_REPORT_OFF);

$db_hostname = '127.0.0.1';
$db_username = 'root';
$db_password = ''; // Add your local MySQL password here only if you configured one.
$db_name = 'pglife';

$conn = @mysqli_connect($db_hostname, $db_username, $db_password, $db_name);

if (!$conn) {
    error_log('PGLife database connection failed: ' . mysqli_connect_error());
    http_response_code(500);

    $is_api_request = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false;
    if ($is_api_request) {
        header('Content-Type: application/json; charset=utf-8');
        if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'get_properties_by_city.php') {
            // The React property-list component expects the city endpoint to return an array.
            echo '[]';
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed. Check includes/database_connect.php.'
            ]);
        }
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Database connection failed. Check includes/database_connect.php.';
    }
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');
