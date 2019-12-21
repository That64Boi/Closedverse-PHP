<?php
require_once('lib/connect.php');

$get_reply = $dbc->prepare('SELECT * FROM replies WHERE reply_id = ?');
$get_reply->bind_param('i', $id);
$get_reply->execute();
$reply_result = $get_reply->get_result();
if ($reply_result->num_rows == 0) {
    exit();
}
$reply = $reply_result->fetch_assoc();

if ($_SESSION['user_id'] != $reply['reply_by_id']) {
    exit();
}

if (empty($_POST['body'])) {
    exit('Replies cannot be empty.');
} elseif (mb_strlen($_POST['body']) > 2000) {
    exit('Replies cannot be longer than 2000 characters.');
}

if (empty($_POST['feeling_id']) || strval($_POST['feeling_id']) >= 6) {
    $_POST['feeling_id'] = 0;
}

$edit_reply = $dbc->prepare('UPDATE replies SET text = ?, feeling_id = ?, edited = 1 WHERE reply_id = ?');
$edit_reply->bind_param('sii', $_POST['body'], $_POST['feeling_id'], $reply['reply_id']);
$edit_reply->execute();

echo 'success';
