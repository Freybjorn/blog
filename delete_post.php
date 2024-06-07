<?php
// delete_post.php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$post_id = $_GET['id'];

$stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id AND user_id = :user_id');
$stmt->execute(['id' => $post_id, 'user_id' => $_SESSION['user_id']]);

header('Location: index.php');
exit();
?>
