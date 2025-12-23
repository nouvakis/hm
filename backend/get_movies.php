<?php
/**
 * backend/get_movies.php
 */

require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../lib/functions.php';

startSessionSafe();
$userID = $_SESSION['user_id'] ?? null;

$search_query = "";
$params = [];

// 1. Ρυθμίσεις Σελιδοποίησης
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], [20, 50, 100]) ? (int)$_GET['limit'] : 20;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    // 2. Διαχείριση Αναζήτησης
    if (isset($_GET['q']) && !empty($_GET['q'])) {
        $search_query = trim($_GET['q']);
        
        // Παράμετροι για την Prefix Search λογική (αρχή ονόματος ή επιθέτου)
        $titleParam = "%$search_query%";      // Οπουδήποτε στον τίτλο
        $firstNameParam = "$search_query%";   // Αρχή ονόματος
        $lastNameParam = "% $search_query%";  // Αρχή επιθέτου (μετά από κενό)

        // SQL συνθήκη για ηθοποιούς (Top 10 & jobID 1)
        $actorCondition = "(p.name LIKE ? OR p.name LIKE ?) AND mc.jobID = 1 AND mc.actor_order < 10";
        
        // α) Query για το Count (DISTINCT για να μην μετράει διπλά τις ταινίες)
        $countSql = "SELECT COUNT(DISTINCT m.id) FROM movies m
                     LEFT JOIN moviecrew mc ON m.id = mc.movieID
                     LEFT JOIN people p ON mc.personID = p.id
                     WHERE m.title LIKE ? OR ($actorCondition)";
        
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute([$titleParam, $firstNameParam, $lastNameParam]);
        $totalMovies = $stmtCount->fetchColumn();

        // β) Κύριο Query με DISTINCT και Like Logic
        $sql = "SELECT DISTINCT m.id, m.title, m.release_date, m.poster_path, m.TMDB_vote_average,
                (SELECT COUNT(*) FROM movie_likes WHERE movieID = m.id) as likes_count 
                FROM movies m
                LEFT JOIN moviecrew mc ON m.id = mc.movieID
                LEFT JOIN people p ON mc.personID = p.id
                WHERE m.title LIKE ? OR ($actorCondition)
                ORDER BY m.release_date DESC LIMIT $limit OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titleParam, $firstNameParam, $lastNameParam]);
        
    } else {
        // 3. Απλή Προβολή (χωρίς αναζήτηση)
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM movies");
        $totalMovies = $stmtCount->fetchColumn();

        $sql = "SELECT m.id, m.title, m.release_date, m.poster_path, m.TMDB_vote_average,
                (SELECT COUNT(*) FROM movie_likes WHERE movieID = m.id) as likes_count 
                FROM movies m
                ORDER BY m.release_date DESC LIMIT $limit OFFSET $offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $movies = $stmt->fetchAll();
    $totalPages = ceil($totalMovies / $limit);

} catch (Exception $e) {
    $error = "Σφάλμα φόρτωσης ταινιών: " . $e->getMessage();
    $movies = [];
    $totalMovies = 0;
    $totalPages = 0;
}

$img_base_url = "https://image.tmdb.org/t/p/w500";
?>