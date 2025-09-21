<?php
session_start();
require_once '../includes/db.php';

// Check for authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_entries.php?error=Invalid request');
    exit;
}

// Get the array of IDs
$entry_ids = $_POST['entry_ids'] ?? [];

if (empty($entry_ids)) {
    header('Location: manage_entries.php?error=No entries selected for deletion');
    exit;
}

try {
    // Create a string of placeholders (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($entry_ids), '?'));

    // The foreign key constraints with ON DELETE CASCADE will handle related records.
    $sql = "DELETE FROM entries WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($entry_ids);

    $count = $stmt->rowCount();
    header("Location: manage_entries.php?success=$count entries deleted successfully");
    exit;

} catch (PDOException $e) {
    header('Location: manage_entries.php?error=Failed to delete entries. ' . $e->getMessage());
    exit;
}
?>
