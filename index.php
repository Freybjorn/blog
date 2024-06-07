<?php
// index.php
include 'config.php';
session_start();

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'title_asc';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$categories_stmt = $pdo->query('SELECT * FROM categories');
$categories = $categories_stmt->fetchAll();

$query = 'SELECT posts.*, categories.name AS category_name, users.username AS author FROM posts
          JOIN categories ON posts.category_id = categories.id
          JOIN users ON posts.user_id = users.id';

if ($category > 0) {
    $query .= ' WHERE posts.category_id = :category';
}

switch ($sort) {
    case 'title_asc':
        $query .= ' ORDER BY posts.title ASC';
        break;
    case 'title_desc':
        $query .= ' ORDER BY posts.title DESC';
        break;
    case 'date_asc':
        $query .= ' ORDER BY posts.created_at ASC';
        break;
    case 'date_desc':
        $query .= ' ORDER BY posts.created_at DESC';
        break;
}

$query .= ' LIMIT :limit OFFSET :offset';

$stmt = $pdo->prepare($query);

if ($category > 0) {
    $stmt->bindParam(':category', $category, PDO::PARAM_INT);
}

$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$total_query = 'SELECT COUNT(*) FROM posts';
if ($category > 0) {
    $total_query .= ' WHERE category_id = :category';
}
$total_stmt = $pdo->prepare($total_query);

if ($category > 0) {
    $total_stmt->bindParam(':category', $category, PDO::PARAM_INT);
}

$total_stmt->execute();
$total_posts = $total_stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Блог</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
<h1 class="my-4">Блог</h1>

    <?php if (isset($_SESSION['username'])): ?>
        <p>Добро пожаловать, <?= htmlspecialchars($_SESSION['username']) ?>! <a href="logout.php" class="btn btn-danger">Выйти из системы</a></p>
        <a href="create_post.php" class="btn btn-primary mb-4">Создать новый пост</a>
    <?php else: ?>
        <p><a href="login.php" class="btn btn-primary">Войдите</a> или <a href="register.php" class="btn btn-secondary">Зарегистрируйтесь</a></p>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <div class="form-row">
            <div class="col">
                <select name="sort" class="form-control">
                    <option value="title_asc" <?= $sort == 'title_asc' ? 'selected' : '' ?>>Сортировать по названию (A-Я)</option>
                    <option value="title_desc" <?= $sort == 'title_desc' ? 'selected' : '' ?>>Сортировать по названию  (Я-A)</option>
                    <option value="date_asc" <?= $sort == 'date_asc' ? 'selected' : '' ?>>Сортировать по дате (Старые)</option>
                    <option value="date_desc" <?= $sort == 'date_desc' ? 'selected' : '' ?>>Сортировать по дате (Новые)</option>
                </select>
            </div>
            <div class="col">
                <select name="category" class="form-control">
                    <option value="0">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col">
                <button type="submit" class="btn btn-primary">Применить</button>
            </div>
        </div>
    </form>

    <?php foreach ($posts as $post): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title"><?= htmlspecialchars($post['title']) ?></h2>
                <p class="card-text"><?= htmlspecialchars($post['description']) ?></p>
                <p class="card-text"><small class="text-muted">Category: <?= htmlspecialchars($post['category_name']) ?>, Author: <?= htmlspecialchars($post['author']) ?></small></p>
                <button class="btn btn-primary view-post-btn" data-post-id="<?= $post['id'] ?>">Смотреть пост</button>
                <button class="btn btn-success like-btn" data-post-id="<?= $post['id'] ?>" data-like-type="like">Хорошо</button>
                <span class="likes-count"><?= $post['likes'] ?></span>
                <button class="btn btn-danger like-btn" data-post-id="<?= $post['id'] ?>" data-like-type="dislike">Плохо</button>
                <span class="dislikes-count"><?= $post['dislikes'] ?></span>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                    <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn btn-secondary">Редактировать пост</a>
                    <button class="btn btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?= $post['id'] ?>">Удалить пост</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&sort=<?= $sort ?>&category=<?= $category ?>"><?= $i ?></a></li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Подтвердите удаление</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Вы действительно хотите удалить этот пост?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <a id="confirmDeleteButton" href="#" class="btn btn-danger">Удалить</a>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для просмотра поста и комментариев -->
<div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="post-title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="post-description"></p>
                <p id="post-meta"></p>
                <hr>
                <div id="comments"></div>
                <button id="load-more-comments" class="btn btn-primary mt-3">Загрузить комментарии</button>
            </div>
            <div class="modal-footer">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form id="comment-form" class="w-100">
                        <div class="input-group">
                            <input type="text" class="form-control" id="new-comment" placeholder="Add a comment...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" id="add-comment-btn">Добавить комментарий</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    $('#deleteModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var postId = button.data('id');
        var deleteUrl = 'delete_post.php?id=' + postId;
        $('#confirmDeleteButton').attr('href', deleteUrl);
    });

    // Like and Dislike functionality
    $('.like-btn').on('click', function() {
        var post_id = $(this).data('post-id');
        var like_type = $(this).data('like-type');

        $.ajax({
            url: 'like.php',
            type: 'POST',
            data: { post_id: post_id, like_type: like_type },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.status === 'success') {
                    $('button[data-post-id="' + post_id + '"][data-like-type="like"]').next('.likes-count').text(data.likes);
                    $('button[data-post-id="' + post_id + '"][data-like-type="dislike"]').next('.dislikes-count').text(data.dislikes);
                } else {
                    alert(data.message);
                }
            }
        });
    });

    // View Post functionality
    $('.view-post-btn').on('click', function() {
        var post_id = $(this).data('post-id');
        $.ajax({
            url: 'post_details.php',
            type: 'GET',
            data: { post_id: post_id, offset: 0 },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.post) {
                    $('#post-title').text(data.post.title);
                    $('#post-description').text(data.post.description);
                    $('#post-meta').text('Категория: ' + data.post.category_name + ', Автор: ' + data.post.author);

                    $('#comments').empty();
                    data.comments.forEach(function(comment) {
                        var commentHtml = '<div class="comment" data-comment-id="' + comment.id + '"><p>' + comment.comment + '</p><small class="text-muted">By ' + comment.author + ' on ' + comment.created_at + '</small>';
                        if (<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?> == comment.user_id) {
                            commentHtml += '<button class="btn btn-danger btn-sm delete-comment-btn" data-comment-id="' + comment.id +      '">Удалить комментарий</button>';
                        }
                        commentHtml += '</div>';
                        $('#comments').append(commentHtml);
                    });

                    $('#load-more-comments').data('post-id', post_id);
                    $('#load-more-comments').data('offset', 3);

                    $('#postModal').modal('show');
                } else {
                    alert(data.error);
                }
            }
        });
    });

    // Load more comments functionality
    $('#load-more-comments').on('click', function() {
        var post_id = $(this).data('post-id');
        var offset = $(this).data('offset');

        $.ajax({
            url: 'post_details.php',
            type: 'GET',
            data: { post_id: post_id, offset: offset },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.comments.length > 0) {
                    data.comments.forEach(function(comment) {
                        var commentHtml = '<div class="comment" data-comment-id="' + comment.id + '"><p>' + comment.comment + '</p><small class="text-muted">By ' + comment.author + ' on ' + comment.created_at + '</small>';
                        if (<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?> == comment.user_id) {
                            commentHtml += '<button class="btn btn-danger btn-sm delete-comment-btn" data-comment-id="' + comment.id + '">Удалить комментарий</button>';
                        }
                        commentHtml += '</div>';
                        $('#comments').append(commentHtml);
                    });

                    $('#load-more-comments').data('offset', offset + 3);
                } else {
                    alert('Нет больше комментариев');
                }
            }
        });
    });

    // Add comment functionality
    $('#add-comment-btn').on('click', function() {
        var post_id = $('#load-more-comments').data('post-id');
        var comment = $('#new-comment').val();

        if (comment.trim() === '') {
            alert('Комментарий не может быть пустым');
            return;
        }

        $.ajax({
            url: 'add_comment.php',
            type: 'POST',
            data: { post_id: post_id, comment: comment },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.status === 'success') {
                    var commentHtml = '<div class="comment" data-comment-id="' + data.comment.id + '"><p>' + data.comment.comment + '</p><small class="text-muted">By ' + data.comment.author + ' on ' + data.comment.created_at + '</small>';
                    if (<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?> == data.comment.user_id) {
                        commentHtml += '<button class="btn btn-danger btn-sm delete-comment-btn" data-comment-id="' + data.comment.id + '">Удалить комментарий</button>';
                    }
                    commentHtml += '</div>';
                    $('#comments').prepend(commentHtml);
                    $('#new-comment').val('');
                } else {
                    alert(data.message);
                }
            }
        });
    });

    // Delete comment functionality
    $(document).on('click', '.delete-comment-btn', function() {
        var comment_id = $(this).data('comment-id');
        $.ajax({
            url: 'delete_comment.php',
            type: 'POST',
            data: { comment_id: comment_id },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.status === 'success') {
                    $('div[data-comment-id="' + comment_id + '"]').remove();
                } else {
                    alert(data.message);
                }
            }
        });
    });
});
</script>

</body>
</html>
