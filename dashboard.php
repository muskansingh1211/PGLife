<?php
session_start();
require_once "includes/database_connect.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if (!$user_id || $user_id < 1) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

$user_stmt = mysqli_prepare($conn, 'SELECT id, email, full_name, phone, gender, college_name FROM users WHERE id = ? LIMIT 1');
if (!$user_stmt) {
    error_log('PGLife dashboard user query prepare failed: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Unable to load dashboard. Please try again later.');
}
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = $user_result ? mysqli_fetch_assoc($user_result) : null;
mysqli_stmt_close($user_stmt);
if (!$user) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

$interested_stmt = mysqli_prepare(
    $conn,
    'SELECT p.* FROM interested_users_properties iup INNER JOIN properties p ON iup.property_id = p.id WHERE iup.user_id = ? ORDER BY p.id'
);
if (!$interested_stmt) {
    error_log('PGLife dashboard interested properties prepare failed: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Unable to load dashboard. Please try again later.');
}
mysqli_stmt_bind_param($interested_stmt, 'i', $user_id);
mysqli_stmt_execute($interested_stmt);
$interested_result = mysqli_stmt_get_result($interested_stmt);
$interested_properties = $interested_result ? mysqli_fetch_all($interested_result, MYSQLI_ASSOC) : [];
mysqli_stmt_close($interested_stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | PG Life</title>

    <?php
    include "includes/head_links.php";
    ?>
    <link href="css/dashboard.css" rel="stylesheet" />
</head>

<body>
    <?php
    include "includes/header.php";
    ?>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb py-2">
            <li class="breadcrumb-item">
                <a href="index.php">Home</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                Dashboard
            </li>
        </ol>
    </nav>

    <div class="my-profile page-container">
        <h1>My Profile</h1>
        <div class="row">
            <div class="col-md-3 profile-img-container">
                <i class="fas fa-user profile-img"></i>
            </div>
            <div class="col-md-9">
                <div class="row no-gutters justify-content-between align-items-end">
                    <div class="profile">
                        <div class="name"><?= htmlspecialchars((string)$user['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="email"><?= htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="phone"><?= htmlspecialchars((string)$user['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="college"><?= htmlspecialchars((string)$user['college_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="edit">
                        <div class="edit-profile">Edit Profile</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    if (count($interested_properties) > 0) {
    ?>
        <div class="my-interested-properties">
            <div class="page-container">
                <h1>My Interested Properties</h1>

                <?php
                foreach ($interested_properties as $property) {
                    $property_id = (int)$property['id'];
                    $property_images = glob(__DIR__ . "/img/properties/" . $property_id . "/*") ?: [];
                    $property_image = $property_images
                        ? "img/properties/" . $property_id . "/" . basename($property_images[0])
                        : "img/about.jpg";
                ?>
                    <div class="property-card property-id-<?= $property['id'] ?> row">
                        <div class="image-container col-md-4">
                            <img src="<?= htmlspecialchars($property_image, ENT_QUOTES, 'UTF-8') ?>" alt="Property image" />
                        </div>
                        <div class="content-container col-md-8">
                            <div class="row no-gutters justify-content-between">
                                <?php
                                $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
                                $total_rating = round($total_rating, 1);
                                ?>
                                <div class="star-container" title="<?= $total_rating ?>">
                                    <?php
                                    $rating = $total_rating;
                                    for ($i = 0; $i < 5; $i++) {
                                        if ($rating >= $i + 0.8) {
                                    ?>
                                            <i class="fas fa-star"></i>
                                        <?php
                                        } elseif ($rating >= $i + 0.3) {
                                        ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php
                                        } else {
                                        ?>
                                            <i class="far fa-star"></i>
                                    <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="interested-container">
                                    <i class="is-interested-image fas fa-heart" property_id="<?= $property['id'] ?>"></i>
                                </div>
                            </div>
                            <div class="detail-container">
                                <div class="property-name"><?= htmlspecialchars((string)$property['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="property-address"><?= htmlspecialchars((string)$property['address'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="property-gender">
                                    <?php
                                    if ($property['gender'] == "male") {
                                    ?>
                                        <img src="img/male.png">
                                    <?php
                                    } elseif ($property['gender'] == "female") {
                                    ?>
                                        <img src="img/female.png">
                                    <?php
                                    } else {
                                    ?>
                                        <img src="img/unisex.png">
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="row no-gutters">
                                <div class="rent-container col-6">
                                    <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                                    <div class="rent-unit">per month</div>
                                </div>
                                <div class="button-container col-6">
                                    <a href="property_detail.php?property_id=<?= $property['id'] ?>" class="btn btn-primary">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    <?php
    }
    ?>

    <?php
    include "includes/footer.php";
    ?>

    <script type="text/javascript" src="js/dashboard.js"></script>
</body>

</html>
