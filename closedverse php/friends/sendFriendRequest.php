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

$get_fr = $dbc->prepare('SELECT * FROM friend_requests WHERE request_by = ? AND request_to = ?');
$get_fr->bind_param('ii', $_SESSION['user_id'], $user['user_id']);
$get_fr->execute();
$fr_result = $get_fr->get_result();
if (!$fr_result->num_rows == 0) {
    exit();
}

$send_fr = $dbc->prepare('INSERT INTO friend_requests (request_to, request_by, request_text) VALUES (?, ?, ?)');
$send_fr->bind_param('iis', $user['user_id'], $_SESSION['user_id'], $_POST['body']);
$send_fr->execute();
