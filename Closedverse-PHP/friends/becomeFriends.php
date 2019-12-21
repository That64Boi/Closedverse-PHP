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

$get_fr = $dbc->prepare('SELECT * FROM friend_requests WHERE request_to = ? AND request_by = ? LIMIT 1');
$get_fr->bind_param('ii', $_SESSION['user_id'], $user['user_id']);
$get_fr->execute();
$fr_result = $get_fr->get_result();
if ($fr_result->num_rows == 0) {
    exit();
}
$fr = $fr_result->fetch_assoc();

$delete_fr = $dbc->prepare('DELETE FROM friend_requests WHERE request_id = ?');
$delete_fr->bind_param('i', $fr['request_id']);
$delete_fr->execute();

$get_convo = $dbc->prepare('SELECT * FROM conversations WHERE (user_one = ? AND user_two = ?) OR (user_two = ? AND user_one = ?) LIMIT 1');
$get_convo->bind_param('iiii', $_SESSION['user_id'], $user['user_id'], $_SESSION['user_id'], $user['user_id']);
$get_convo->execute();
$convo_result = $get_convo->get_result();
if ($convo_result->num_rows == 0) {
    $create_convo = $dbc->prepare('INSERT INTO conversations (user_one, user_two) VALUES (?, ?)');
    $create_convo->bind_param('ii', $_SESSION['user_id'], $user['user_id']);
    $create_convo->execute();
}

$get_convo = $dbc->prepare('SELECT * FROM conversations WHERE (user_one = ? AND user_two = ?) OR (user_two = ? AND user_one = ?) LIMIT 1');
$get_convo->bind_param('iiii', $_SESSION['user_id'], $user['user_id'], $_SESSION['user_id'], $user['user_id']);
$get_convo->execute();
$convo_result = $get_convo->get_result();
$convo = $convo_result->fetch_assoc();

$create_friend = $dbc->prepare('INSERT INTO friends (user_one, user_two, conversation_id) VALUES (?, ?, ?)');
$create_friend->bind_param('iii', $_SESSION['user_id'], $user['user_id'], $convo['conversation_id']);
$create_friend->execute();
