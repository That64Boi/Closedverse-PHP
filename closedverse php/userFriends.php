<?php
require_once('lib/htm.php');
require_once('lib/htmUsers.php');

$get_user = $dbc->prepare('SELECT * FROM users INNER JOIN profiles ON profiles.user_id = users.user_id WHERE user_name = ? LIMIT 1');
$get_user->bind_param('s', $action);
$get_user->execute();
$user_result = $get_user->get_result();

if ($user_result->num_rows == 0) {
    printHeader(0);
    noUser();
} else {
    $user = $user_result->fetch_assoc();

    if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
        $offset = ($_GET['offset'] * 20);

        $get_friends = $dbc->prepare('SELECT * FROM friends WHERE user_one = ? OR user_two = ? ORDER BY friend_date DESC LIMIT 20 OFFSET ?');
        $get_friends->bind_param('iii', $user['user_id'], $user['user_id'], $offset);
    } else {
        $tabTitle = $user['nickname'] .'\'s Friends - Closedverse';

        printHeader('');

        echo '<script>var loadOnScroll=true; var atBottom = false;</script><div id="sidebar" class="user-sidebar">';

        userContent($user, "friends");

        userSidebarSetting($user, 0);

        userInfo($user);

        echo '</div>
		<div class="main-column"><div class="post-list-outline">
		  <h2 class="label">'. $user['nickname'] .'\'s Friends</h2>
		  <div class="list follow-list">
		    <ul class="list-content-with-icon-and-text arrow-list" id="friend-list-content" data-next-page-url="/users/'. $user['user_name'] .'/friends?offset=1&">';

        $get_friends = $dbc->prepare('SELECT * FROM friends WHERE user_one = ? OR user_two = ? ORDER BY friend_date DESC LIMIT 20');
        $get_friends->bind_param('ii', $user['user_id'], $user['user_id']);
    }
        $get_friends->execute();
        $friends_result = $get_friends->get_result();

    if (!$friends_result->num_rows == 0) {
        while ($friends = $friends_result->fetch_array()) {
            $get_friend_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
            if ($friends['user_one'] == $user['user_id']) {
                $get_friend_user->bind_param('i', $friends['user_two']);
            } else {
                $get_friend_user->bind_param('i', $friends['user_one']);
            }
            $get_friend_user->execute();
            $friend_user_result = $get_friend_user->get_result();
            $friend_user = $friend_user_result->fetch_assoc();

            echo '<li class="trigger" data-href="/users/'. $friend_user['user_name'] .'/posts">
				  <a href="/users/'. $friend_user['user_name'] .'/posts" class="icon-container'. ($friend_user['user_level'] > 1 ? ' verified' : '') . ($friend_user['hide_online'] == 0 ? (strtotime($friend_user['last_online']) > time() - 35 ? ' online' : ' offline') : '') .'">
				    <img src="'. printFace($friend_user['user_face'], 0) .'" id="icon">
				  </a>

				    <div class="toggle-button">';

            $check_followed = $dbc->prepare('SELECT * FROM follows WHERE follow_by = ? AND follow_to = ? LIMIT 1');
            $check_followed->bind_param('ii', $_SESSION['user_id'], $friend_user['user_id']);
            $check_followed->execute();
            $followed_result = $check_followed->get_result();

            if (($followed_result->num_rows == 0) && ($_SESSION['user_id'] != $friend_user['user_id'])) {
                echo '<button type="button" data-user-id="'. $friend_user['user_id'] .'" class="follow-button button symbol relationship-button" data-community-id="" data-url-id="" data-track-label="user" data-title-id="" data-track-action="follow" data-track-category="follow">Follow</button><button type="button" class="button follow-done-button relationship-button symbol none" disabled="">Follow</button>';
            }

            echo '</div>
				  <div class="body">
				    <p class="title">
				      <span class="nick-name">
				        <a '. (isset($friend_user['name_color']) ? 'style="color: '. $friend_user['name_color'] .'"' : '') .' href="/users/'. $friend_user['user_name'] .'/posts">'. $friend_user['nickname'] .'</a>
				      </span>
				      <span class="id-name">'. $friend_user['user_name'] .'</span>
				    </p>
				  </div>
				</li>';
        }
    } else {
        if (!(isset($_GET['offset']) && is_numeric($_GET['offset']))) {
            echo '<div id="user-page-no-content" class="no-content"><div>
            <p>This user has no friends. What a loser.</p>
            </div></div>';
        }
    }
}
