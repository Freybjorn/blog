<?php
// like.php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to like or dislike a post.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];
$like_type = $_POST['like_type'];

// Проверка на наличие предыдущего лайка или дизлайка
$stmt = $pdo->prepare('SELECT like_type FROM likes WHERE post_id = :post_id AND user_id = :user_id');
$stmt->execute(['post_id' => $post_id, 'user_id' => $user_id]);
$existing_like = $stmt->fetch();

if ($existing_like) {
    // Удаление предыдущего лайка или дизлайка
    $stmt = $pdo->prepare('DELETE FROM likes WHERE post_id = :post_id AND user_id = :user_id');
    $stmt->execute(['post_id' => $post_id, 'user_id' => $user_id]);

    // Обновление счетчиков лайков и дизлайков
    if ($existing_like['like_type'] == 'like') {
        $stmt = $pdo->prepare('UPDATE posts SET likes = likes - 1 WHERE id = :post_id');
    } else {
        $stmt = $pdo->prepare('UPDATE posts SET dislikes = dislikes - 1 WHERE id = :post_id');
    }
    $stmt->execute(['post_id' => $post_id]);
}

// Добавление нового лайка или дизлайка
$stmt = $pdo->prepare('INSERT INTO likes (post_id, user_id, like_type) VALUES (:post_id, :user_id, :like_type)');
$stmt->execute(['post_id' => $post_id, 'user_id' => $user_id, 'like_type' => $like_type]);

if ($like_type == 'like') {
    $stmt = $pdo->prepare('UPDATE posts SET likes = likes + 1 WHERE id = :post_id');
} else {
    $stmt = $pdo->prepare('UPDATE posts SET dislikes = dislikes + 1 WHERE id = :post_id');
}
$stmt->execute(['post_id' => $post_id]);

// Возвращаем обновленные значения лайков и дизлайков
$stmt = $pdo->prepare('SELECT likes, dislikes FROM posts WHERE id = :post_id');
$stmt->execute(['post_id' => $post_id]);
$post = $stmt->fetch();

echo json_encode(['status' => 'success', 'likes' => $post['likes'], 'dislikes' => $post['dislikes']]);
exit();
?>
