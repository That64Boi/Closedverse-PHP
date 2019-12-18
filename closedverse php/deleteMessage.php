<?php
require_once('lib/htm.php');

if (!empty($_SESSION['signed_in']) && isset($id)) {
    $get_message = $dbc->prepare('SELECT * FROM messages WHERE message_id = ? LIMIT 1');
    $get_message->bind_param('i', $id);
    $get_message->execute();
    $message_result = $get_message->get_result();

    if ($message_result->num_rows == 0) {
        exit();
    }

    $message = $message_result->fetch_assoc();

    if ($message['message_by'] == $_SESSION['user_id']) {
        $delete = $dbc->prepare('UPDATE messages SET deleted = 1 WHERE message_id = ?');
        $delete->bind_param('i', $id);
        $delete->execute();
        echo 'success';
    }
}
