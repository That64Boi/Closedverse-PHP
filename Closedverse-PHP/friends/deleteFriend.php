<?php
require_once('lib/connect.php');

$get_user = $dbc->prepare('SELECT user_id FROM users WHERE user_name = ?');
$get_user->bind_param('s', $action);
$get_user->execute();
$user_result = $get_user->get_result();
if ($user_result->num_rows == 0) {
    exit();
}
$user = $user_result->fetch_assoc();

$get_friend = $dbc->prepare('SELECT * FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_two = ? AND user_one = ?)');
$get_friend->bind_param('iiii', $_SESSION['user_id'], $user['user_id'], $_SESSION['user_id'], $user['user_id']);
$get_friend->execute();
$friend_result = $get_friend->get_result();
if ($friend_result->num_rows == 0) {
    exit();
}
$friend = $friend_result->fetch_assoc();

$read_messages = $dbc->prepare('UPDATE messages SET message_read = 1 WHERE conversation_id = ?');
$read_messages->bind_param('i', $friend['conversation_id']);
$read_messages->execute();

$delete_friend = $dbc->prepare('DELETE FROM friends WHERE friend_id = ?');
$delete_friend->bind_param('i', $friend['friend_id']);
$delete_friend->execute();
