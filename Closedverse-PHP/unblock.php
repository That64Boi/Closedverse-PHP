<?php
require_once('lib/htm.php');

if (empty($_SESSION['signed_in'])) {
    exit();
}
if (isset($action)) {
    $get_user = $dbc->prepare('SELECT * FROM users WHERE user_name = ?');
    $get_user->bind_param('s', $action);
    $get_user->execute();
    $user_result = $get_user->get_result();
    if ($user_result->num_rows == 0) {
        exit();
    }
    $user = $user_result->fetch_assoc();

    if ($_SESSION['user_id'] == $user['user_id']) {
        exit();
    }

    $unblock = $dbc->prepare('DELETE FROM blocks WHERE block_by = ? AND block_to = ?');
    $unblock->bind_param('ii', $_SESSION['user_id'], $user['user_id']);
    $unblock->execute();

    echo 'success';
}
