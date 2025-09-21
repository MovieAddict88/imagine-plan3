<?php
include 'includes/header.php';
?>

<h1 class="mt-4">Bulk Entry</h1>
<p>Automatically generate entries by fetching data from The Movie Database (TMDb).</p>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs" id="bulkEntryTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tmdb-id-tab" data-bs-toggle="tab" data-bs-target="#tmdb-id" type="button" role="tab" aria-controls="tmdb-id" aria-selected="true">Generate by TMDb ID</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="regional-tab" data-bs-toggle="tab" data-bs-target="#regional" type="button" role="tab" aria-controls="regional" aria-selected="false">Regional Generation</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="genre-tab" data-bs-toggle="tab" data-bs-target="#genre" type="button" role="tab" aria-controls="genre" aria-selected="false">Genre Generation</button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="bulkEntryTabContent">
    <!-- TMDb ID Generation Tab -->
    <div class="tab-pane fade show active" id="tmdb-id" role="tabpanel" aria-labelledby="tmdb-id-tab">
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Generate a single Movie or TV Series from its TMDb ID</h5>
                <form id="tmdb-id-form">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="api_key_select" class="form-label">TMDb API Key</label>
                            <select class="form-select" id="api_key_select" name="api_key">
                                <option value="<?php echo TMDB_API_KEY_1; ?>">API Key 1</option>
                                <option value="<?php echo TMDB_API_KEY_2; ?>">API Key 2</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="media_type" class="form-label">Media Type</label>
                            <select class="form-select" id="media_type" name="type">
                                <option value="movie">Movie</option>
                                <option value="tv">TV Series</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="tmdb_id_input" class="form-label">TMDb ID</label>
                            <input type="text" class="form-control" id="tmdb_id_input" name="tmdb_id" placeholder="e.g., 550" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Generate Entry
                    </button>
                </form>
                <hr>
                <h6>Logs:</h6>
                <pre id="tmdb-id-logs" class="bg-dark text-white p-3 rounded" style="min-height: 200px; max-height: 400px; overflow-y: auto;"></pre>
            </div>
        </div>
    </div>

    <!-- Regional Generation Tab -->
    <div class="tab-pane fade" id="regional" role="tabpanel" aria-labelledby="regional-tab">
         <div class="card mt-3">
            <div class="card-body">
                <p class="text-muted">This feature is not yet implemented.</p>
            </div>
        </div>
    </div>

    <!-- Genre Generation Tab -->
    <div class="tab-pane fade" id="genre" role="tabpanel" aria-labelledby="genre-tab">
         <div class="card mt-3">
            <div class="card-body">
                <p class="text-muted">This feature is not yet implemented.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('tmdb-id-form');
    const logs = document.getElementById('tmdb-id-logs');
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        logs.textContent = 'Starting generation...';
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');

        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();

        fetch(`../api/generate_from_tmdb.php?${params}`)
            .then(response => response.json())
            .then(result => {
                logs.textContent = result.log.join('\\n');
            })
            .catch(error => {
                logs.textContent = 'An error occurred: ' + error;
            })
            .finally(() => {
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
            });
    });
});
</script>

<?php
include 'includes/footer.php';
?>
