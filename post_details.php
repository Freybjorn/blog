<?php
// post_details.php
include 'config.php';
session_start();

if (!isset($_GET['post_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$post_id = $_GET['post_id'];
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 3;

// Получение информации о посте
$stmt = $pdo->prepare('SELECT posts.*, categories.name AS category_name, users.username AS author FROM posts
                       JOIN categories ON posts.category_id = categories.id
                       JOIN users ON posts.user_id = users.id
                       WHERE posts.id = :post_id');
$stmt->execute(['post_id' => $post_id]);
$post = $stmt->fetch();

// Получение комментариев к посту
$stmt = $pdo->prepare('SELECT comments.*, users.username AS author FROM comments
                       JOIN users ON comments.user_id = users.id
                       WHERE comments.post_id = :post_id
                       ORDER BY comments.created_at DESC
                       LIMIT :limit OFFSET :offset');
$stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll();

echo json_encode(['post' => $post, 'comments' => $comments]);
exit();
?>
