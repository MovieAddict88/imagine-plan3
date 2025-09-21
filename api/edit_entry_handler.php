<?php
session_start();
require_once '../includes/db.php';

// Auth and method check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/manage_entries.php?error=Invalid request');
    exit;
}

// Get common data
$entry_id = $_POST['entry_id'] ?? null;
$category_name = $_POST['category'] ?? '';
$title = $_POST['title'] ?? null;
// ... (get all other fields like in add_entry_handler)
$tmdb_id = !empty($_POST['tmdb_id']) ? (int)$_POST['tmdb_id'] : null;
$description = $_POST['description'] ?? null;
$poster_url = $_POST['poster_url'] ?? null;
$thumbnail_url = $_POST['thumbnail_url'] ?? null;
$rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : null;
$year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
$parental_rating = $_POST['parental_rating'] ?? null;
$country = $_POST['country'] ?? null;


// Validation
if (!$entry_id || !$category_name || !$title) {
    header('Location: ../admin/manage_entries.php?error=Missing required data');
    exit;
}

$pdo->beginTransaction();

try {
    // 1. Update the main 'entries' table
    $sql = "UPDATE entries SET
                tmdb_id = ?,
                title = ?,
                description = ?,
                poster_url = ?,
                thumbnail_url = ?,
                rating = ?,
                parental_rating = ?,
                country = ?,
                year = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $tmdb_id,
        $title,
        $description,
        $poster_url,
        $thumbnail_url,
        $rating,
        $parental_rating,
        $country,
        $year,
        $entry_id
    ]);

    // 2. Handle category-specific updates (Live TV servers)
    if ($category_name === 'Live TV') {
        $submitted_servers = $_POST['servers'] ?? [];
        $submitted_server_ids = [];

        // Update existing or insert new servers
        foreach ($submitted_servers as $key => $server_data) {
            if (empty($server_data['name']) || empty($server_data['url'])) continue;

            $is_new = strpos($key, 'new_') === 0;

            if ($is_new) {
                // Insert new server
                $sql = "INSERT INTO livetv_servers (entry_id, name, url, license_key) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$entry_id, $server_data['name'], $server_data['url'], $server_data['license'] ?? null]);
            } else {
                // Update existing server
                $server_id = $server_data['id'];
                $submitted_server_ids[] = $server_id;
                $sql = "UPDATE livetv_servers SET name = ?, url = ?, license_key = ? WHERE id = ? AND entry_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$server_data['name'], $server_data['url'], $server_data['license'] ?? null, $server_id, $entry_id]);
            }
        }

        // Delete servers that were removed
        if (count($submitted_server_ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($submitted_server_ids), '?'));
            $sql = "DELETE FROM livetv_servers WHERE entry_id = ? AND id NOT IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $params = array_merge([$entry_id], $submitted_server_ids);
            $stmt->execute($params);
        } else {
            // If no existing servers were submitted, delete all for this entry
            $sql = "DELETE FROM livetv_servers WHERE entry_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$entry_id]);
        }
    }

    // 3. Commit transaction
    $pdo->commit();
    header('Location: ../admin/manage_entries.php?success=Entry updated successfully!');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ../admin/edit_entry.php?id=' . $entry_id . '&error=Failed to update entry. ' . $e->getMessage());
    exit;
}
?>
