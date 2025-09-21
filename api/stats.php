<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

try {
    // Get category IDs first
    $stmt = $pdo->query("SELECT id, name FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $movies_id = array_search('Movies', $categories);
    $series_id = array_search('TV Series', $categories);
    $livetv_id = array_search('Live TV', $categories);

    // Fetch counts
    $stmt_movies = $pdo->prepare("SELECT COUNT(*) FROM entries WHERE category_id = ?");
    $stmt_movies->execute([$movies_id]);
    $total_movies = $stmt_movies->fetchColumn();

    $stmt_series = $pdo->prepare("SELECT COUNT(*) FROM entries WHERE category_id = ?");
    $stmt_series->execute([$series_id]);
    $total_series = $stmt_series->fetchColumn();

    $stmt_livetv = $pdo->prepare("SELECT COUNT(*) FROM entries WHERE category_id = ?");
    $stmt_livetv->execute([$livetv_id]);
    $total_livetv = $stmt_livetv->fetchColumn();

    $total_entries = $total_movies + $total_series + $total_livetv;

    // Fetch data for the chart (entries in the last 7 days)
    $chart_labels = [];
    $chart_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = date('M d', strtotime($date));

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM entries WHERE DATE(created_at) = ?");
        $stmt->execute([$date]);
        $chart_data[] = $stmt->fetchColumn();
    }

    // Prepare the response
    $response = [
        'success' => true,
        'data' => [
            'total_movies' => $total_movies,
            'total_series' => $total_series,
            'total_livetv' => $total_livetv,
            'total_entries' => $total_entries,
            'chart' => [
                'labels' => $chart_labels,
                'data' => $chart_data
            ]
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
