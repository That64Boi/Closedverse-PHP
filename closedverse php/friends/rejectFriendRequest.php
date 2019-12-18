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
