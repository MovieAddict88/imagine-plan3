<?php
include 'includes/header.php';
require_once '../includes/db.php';

$entry_id = $_GET['id'] ?? null;
if (!$entry_id) {
    echo "<div class='alert alert-danger'>No entry ID specified.</div>";
    include 'includes/footer.php';
    exit;
}

// Fetch the entry and its category
$stmt = $pdo->prepare("SELECT e.*, c.name as category_name FROM entries e JOIN categories c ON e.category_id = c.id WHERE e.id = ?");
$stmt->execute([$entry_id]);
$entry = $stmt->fetch();

if (!$entry) {
    echo "<div class='alert alert-danger'>Entry not found.</div>";
    include 'includes/footer.php';
    exit;
}

// Fetch related data if it's a Live TV entry
$livetv_servers = [];
if ($entry['category_name'] === 'Live TV') {
    $stmt = $pdo->prepare("SELECT * FROM livetv_servers WHERE entry_id = ?");
    $stmt->execute([$entry_id]);
    $livetv_servers = $stmt->fetchAll();
}

?>

<h1 class="mt-4">Edit Entry: <?php echo htmlspecialchars($entry['title']); ?></h1>
<p>Update the details for this entry below.</p>

<!-- Form for editing. We only show the relevant form based on the category -->
<form action="../api/edit_entry_handler.php" method="POST" class="p-3 border bg-light">
    <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
    <input type="hidden" name="category" value="<?php echo $entry['category_name']; ?>">

    <?php if ($entry['category_name'] === 'Movies'): ?>
        <!-- MOVIE FIELDS -->
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($entry['title']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">TMDb ID</label>
            <input type="number" class="form-control" name="tmdb_id" value="<?php echo htmlspecialchars($entry['tmdb_id']); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($entry['description']); ?></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label>Poster URL</label><input type="url" class="form-control" name="poster_url" value="<?php echo htmlspecialchars($entry['poster_url']); ?>"></div>
            <div class="col-md-6 mb-3"><label>Thumbnail URL</label><input type="url" class="form-control" name="thumbnail_url" value="<?php echo htmlspecialchars($entry['thumbnail_url']); ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label>Rating</label><input type="number" step="0.1" class="form-control" name="rating" value="<?php echo htmlspecialchars($entry['rating']); ?>"></div>
            <div class="col-md-4 mb-3"><label>Year</label><input type="number" class="form-control" name="year" value="<?php echo htmlspecialchars($entry['year']); ?>"></div>
            <div class="col-md-4 mb-3"><label>Parental Rating</label><input type="text" class="form-control" name="parental_rating" value="<?php echo htmlspecialchars($entry['parental_rating']); ?>"></div>
        </div>

    <?php elseif ($entry['category_name'] === 'Live TV'): ?>
        <!-- LIVE TV FIELDS -->
        <div class="mb-3">
            <label class="form-label">Channel Name</label>
            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($entry['title']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($entry['description']); ?></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label>Poster URL</label><input type="url" class="form-control" name="poster_url" value="<?php echo htmlspecialchars($entry['poster_url']); ?>"></div>
            <div class="col-md-6 mb-3"><label>Thumbnail URL</label><input type="url" class="form-control" name="thumbnail_url" value="<?php echo htmlspecialchars($entry['thumbnail_url']); ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label>Country</label><input type="text" class="form-control" name="country" value="<?php echo htmlspecialchars($entry['country']); ?>"></div>
            <div class="col-md-6 mb-3"><label>Year</label><input type="number" class="form-control" name="year" value="<?php echo htmlspecialchars($entry['year']); ?>"></div>
        </div>
        <hr>
        <h5>Servers</h5>
        <div id="livetv-servers-container">
            <?php foreach ($livetv_servers as $index => $server): ?>
                <div class="row mb-3 align-items-end">
                    <input type="hidden" name="servers[<?php echo $index; ?>][id]" value="<?php echo $server['id']; ?>">
                    <div class="col-md-3"><label class="form-label">Server Name</label><input type="text" name="servers[<?php echo $index; ?>][name]" class="form-control" value="<?php echo htmlspecialchars($server['name']); ?>"></div>
                    <div class="col-md-5"><label class="form-label">Stream URL</label><input type="text" name="servers[<?php echo $index; ?>][url]" class="form-control" value="<?php echo htmlspecialchars($server['url']); ?>"></div>
                    <div class="col-md-3"><label class="form-label">License Key</label><input type="text" name="servers[<?php echo $index; ?>][license]" class="form-control" value="<?php echo htmlspecialchars($server['license_key']); ?>"></div>
                    <div class="col-md-1"><button type="button" class="btn btn-danger remove-server-btn">X</button></div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-secondary mt-2" id="add-server-btn">Add Server</button>

    <?php else: ?>
        <div class="alert alert-warning">Editing for this category (e.g., TV Series) should be done via the Bulk Entry tools for better data consistency.</div>
    <?php endif; ?>

    <hr>
    <button type="submit" class="btn btn-success">Save Changes</button>
    <a href="manage_entries.php" class="btn btn-secondary">Cancel</a>
</form>

<!-- JS is the same as add_entry.php, can be refactored later -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('livetv-servers-container');
    if (!container) return; // Don't run if not on Live TV edit page

    const addBtn = document.getElementById('add-server-btn');
    let serverCount = <?php echo count($livetv_servers); ?>;

    addBtn.addEventListener('click', function() {
        serverCount++;
        const serverDiv = document.createElement('div');
        serverDiv.classList.add('row', 'mb-3', 'align-items-end');
        // Use a unique index for new servers to avoid conflicts
        const newIndex = 'new_' + serverCount;
        serverDiv.innerHTML = `
            <div class="col-md-3"><label class="form-label">Server Name</label><input type="text" name="servers[${newIndex}][name]" class="form-control" placeholder="e.g., HD"></div>
            <div class="col-md-5"><label class="form-label">Stream URL</label><input type="text" name="servers[${newIndex}][url]" class="form-control" placeholder="https://..."></div>
            <div class="col-md-3"><label class="form-label">License Key</label><input type="text" name="servers[${newIndex}][license]" class="form-control" placeholder="(Optional)"></div>
            <div class="col-md-1"><button type="button" class="btn btn-danger remove-server-btn">X</button></div>
        `;
        container.appendChild(serverDiv);
    });

    container.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-server-btn')) {
            e.target.closest('.row').remove();
        }
    });
});
</script>

<?php
include 'includes/footer.php';
?>
