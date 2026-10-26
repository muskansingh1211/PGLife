<?php
session_start();
require_once "includes/database_connect.php";

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$property_id = filter_input(INPUT_GET, 'property_id', FILTER_VALIDATE_INT);
if (!$property_id || $property_id < 1) {
    http_response_code(404);
    exit('Property not found.');
}

$property_stmt = mysqli_prepare(
    $conn,
    'SELECT p.*, p.id AS property_id, p.name AS property_name, c.name AS city_name FROM properties p INNER JOIN cities c ON p.city_id = c.id WHERE p.id = ? LIMIT 1'
);
if (!$property_stmt) {
    error_log('PGLife property detail prepare failed: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Unable to load this property. Please try again later.');
}
mysqli_stmt_bind_param($property_stmt, 'i', $property_id);
mysqli_stmt_execute($property_stmt);
$property_result = mysqli_stmt_get_result($property_stmt);
$property = $property_result ? mysqli_fetch_assoc($property_result) : null;
mysqli_stmt_close($property_stmt);
if (!$property) {
    http_response_code(404);
    exit('Property not found.');
}

$testimonial_stmt = mysqli_prepare($conn, 'SELECT user_name, content FROM testimonials WHERE property_id = ? ORDER BY id');
if (!$testimonial_stmt) {
    http_response_code(500);
    exit('Unable to load this property. Please try again later.');
}
mysqli_stmt_bind_param($testimonial_stmt, 'i', $property_id);
mysqli_stmt_execute($testimonial_stmt);
$testimonial_result = mysqli_stmt_get_result($testimonial_stmt);
$testimonials = $testimonial_result ? mysqli_fetch_all($testimonial_result, MYSQLI_ASSOC) : [];
mysqli_stmt_close($testimonial_stmt);

$amenity_stmt = mysqli_prepare(
    $conn,
    'SELECT a.id, a.name, a.type, a.icon FROM amenities a INNER JOIN properties_amenities pa ON a.id = pa.amenity_id WHERE pa.property_id = ? ORDER BY a.id'
);
if (!$amenity_stmt) {
    http_response_code(500);
    exit('Unable to load this property. Please try again later.');
}
mysqli_stmt_bind_param($amenity_stmt, 'i', $property_id);
mysqli_stmt_execute($amenity_stmt);
$amenity_result = mysqli_stmt_get_result($amenity_stmt);
$amenities = $amenity_result ? mysqli_fetch_all($amenity_result, MYSQLI_ASSOC) : [];
mysqli_stmt_close($amenity_stmt);

$interested_stmt = mysqli_prepare($conn, 'SELECT user_id FROM interested_users_properties WHERE property_id = ?');
if (!$interested_stmt) {
    http_response_code(500);
    exit('Unable to load this property. Please try again later.');
}
mysqli_stmt_bind_param($interested_stmt, 'i', $property_id);
mysqli_stmt_execute($interested_stmt);
$interested_result = mysqli_stmt_get_result($interested_stmt);
$interested_users = $interested_result ? mysqli_fetch_all($interested_result, MYSQLI_ASSOC) : [];
$interested_users_count = count($interested_users);
mysqli_stmt_close($interested_stmt);

$property_id_int = (int)$property['property_id'];
$image_files = glob(__DIR__ . '/img/properties/' . $property_id_int . '/*') ?: [];
$property_images = array_map(
    static fn($path) => 'img/properties/' . $property_id_int . '/' . basename($path),
    $image_files
);
if (!$property_images) {
    $property_images = ['img/about.jpg'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string)$property['property_name'], ENT_QUOTES, 'UTF-8'); ?> | PG Life</title>

    <?php
    include "includes/head_links.php";
    ?>
    <link href="css/property_detail.css" rel="stylesheet" />
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
            <li class="breadcrumb-item">
                <a href="property_list.php?city=<?= rawurlencode((string)$property['city_name']); ?>"><?= htmlspecialchars((string)$property['city_name'], ENT_QUOTES, 'UTF-8'); ?></a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= htmlspecialchars((string)$property['property_name'], ENT_QUOTES, 'UTF-8'); ?>
            </li>
        </ol>
    </nav>

    <div id="property-images" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
            <?php
            foreach ($property_images as $index => $property_image) {
            ?>
                <li data-target="#property-images" data-slide-to="<?= $index ?>" class="<?= $index == 0 ? "active" : ""; ?>"></li>
            <?php
            }
            ?>
        </ol>
        <div class="carousel-inner">
            <?php
            foreach ($property_images as $index => $property_image) {
            ?>
                <div class="carousel-item <?= $index == 0 ? "active" : ""; ?>">
                    <img class="d-block w-100" src="<?= htmlspecialchars($property_image, ENT_QUOTES, 'UTF-8') ?>" alt="Property image">
                </div>
            <?php
            }
            ?>
        </div>
        <a class="carousel-control-prev" href="#property-images" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#property-images" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>

    <div class="property-summary page-container">
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
                <?php
                $is_interested = false;
                foreach ($interested_users as $interested_user) {
                    if ($interested_user['user_id'] == $user_id) {
                        $is_interested = true;
                    }
                }

                if ($is_interested) {
                ?>
                    <i class="is-interested-image fas fa-heart"></i>
                <?php
                } else {
                ?>
                    <i class="is-interested-image far fa-heart"></i>
                <?php
                }
                ?>
                <div class="interested-text">
                    <span class="interested-user-count"><?= $interested_users_count ?></span> interested
                </div>
            </div>
        </div>
        <div class="detail-container">
            <div class="property-name"><?= htmlspecialchars((string)$property['property_name'], ENT_QUOTES, 'UTF-8') ?></div>
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
                <a href="#" class="btn btn-primary">Book Now</a>
            </div>
        </div>
    </div>

    <div class="property-amenities">
        <div class="page-container">
            <h1>Amenities</h1>
            <div class="row justify-content-between">
                <div class="col-md-auto">
                    <h5>Building</h5>
                    <?php
                    foreach ($amenities as $amenity) {
                        if ($amenity['type'] == "Building") {
                    ?>
                            <div class="amenity-container">
                                <img src="img/amenities/<?= htmlspecialchars((string)$amenity['icon'], ENT_QUOTES, 'UTF-8') ?>.svg">
                                <span><?= htmlspecialchars((string)$amenity['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                    <?php
                        }
                    }
                    ?>
                </div>

                <div class="col-md-auto">
                    <h5>Common Area</h5>
                    <?php
                    foreach ($amenities as $amenity) {
                        if ($amenity['type'] == "Common Area") {
                    ?>
                            <div class="amenity-container">
                                <img src="img/amenities/<?= htmlspecialchars((string)$amenity['icon'], ENT_QUOTES, 'UTF-8') ?>.svg">
                                <span><?= htmlspecialchars((string)$amenity['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                    <?php
                        }
                    }
                    ?>
                </div>

                <div class="col-md-auto">
                    <h5>Bedroom</h5>
                    <?php
                    foreach ($amenities as $amenity) {
                        if ($amenity['type'] == "Bedroom") {
                    ?>
                            <div class="amenity-container">
                                <img src="img/amenities/<?= htmlspecialchars((string)$amenity['icon'], ENT_QUOTES, 'UTF-8') ?>.svg">
                                <span><?= htmlspecialchars((string)$amenity['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                    <?php
                        }
                    }
                    ?>
                </div>

                <div class="col-md-auto">
                    <h5>Washroom</h5>
                    <?php
                    foreach ($amenities as $amenity) {
                        if ($amenity['type'] == "Washroom") {
                    ?>
                            <div class="amenity-container">
                                <img src="img/amenities/<?= htmlspecialchars((string)$amenity['icon'], ENT_QUOTES, 'UTF-8') ?>.svg">
                                <span><?= htmlspecialchars((string)$amenity['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="property-about page-container">
        <h1>About the Property</h1>
        <p><?= nl2br(htmlspecialchars((string)$property['description'], ENT_QUOTES, 'UTF-8')) ?></p>
    </div>

    <div class="property-rating">
        <div class="page-container">
            <h1>Property Rating</h1>
            <div class="row align-items-center justify-content-between">
                <div class="col-md-6">
                    <div class="rating-criteria row">
                        <div class="col-6">
                            <i class="rating-criteria-icon fas fa-broom"></i>
                            <span class="rating-criteria-text">Cleanliness</span>
                        </div>
                        <div class="rating-criteria-star-container col-6" title="<?= $property['rating_clean'] ?>">
                            <?php
                            $rating = $property['rating_clean'];
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
                    </div>

                    <div class="rating-criteria row">
                        <div class="col-6">
                            <i class="rating-criteria-icon fas fa-utensils"></i>
                            <span class="rating-criteria-text">Food Quality</span>
                        </div>
                        <div class="rating-criteria-star-container col-6" title="<?= $property['rating_food'] ?>">
                            <?php
                            $rating = $property['rating_food'];
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
                    </div>

                    <div class="rating-criteria row">
                        <div class="col-6">
                            <i class="rating-criteria-icon fa fa-lock"></i>
                            <span class="rating-criteria-text">Safety</span>
                        </div>
                        <div class="rating-criteria-star-container col-6" title="<?= $property['rating_safety'] ?>">
                            <?php
                            $rating = $property['rating_safety'];
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
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="rating-circle">
                        <?php
                        $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
                        $total_rating = round($total_rating, 1);
                        ?>
                        <div class="total-rating"><?= $total_rating ?></div>
                        <div class="rating-circle-star-container">
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="property-testimonials page-container">
        <h1>What people say</h1>
        <?php
        foreach ($testimonials as $testimonial) {
        ?>
            <div class="testimonial-block">
                <div class="testimonial-image-container">
                    <img class="testimonial-img" src="img/man.png">
                </div>
                <div class="testimonial-text">
                    <i class="fa fa-quote-left" aria-hidden="true"></i>
                    <p><?= htmlspecialchars((string)$testimonial['content'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="testimonial-name">- <?= htmlspecialchars((string)$testimonial['user_name'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php
        }
        ?>
    </div>

    <?php
    include "includes/signup_modal.php";
    include "includes/login_modal.php";
    include "includes/footer.php";
    ?>

    <script type="text/javascript" src="js/property_detail.js"></script>
</body>

</html>
