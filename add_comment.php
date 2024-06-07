<?php
// add_comment.php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to comment']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'];
    $comment = $_POST['comment'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, comment) VALUES (:post_id, :user_id, :comment)');
    $stmt->execute(['post_id' => $post_id, 'user_id' => $user_id, 'comment' => $comment]);

    $comment_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT comments.*, users.username AS author FROM comments
                           JOIN users ON comments.user_id = users.id
                           WHERE comments.id = :id');
    $stmt->execute(['id' => $comment_id]);
    $new_comment = $stmt->fetch();

    echo json_encode(['status' => 'success', 'comment' => $new_comment]);
    exit();
}
?>
