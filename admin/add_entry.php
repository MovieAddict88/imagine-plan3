<?php
include 'includes/header.php';
?>

<h1 class="mt-4">Add New Entry</h1>
<p>Select a category and fill in the details to add a new entry to the database.</p>

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

<!-- Navigation Tabs -->
<ul class="nav nav-tabs" id="entryTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="movie-tab" data-bs-toggle="tab" data-bs-target="#movie" type="button" role="tab" aria-controls="movie" aria-selected="true">Movie</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="series-tab" data-bs-toggle="tab" data-bs-target="#series" type="button" role="tab" aria-controls="series" aria-selected="false">TV Series</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="livetv-tab" data-bs-toggle="tab" data-bs-target="#livetv" type="button" role="tab" aria-controls="livetv" aria-selected="false">Live TV</button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="entryTabContent">
    <!-- Movie Tab -->
    <div class="tab-pane fade show active" id="movie" role="tabpanel" aria-labelledby="movie-tab">
        <form action="../api/add_entry_handler.php" method="POST" class="p-3 border border-top-0">
            <input type="hidden" name="category" value="Movies">
            <div class="mb-3">
                <label for="movie_title" class="form-label">Title</label>
                <input type="text" class="form-control" id="movie_title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="movie_tmdb_id" class="form-label">TMDb ID (Optional)</label>
                <input type="number" class="form-control" id="movie_tmdb_id" name="tmdb_id">
                 <div class="form-text">Provide a TMDb ID to allow for automatic server linking later.</div>
            </div>
            <div class="mb-3">
                <label for="movie_description" class="form-label">Description</label>
                <textarea class="form-control" id="movie_description" name="description" rows="3"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="movie_poster" class="form-label">Poster URL</label>
                    <input type="url" class="form-control" id="movie_poster" name="poster_url">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="movie_thumbnail" class="form-label">Thumbnail URL</label>
                    <input type="url" class="form-control" id="movie_thumbnail" name="thumbnail_url">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="movie_rating" class="form-label">Rating (e.g., 8.5)</label>
                    <input type="number" step="0.1" class="form-control" id="movie_rating" name="rating">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="movie_year" class="form-label">Year</label>
                    <input type="number" class="form-control" id="movie_year" name="year">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="movie_parental_rating" class="form-label">Parental Rating</label>
                    <input type="text" class="form-control" id="movie_parental_rating" name="parental_rating">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Add Movie</button>
        </form>
    </div>

    <!-- TV Series Tab -->
    <div class="tab-pane fade" id="series" role="tabpanel" aria-labelledby="series-tab">
        <div class="p-3 border border-top-0">
            <p class="text-muted">Manual creation of TV Series with seasons and episodes is complex. Please use the <strong>Bulk Entry > TMDb ID</strong> feature to automatically generate a full TV series with all its seasons and episodes.</p>
            <a href="bulk_entry.php" class="btn btn-primary">Go to Bulk Entry</a>
        </div>
    </div>

    <!-- Live TV Tab -->
    <div class="tab-pane fade" id="livetv" role="tabpanel" aria-labelledby="livetv-tab">
        <form action="../api/add_entry_handler.php" method="POST" class="p-3 border border-top-0">
            <input type="hidden" name="category" value="Live TV">
            <div class="mb-3">
                <label for="livetv_title" class="form-label">Channel Name</label>
                <input type="text" class="form-control" id="livetv_title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="livetv_description" class="form-label">Description</label>
                <textarea class="form-control" id="livetv_description" name="description" rows="3"></textarea>
            </div>
            <div class="row">
                 <div class="col-md-6 mb-3">
                    <label for="livetv_poster" class="form-label">Poster URL</label>
                    <input type="url" class="form-control" id="livetv_poster" name="poster_url">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="livetv_thumbnail" class="form-label">Thumbnail URL</label>
                    <input type="url" class="form-control" id="livetv_thumbnail" name="thumbnail_url">
                </div>
            </div>
             <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="livetv_country" class="form-label">Country</label>
                    <input type="text" class="form-control" id="livetv_country" name="country">
                </div>
                 <div class="col-md-6 mb-3">
                    <label for="livetv_year" class="form-label">Year</label>
                    <input type="number" class="form-control" id="livetv_year" name="year">
                </div>
            </div>
            <hr>
            <h5>Servers</h5>
            <div id="livetv-servers-container">
                <!-- Server inputs will be added here dynamically -->
            </div>
            <button type="button" class="btn btn-secondary mt-2" id="add-server-btn">Add Server</button>
            <hr>
            <button type="submit" class="btn btn-primary">Add Live TV Channel</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('livetv-servers-container');
    const addBtn = document.getElementById('add-server-btn');
    let serverCount = 0;

    addBtn.addEventListener('click', function() {
        serverCount++;
        const serverDiv = document.createElement('div');
        serverDiv.classList.add('row', 'mb-3', 'align-items-end');
        serverDiv.innerHTML = `
            <div class="col-md-3">
                <label class="form-label">Server Name</label>
                <input type="text" name="servers[${serverCount}][name]" class="form-control" placeholder="e.g., HD">
            </div>
            <div class="col-md-5">
                <label class="form-label">Stream URL</label>
                <input type="text" name="servers[${serverCount}][url]" class="form-control" placeholder="https://...">
            </div>
            <div class="col-md-3">
                <label class="form-label">License Key</label>
                <input type="text" name="servers[${serverCount}][license]" class="form-control" placeholder="(Optional)">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger remove-server-btn">X</button>
            </div>
        `;
        container.appendChild(serverDiv);
    });

    container.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-server-btn')) {
            e.target.closest('.row').remove();
        }
    });

    // Add one server field by default
    addBtn.click();
});
</script>

<?php
include 'includes/footer.php';
?>
