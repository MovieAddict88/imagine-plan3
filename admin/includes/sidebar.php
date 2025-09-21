<div class="bg-dark border-right" id="sidebar-wrapper">
    <div class="sidebar-heading text-white">Admin Panel</div>
    <div class="list-group list-group-flush">
        <a href="index.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        <a href="manage_entries.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo ($current_page == 'manage_entries.php') ? 'active' : ''; ?>">
            <i class="fas fa-film me-2"></i>Manage Entries
        </a>
        <a href="add_entry.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo ($current_page == 'add_entry.php') ? 'active' : ''; ?>">
            <i class="fas fa-plus me-2"></i>Add New Entry
        </a>
        <a href="bulk_entry.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo ($current_page == 'bulk_entry.php') ? 'active' : ''; ?>">
            <i class="fas fa-file-import me-2"></i>Bulk Entry
        </a>
    </div>
</div>
