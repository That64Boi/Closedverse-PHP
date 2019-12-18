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

    if ($user['user_level'] > 1) {
        exit('You cannot block verified users.');
    }

    $block = $dbc->prepare('INSERT INTO blocks (block_to, block_by) VALUES (?, ?)');
    $block->bind_param('ii', $user['user_id'], $_SESSION['user_id']);
    $block->execute();

    $get_friend = $dbc->prepare('SELECT * FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_two = ? AND user_one = ?)');
    $get_friend->bind_param('iiii', $_SESSION['user_id'], $user['user_id'], $_SESSION['user_id'], $user['user_id']);
    $get_friend->execute();
    $friend_result = $get_friend->get_result();
    if (!$friend_result->num_rows == 0) {
        $friend = $friend_result->fetch_assoc();

        $read_messages = $dbc->prepare('UPDATE messages SET message_read = 1 WHERE conversation_id = ?');
        $read_messages->bind_param('i', $friend['conversation_id']);
        $read_messages->execute();
    }

    $remove_friend = $dbc->prepare('DELETE FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_two = ? AND user_one = ?)');
    $remove_friend->bind_param('iiii', $_SESSION['user_id'], $user['user_id'], $_SESSION['user_id'], $user['user_id']);
    $remove_friend->execute();

    $get_follows = $dbc->prepare('DELETE FROM follows WHERE (follow_by = ? AND follow_to = ?) OR (follow_to = ? AND follow_by = ?)');
    $get_follows->bind_param('iiii', $_SESSION['user_id'], $user['user_id'], $_SESSION['user_id'], $user['user_id']);
    $get_follows->execute();

    echo 'success';
}
