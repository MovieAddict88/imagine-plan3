<?php
session_start();
require_once '../includes/db.php';

// Check for authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get the ID from the URL
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: manage_entries.php?error=No ID specified');
    exit;
}

try {
    // The foreign key constraints with ON DELETE CASCADE will handle
    // deleting related records in livetv_servers, seasons, episodes, etc.
    $stmt = $pdo->prepare("DELETE FROM entries WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: manage_entries.php?success=Entry deleted successfully');
    exit;

} catch (PDOException $e) {
    header('Location: manage_entries.php?error=Failed to delete entry. ' . $e->getMessage());
    exit;
}
?>
