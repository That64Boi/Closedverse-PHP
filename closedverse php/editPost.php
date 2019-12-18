<?php
require_once('lib/connect.php');

$get_post = $dbc->prepare('SELECT * FROM posts WHERE id = ?');
$get_post->bind_param('i', $id);
$get_post->execute();
$post_result = $get_post->get_result();
if ($post_result->num_rows == 0) {
    exit();
}
$post = $post_result->fetch_assoc();

if ($_SESSION['user_id'] != $post['post_by_id']) {
    exit();
}

if (empty($_POST['body'])) {
    exit('Posts cannot be empty.');
} elseif (mb_strlen($_POST['body']) > 2000) {
    exit('Posts cannot be longer than 2000 characters.');
}

if (empty($_POST['feeling_id']) || strval($_POST['feeling_id']) >= 6) {
    $_POST['feeling_id'] = 0;
}

$edit_post = $dbc->prepare('UPDATE posts SET text = ?, feeling_id = ?, edited = 1 WHERE id = ?');
$edit_post->bind_param('sii', $_POST['body'], $_POST['feeling_id'], $post['id']);
$edit_post->execute();

echo 'success';
