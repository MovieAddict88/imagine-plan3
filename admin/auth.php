<?php
session_start();
require_once '../includes/db.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Get form data
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header('Location: login.php?error=Username and password are required');
    exit;
}

try {
    // Find the user by username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Verify password
    if ($user && password_verify($password, $user['password'])) {
        // Password is correct, start the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Redirect to the admin dashboard
        header('Location: index.php');
        exit;
    } else {
        // Invalid credentials
        header('Location: login.php?error=Invalid username or password');
        exit;
    }

} catch (PDOException $e) {
    // In a production environment, log this error instead of displaying it.
    header('Location: login.php?error=A database error occurred.');
    // For debugging, you might want to see the error:
    // die($e->getMessage());
    exit;
}
?>
