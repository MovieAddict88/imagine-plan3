<?php
header('Content-Type: application/json');
set_time_limit(300); // 5 minutes for long operations like fetching a full series

require_once '../includes/db.php';

$log = [];

function api_call($url) {
    global $log;
    $log[] = "Calling TMDb API: $url";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

try {
    $api_key = $_GET['api_key'] ?? null;
    $type = $_GET['type'] ?? null;
    $tmdb_id = $_GET['tmdb_id'] ?? null;

    if (!$api_key || !$type || !$tmdb_id) {
        throw new Exception("API Key, Type, and TMDb ID are required.");
    }

    // Check for duplicates before making any API calls
    $stmt = $pdo->prepare("SELECT id FROM entries WHERE tmdb_id = ?");
    $stmt->execute([$tmdb_id]);
    if ($stmt->fetch()) {
        throw new Exception("Error: An entry with TMDb ID $tmdb_id already exists.");
    }

    $base_url = "https://api.themoviedb.org/3";
    $poster_base = "https://image.tmdb.org/t/p/w500";

    if ($type === 'movie') {
        // --- MOVIE WORKFLOW ---
        $url = "$base_url/movie/$tmdb_id?api_key=$api_key";
        $data = api_call($url);

        if (isset($data['success']) && $data['success'] === false) {
            throw new Exception("TMDb API Error: " . ($data['status_message'] ?? 'Unknown error'));
        }

        $pdo->beginTransaction();

        // Get Movie Category ID
        $cat_stmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'Movies'");
        $cat_stmt->execute();
        $category_id = $cat_stmt->fetchColumn();

        // Insert movie entry
        $sql = "INSERT INTO entries (tmdb_id, category_id, title, description, poster_url, thumbnail_url, rating, year, parental_rating)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['id'],
            $category_id,
            $data['title'],
            $data['overview'],
            $poster_base . $data['poster_path'],
            $poster_base . $data['backdrop_path'],
            $data['vote_average'],
            date('Y', strtotime($data['release_date'])),
            'N/A' // Parental rating not easily available in main movie call
        ]);
        $entry_id = $pdo->lastInsertId();
        $log[] = "Successfully inserted movie '{$data['title']}' with ID $entry_id.";

        // Get enabled movie servers
        $server_stmt = $pdo->prepare("SELECT id, url_template FROM servers WHERE type = 'movie' AND is_enabled = TRUE");
        $server_stmt->execute();
        $servers = $server_stmt->fetchAll();

        // Insert server links
        $link_sql = "INSERT INTO entry_servers (entry_id, server_id, url) VALUES (?, ?, ?)";
        $link_stmt = $pdo->prepare($link_sql);
        foreach ($servers as $server) {
            $final_url = str_replace('{tmdb_id}', $data['id'], $server['url_template']);
            $link_stmt->execute([$entry_id, $server['id'], $final_url]);
        }
        $log[] = "Added " . count($servers) . " server links.";

        $pdo->commit();
        $log[] = "Generation complete!";

    } elseif ($type === 'tv') {
        // --- TV SERIES WORKFLOW ---
        $url = "$base_url/tv/$tmdb_id?api_key=$api_key";
        $data = api_call($url);

        if (isset($data['success']) && $data['success'] === false) {
            throw new Exception("TMDb API Error: " . ($data['status_message'] ?? 'Unknown error'));
        }

        $pdo->beginTransaction();

        $cat_stmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'TV Series'");
        $cat_stmt->execute();
        $category_id = $cat_stmt->fetchColumn();

        // Insert main TV series entry
        $sql = "INSERT INTO entries (tmdb_id, category_id, title, description, poster_url, thumbnail_url, rating, year)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['id'], $category_id, $data['name'], $data['overview'],
            $poster_base . $data['poster_path'], $poster_base . $data['backdrop_path'],
            $data['vote_average'], date('Y', strtotime($data['first_air_date']))
        ]);
        $entry_id = $pdo->lastInsertId();
        $log[] = "Successfully inserted TV Series '{$data['name']}' with ID $entry_id.";

        // Get enabled TV servers
        $server_stmt = $pdo->prepare("SELECT id, url_template FROM servers WHERE type = 'tv' AND is_enabled = TRUE");
        $server_stmt->execute();
        $servers = $server_stmt->fetchAll();
        $log[] = "Found " . count($servers) . " enabled TV servers.";

        // Loop through seasons
        foreach ($data['seasons'] as $season_data) {
            // Skip "Specials" season
            if ($season_data['season_number'] == 0) continue;

            $season_sql = "INSERT INTO seasons (entry_id, season_number, name, poster_url) VALUES (?, ?, ?, ?)";
            $season_stmt = $pdo->prepare($season_sql);
            $season_stmt->execute([
                $entry_id, $season_data['season_number'], $season_data['name'], $poster_base . $season_data['poster_path']
            ]);
            $season_id = $pdo->lastInsertId();
            $log[] = "  - Added Season {$season_data['season_number']} with ID $season_id.";

            // Get episodes for this season
            $ep_url = "$base_url/tv/$tmdb_id/season/{$season_data['season_number']}?api_key=$api_key";
            $ep_data = api_call($ep_url);

            $ep_sql = "INSERT INTO episodes (season_id, episode_number, title, description, thumbnail_url) VALUES (?, ?, ?, ?, ?)";
            $ep_stmt = $pdo->prepare($ep_sql);
            $link_sql = "INSERT INTO entry_servers (episode_id, server_id, url) VALUES (?, ?, ?)";
            $link_stmt = $pdo->prepare($link_sql);

            foreach ($ep_data['episodes'] as $episode) {
                $ep_stmt->execute([
                    $season_id, $episode['episode_number'], $episode['name'], $episode['overview'], $poster_base . $episode['still_path']
                ]);
                $episode_id = $pdo->lastInsertId();

                // Add server links for the episode
                foreach ($servers as $server) {
                    $final_url = str_replace(
                        ['{tmdb_id}', '{season}', '{episode}'],
                        [$tmdb_id, $season_data['season_number'], $episode['episode_number']],
                        $server['url_template']
                    );
                    $link_stmt->execute([$episode_id, $server['id'], $final_url]);
                }
            }
            $log[] = "    - Added {$ep_data['episode_count']} episodes with server links.";
        }

        $pdo->commit();
        $log[] = "Generation complete!";
    } else {
        throw new Exception("Invalid type specified.");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $log[] = "FATAL ERROR: " . $e->getMessage();
}

echo json_encode(['log' => $log]);
?>
