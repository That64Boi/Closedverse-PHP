<?php
require_once('lib/htm.php');
require_once('lib/htmUsers.php');

$tabTitle = 'Messages - Closedverse';

printHeader(4);

$get_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
$get_user->bind_param('i', $_SESSION['user_id']);
$get_user->execute();
$user_result = $get_user->get_result();
$user = $user_result->fetch_assoc();
echo '<div id="sidebar" class="general-sidebar">';
userContent($user, "");
sidebarSetting();
?></div><?php

echo '<div class="main-column messages">
<div class="post-list-outline">
<h2 class="label">Messages';
/*<span class="message-chk">
<input type="checkbox" name="online" value="1"> Only show online friends
</span>*/
echo '</h2>

<div class="list">
<ul class="list-content-with-icon-and-text arrow-list">';

$get_convos = $dbc->prepare('SELECT * FROM friends INNER JOIN conversations ON conversations.conversation_id = friends.conversation_id WHERE friends.user_one = ? OR friends.user_two = ? ORDER BY last_message DESC');
$get_convos->bind_param('ii', $_SESSION['user_id'], $_SESSION['user_id']);
$get_convos->execute();
$convos_result = $get_convos->get_result();

if ($convos_result->num_rows == 0) {
    echo '<div id="user-page-no-content" class="no-content"><div><p>You don\'t have any friends yet.</p></div></div>';
} else {
    while ($convos = $convos_result->fetch_array()) {
        $get_convo_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
        if ($convos['user_one'] == $user['user_id']) {
            $get_convo_user->bind_param('i', $convos['user_two']);
        } else {
            $get_convo_user->bind_param('i', $convos['user_one']);
        }
        $get_convo_user->execute();
        $convo_user_result = $get_convo_user->get_result();
        $convo_user = $convo_user_result->fetch_assoc();

        $get_last_message = $dbc->prepare('SELECT * FROM messages WHERE conversation_id = ? AND deleted = 0 ORDER BY message_date DESC LIMIT 1');
        $get_last_message->bind_param('i', $convos['conversation_id']);
        $get_last_message->execute();
        $last_message_result = $get_last_message->get_result();
        $last_message = $last_message_result->fetch_assoc();

        echo '<li class="trigger'. ($last_message_result->num_rows != 0 && $last_message['message_read'] == 0 && $last_message['message_by'] != $_SESSION['user_id'] ? ' notify' : '') .'" data-href="/messages/'. htmlspecialchars($convo_user['user_name'], ENT_QUOTES) .'">
        <a href="/users/'. htmlspecialchars($convo_user['user_name'], ENT_QUOTES) .'/posts" class="icon-container'. ($convo_user['user_level'] > 1 ? ' verified' : '') . ($convo_user['hide_online'] == 0 ? (strtotime($convo_user['last_online']) > time() - 35 ? ' online' : ' offline') : '') .'"><img src="'. printFace($convo_user['user_face'], 0) .'" id="icon"></a>
        <div class="body"><p class="title">
        <span class="nick-name"><a '. (isset($convo_user['name_color']) ? 'style="color: '. $convo_user['name_color'] .'"' : '') .' href="/users/'. htmlspecialchars($convo_user['user_name'], ENT_QUOTES) .'/posts">'. htmlspecialchars($convo_user['nickname'], ENT_QUOTES) .'</a></span>
        <span class="id-name">'. htmlspecialchars($convo_user['user_name'], ENT_QUOTES) .'</span>
        </p>';

        if ($last_message_result->num_rows == 0) {
            echo '<p class="text placeholder">You haven\'t exchanged messages with this user yet.</p>';
        } else {
            $last_message['body'] = htmlspecialchars($last_message['body'], ENT_QUOTES);
            $last_message['body'] = preg_replace("/^&gt;(.*)\n|^&gt;(.*)/m", '<span class="gt">$0</span>', $last_message['body']);

            echo '<span class="timestamp">'. humanTiming(strtotime($last_message['message_date'])) .'</span>
            <p class="text'. ($last_message['message_by'] == $_SESSION['user_id'] ? ' my' : '') .'">'. $last_message['body'] .'</p>';
        }

        echo '</div>
        </li>';
    }
}

echo '</ul>
</div>
</div>
</div>';