<?php
include 'includes/header.php';
require_once '../includes/db.php';

// Handle the POST request to delete duplicates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_duplicates'])) {
    $tmdb_id_to_clean = $_POST['tmdb_id'] ?? null;
    if ($tmdb_id_to_clean) {
        // Find all entries for this tmdb_id, ordered by creation date
        $stmt = $pdo->prepare("SELECT id FROM entries WHERE tmdb_id = ? ORDER BY created_at ASC");
        $stmt->execute([$tmdb_id_to_clean]);
        $entries = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Keep the first one, delete the rest
        $entry_to_keep = array_shift($entries);

        if (count($entries) > 0) {
            $placeholders = implode(',', array_fill(0, count($entries), '?'));
            $delete_sql = "DELETE FROM entries WHERE id IN ($placeholders)";
            $delete_stmt = $pdo->prepare($delete_sql);
            $delete_stmt->execute($entries);
            $deleted_count = $delete_stmt->rowCount();
            $success_message = "Cleaned up duplicates for TMDb ID $tmdb_id_to_clean. Kept original entry and deleted $deleted_count duplicates.";
        } else {
            $error_message = "No duplicates found to delete for TMDb ID $tmdb_id_to_clean.";
        }
    }
}


// Find all tmdb_ids that have more than one entry
$sql = "SELECT tmdb_id, COUNT(*) as count
        FROM entries
        WHERE tmdb_id IS NOT NULL
        GROUP BY tmdb_id
        HAVING COUNT(*) > 1";
$stmt = $pdo->query($sql);
$duplicate_groups = $stmt->fetchAll();

?>

<h1 class="mt-4">Remove Duplicates</h1>
<p>This page identifies entries that share the same TMDb ID. You can clean them up, keeping only the first-created entry for each set.</p>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>
<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>


<div class="card">
    <div class="card-header">Duplicate Sets</div>
    <div class="card-body">
        <?php if (count($duplicate_groups) > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>TMDb ID</th>
                        <th>Duplicate Count</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($duplicate_groups as $group): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($group['tmdb_id']); ?></td>
                            <td><?php echo htmlspecialchars($group['count']); ?></td>
                            <td>
                                <form method="POST" action="remove_duplicates.php" style="display:inline;">
                                    <input type="hidden" name="tmdb_id" value="<?php echo $group['tmdb_id']; ?>">
                                    <button type="submit" name="delete_duplicates" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure? This will delete all but the first entry for this ID.');">
                                        Clean Duplicates
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center">No duplicate entries found based on TMDb ID.</p>
        <?php endif; ?>
    </div>
</div>


<?php
include 'includes/footer.php';
?>
