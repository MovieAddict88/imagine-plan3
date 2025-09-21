<?php
session_start();
require_once '../includes/db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/add_entry.php?error=Invalid request method');
    exit;
}

$category_name = $_POST['category'] ?? '';
if (empty($category_name)) {
    header('Location: ../admin/add_entry.php?error=Category is missing');
    exit;
}

// --- Common data for all entries ---
$title = $_POST['title'] ?? null;
$tmdb_id = !empty($_POST['tmdb_id']) ? (int)$_POST['tmdb_id'] : null;
$description = $_POST['description'] ?? null;
$poster_url = $_POST['poster_url'] ?? null;
$thumbnail_url = $_POST['thumbnail_url'] ?? null;
$rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : null;
$year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
$parental_rating = $_POST['parental_rating'] ?? null;
$country = $_POST['country'] ?? null;

// Validate title
if (empty($title)) {
    header('Location: ../admin/add_entry.php?error=Title is required');
    exit;
}

$pdo->beginTransaction();

try {
    // Get the category ID
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$category_name]);
    $category_id = $stmt->fetchColumn();

    if (!$category_id) {
        throw new Exception("Invalid category name provided.");
    }

    // Insert into the main 'entries' table
    $sql = "INSERT INTO entries (tmdb_id, category_id, title, description, poster_url, thumbnail_url, rating, parental_rating, country, year)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $tmdb_id,
        $category_id,
        $title,
        $description,
        $poster_url,
        $thumbnail_url,
        $rating,
        $parental_rating,
        $country,
        $year
    ]);

    $entry_id = $pdo->lastInsertId();

    // --- Handle category-specific data ---

    // If it's a Live TV entry, add the servers
    if ($category_name === 'Live TV' && isset($_POST['servers']) && is_array($_POST['servers'])) {
        $server_sql = "INSERT INTO livetv_servers (entry_id, name, url, license_key) VALUES (?, ?, ?, ?)";
        $server_stmt = $pdo->prepare($server_sql);

        foreach ($_POST['servers'] as $server) {
            if (!empty($server['name']) && !empty($server['url'])) {
                $server_stmt->execute([
                    $entry_id,
                    $server['name'],
                    $server['url'],
                    $server['license'] ?? null
                ]);
            }
        }
    }

    $pdo->commit();
    header('Location: ../admin/add_entry.php?success=Entry added successfully!');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    // In production, log the error message
    // error_log($e->getMessage());
    header('Location: ../admin/add_entry.php?error=Failed to add entry. ' . $e->getMessage());
    exit;
}
?>
