<?php
include 'includes/header.php';
require_once '../includes/db.php';

// --- Pagination Logic ---
$limit = 15; // Entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- Filtering Logic ---
$filter_category = $_GET['category'] ?? '';
$where_clauses = [];
$params = [];

if (!empty($filter_category)) {
    $where_clauses[] = 'c.name = ?';
    $params[] = $filter_category;
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// --- Get Total Entries for Pagination ---
$total_sql = "SELECT COUNT(e.id) FROM entries e JOIN categories c ON e.category_id = c.id $where_sql";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($params);
$total_entries = $total_stmt->fetchColumn();
$total_pages = ceil($total_entries / $limit);


// --- Fetch Entries for the Current Page ---
$sql = "SELECT e.id, e.title, e.year, e.thumbnail_url, c.name as category_name
        FROM entries e
        JOIN categories c ON e.category_id = c.id
        $where_sql
        ORDER BY e.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$params[] = $limit;
$params[] = $offset;
// We need to bind params carefully due to mixed types
foreach($params as $key => $param) {
    $stmt->bindValue($key + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$entries = $stmt->fetchAll();

?>

<h1 class="mt-4">Manage Entries</h1>
<p>Here you can view, edit, and delete all content entries.</p>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <span>All Entries</span>
            <a href="remove_duplicates.php" class="btn btn-warning"><i class="fas fa-exclamation-triangle me-2"></i>Remove Duplicates</a>
        </div>
    </div>
    <div class="card-body">
        <form action="bulk_delete.php" method="POST"> <!-- TODO: Create bulk_delete.php -->
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;"><input type="checkbox" id="select-all"></th>
                        <th style="width: 10%;">Preview</th>
                        <th>Title</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 10%;">Year</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($entries) > 0): ?>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><input type="checkbox" name="entry_ids[]" value="<?php echo $entry['id']; ?>"></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($entry['thumbnail_url'] ?: '../assets/images/placeholder.png'); ?>" alt="Thumbnail" style="width: 100px; height: auto;">
                                </td>
                                <td><?php echo htmlspecialchars($entry['title']); ?></td>
                                <td><?php echo htmlspecialchars($entry['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($entry['year']); ?></td>
                                <td>
                                    <a href="edit_entry.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="delete_entry.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this entry?');"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No entries found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete the selected entries?');">Delete Selected</button>
        </form>
    </div>
    <div class="card-footer">
        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('input[name="entry_ids[]"]');

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    });
});
</script>

<?php
include 'includes/footer.php';
?>
