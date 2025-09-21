<?php
// Simple check to prevent running setup multiple times
if (file_exists('setup.lock')) {
    die("Setup has already been completed. Please remove 'setup.lock' to run it again.");
}

try {
    // We can't use db.php directly because the database might not exist yet.
    require_once 'includes/config.php';

    // 1. Connect to MySQL but not a specific database
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create the database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `" . DB_NAME . "`;");

    echo "Database '" . DB_NAME . "' created or already exists.<br>";

    // 3. Create tables
    $sql = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE
        );

        CREATE TABLE IF NOT EXISTS `entries` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `tmdb_id` INT NULL,
            `category_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `poster_url` VARCHAR(255),
            `thumbnail_url` VARCHAR(255),
            `rating` DECIMAL(3, 1),
            `parental_rating` VARCHAR(20),
            `country` VARCHAR(100),
            `year` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS `livetv_servers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `entry_id` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `url` VARCHAR(512) NOT NULL,
            `license_key` VARCHAR(255),
            `is_drm` BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (`entry_id`) REFERENCES `entries`(`id`) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS `seasons` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `entry_id` INT NOT NULL,
            `season_number` INT NOT NULL,
            `name` VARCHAR(255),
            `poster_url` VARCHAR(255),
            FOREIGN KEY (`entry_id`) REFERENCES `entries`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `entry_season` (`entry_id`, `season_number`)
        );

        CREATE TABLE IF NOT EXISTS `episodes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `season_id` INT NOT NULL,
            `episode_number` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `duration` VARCHAR(50),
            `description` TEXT,
            `thumbnail_url` VARCHAR(255),
            FOREIGN KEY (`season_id`) REFERENCES `seasons`(`id`) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS `servers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `url_template` VARCHAR(512) NOT NULL,
            `type` ENUM('movie', 'tv') NOT NULL,
            `is_enabled` BOOLEAN DEFAULT TRUE
        );

        CREATE TABLE IF NOT EXISTS `entry_servers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `entry_id` INT NULL,
            `episode_id` INT NULL,
            `server_id` INT NOT NULL,
            `url` VARCHAR(512) NOT NULL,
            FOREIGN KEY (`entry_id`) REFERENCES `entries`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`server_id`) REFERENCES `servers`(`id`) ON DELETE CASCADE
        );
    ";

    $pdo->exec($sql);
    echo "Tables created successfully.<br>";

    // 4. Populate initial data
    // Insert categories
    $categories = ['Movies', 'TV Series', 'Live TV'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO `categories` (name) VALUES (?)");
    foreach ($categories as $category) {
        $stmt->execute([$category]);
    }
    echo "Categories populated.<br>";

    // Insert embeddable servers from the reference JSON
    $servers_to_add = [
        ['name' => 'VidSrc', 'url_template' => 'https://vidsrc.net/embed/movie/{tmdb_id}', 'type' => 'movie'],
        ['name' => 'VidSrc', 'url_template' => 'https://vidsrc.net/embed/tv/{tmdb_id}/{season}-{episode}', 'type' => 'tv'],
        ['name' => 'VidJoy', 'url_template' => 'https://vidjoy.pro/embed/movie/{tmdb_id}', 'type' => 'movie'],
        ['name' => 'VidJoy', 'url_template' => 'https://vidjoy.pro/embed/tv/{tmdb_id}/{season}-{episode}', 'type' => 'tv'],
        ['name' => 'Embed.su', 'url_template' => 'https://embed.su/embed/movie/{tmdb_id}', 'type' => 'movie'],
        ['name' => 'Embed.su', 'url_template' => 'https://embed.su/embed/tv/{tmdb_id}/{season}/{episode}', 'type' => 'tv'],
        ['name' => 'AutoEmbed', 'url_template' => 'https://player.autoembed.cc/embed/movie/{tmdb_id}', 'type' => 'movie'],
        ['name' => 'AutoEmbed', 'url_template' => 'https://player.autoembed.cc/embed/tv/{tmdb_id}-{season}-{episode}', 'type' => 'tv'],
        ['name' => '2Embed.cc', 'url_template' => 'https://2embed.cc/embed/{tmdb_id}', 'type' => 'movie']
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO `servers` (name, url_template, type) VALUES (?, ?, ?)");
    foreach ($servers_to_add as $server) {
        $stmt->execute([$server['name'], $server['url_template'], $server['type']]);
    }
    echo "Embeddable servers populated.<br>";


    // 5. Create the default admin user
    $username = ADMIN_USERNAME;
    $password = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM `users` WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo "Admin user already exists.<br>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO `users` (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password]);
        echo "Admin user created successfully. <br><strong>Username:</strong> " . ADMIN_USERNAME . "<br><strong>Password:</strong> " . ADMIN_PASSWORD . "<br>";
    }

    // 6. Create lock file
    file_put_contents('setup.lock', 'Completed');
    echo "Setup complete. A 'setup.lock' file has been created. Please delete this setup file for security reasons.";

} catch (PDOException $e) {
    die("DB ERROR: ". $e->getMessage());
}
?>
