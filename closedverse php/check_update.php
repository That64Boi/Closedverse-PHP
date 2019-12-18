<?php
require_once('lib/connect.php');
if (!empty($_SESSION['signed_in'])) {
    $get_notifs = $dbc->prepare('SELECT count(notif_id) FROM notifs WHERE notif_to = ? AND merged IS NULL AND notif_read = 0 LIMIT 25');
    $get_notifs->bind_param('i', $_SESSION['user_id']);
    $get_notifs->execute();
    $notifs_result = $get_notifs->get_result();
    $notif = $notifs_result->fetch_assoc();

    $get_fr = $dbc->prepare('SELECT count(request_id) FROM friend_requests WHERE request_to = ? AND request_read = 0 LIMIT 25');
    $get_fr->bind_param('i', $_SESSION['user_id']);
    $get_fr->execute();
    $fr_result = $get_fr->get_result();
    $fr = $fr_result->fetch_assoc();

    $get_messages = $dbc->prepare('SELECT count(message_id) FROM messages WHERE message_read = 0 AND deleted = 0 AND message_by != ? AND conversation_id IN (SELECT conversation_id FROM conversations WHERE user_one = ? OR user_two = ?) LIMIT 40');
    $get_messages->bind_param('iii', $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
    $get_messages->execute();
    $messages_result = $get_messages->get_result();
    $messages = $messages_result->fetch_assoc();

    $update_online = $dbc->prepare('UPDATE users SET last_online = NOW() WHERE user_id = ?');
    $update_online->bind_param('i', $_SESSION['user_id']);
    $update_online->execute();

    echo json_encode(array('success' => 1, 'notifs' => array('unread_count' => ($notif['count(notif_id)'] + $fr['count(request_id)'])), 'messages' => array('unread_count' => $messages['count(message_id)'])), JSON_FORCE_OBJECT);
}
