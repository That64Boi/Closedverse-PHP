<?php
require_once('lib/htm.php');
require_once('lib/htmUsers.php');

if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
        $offset = ($_GET['offset'] * 20);

        $get_blocking = $dbc->prepare('SELECT * FROM blocks WHERE block_by = ? ORDER BY block_id DESC LIMIT 20 OFFSET ?');
        $get_blocking->bind_param('ii', $user['user_id'], $offset);
} else {
    $tabTitle = 'Blocked Users - Closedverse';

    printHeader(0);

    $get_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
    $get_user->bind_param('i', $_SESSION['user_id']);
    $get_user->execute();
    $user_result = $get_user->get_result();
    $user = $user_result->fetch_assoc();
    echo '<div id="sidebar" class="general-sidebar">';
    userContent($user, "");
    sidebarSetting();
    echo '</div>
<div class="main-column"><div class="post-list-outline">
        <h2 class="label">Blocked Users</h2><div class="list follow-list following">
        <ul class="list-content-with-icon-and-text arrow-list" id="friend-list-content" data-next-page-url="/blocked?offset=1&">';

    $get_blocking = $dbc->prepare('SELECT * FROM blocks WHERE block_by = ? ORDER BY block_id DESC LIMIT 20');
    $get_blocking->bind_param('i', $user['user_id']);
}

$get_blocking->execute();
$blocking_result = $get_blocking->get_result();

if (!$blocking_result->num_rows == 0) {
    while ($blocking = $blocking_result->fetch_array()) {
        $get_block_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
        $get_block_user->bind_param('i', $blocking['block_to']);
        $get_block_user->execute();
        $block_user_result = $get_block_user->get_result();
        $block_user = $block_user_result->fetch_assoc();

        echo '<li class="trigger" data-href="/users/'. $block_user['user_name'] .'/posts"><a href="/users/'. $block_user['user_name'] .'/posts" class="icon-container'.($block_user['user_level'] > 1 ? ' verified' : '').'"><img src="'. printFace($block_user['user_face'], 0) .'" id="icon"></a>
                <div class="toggle-button">';

            echo '<button type="button" data-user-id="'. $block_user['user_id'] .'" class="unblock-button button symbol relationship-button" data-action="/users/'. $block_user['user_name'] .'/unblock">Unblock</button>';

        echo '</div>
                <div class="body">
                <p class="title">
                <span class="nick-name"><a href="/users/'. $block_user['user_name'] .'/posts">'. $block_user['nickname'] .'</a></span>
                <span class="id-name">'. $block_user['user_name'] .'</span>
                </p>
                </div></li>';
    }
} else {
    if (!(isset($_GET['offset']) && is_numeric($_GET['offset']))) {
        echo '<div id="user-page-no-content" class="no-content"><div>
            <p>No blocked users.</p>
            </div></div>';
    }
}
