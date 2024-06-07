<?php
// delete_comment.php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to delete a comment']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_id = $_POST['comment_id'];
    $user_id = $_SESSION['user_id'];

    // Проверка, является ли пользователь автором комментария
    $stmt = $pdo->prepare('SELECT user_id FROM comments WHERE id = :comment_id');
    $stmt->execute(['comment_id' => $comment_id]);
    $comment = $stmt->fetch();

    if ($comment && $comment['user_id'] == $user_id) {
        $stmt = $pdo->prepare('DELETE FROM comments WHERE id = :comment_id');
        $stmt->execute(['comment_id' => $comment_id]);

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'You can only delete your own comments']);
    }
    exit();
}
?>
