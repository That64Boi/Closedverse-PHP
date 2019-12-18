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

        $get_following = $dbc->prepare('SELECT * FROM follows WHERE follow_by = ? ORDER BY follow_id DESC LIMIT 20 OFFSET ?');
        $get_following->bind_param('ii', $user['user_id'], $offset);
    } else {
        $tabTitle = $user['nickname'] .'\'s Follows - Closedverse';

        printHeader(0);

        echo '<script>var loadOnScroll=true; var atBottom = false;</script><div id="sidebar" class="user-sidebar">';

        userContent($user, "following");

        userSidebarSetting($user, 0);

        userInfo($user);

        echo '</div>
		<div class="main-column"><div class="post-list-outline">
		<h2 class="label">Users '. $user['nickname'] .' Is Following</h2><div class="list follow-list following">
		<ul class="list-content-with-icon-and-text arrow-list" id="friend-list-content" data-next-page-url="/users/'. $user['user_name'] .'/following?offset=1&">';

        $get_following = $dbc->prepare('SELECT * FROM follows WHERE follow_by = ? ORDER BY follow_id DESC LIMIT 20');
        $get_following->bind_param('i', $user['user_id']);
    }
        $get_following->execute();
        $following_result = $get_following->get_result();

    if (!$following_result->num_rows == 0) {
        while ($following = $following_result->fetch_array()) {
            $get_follow_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
            $get_follow_user->bind_param('i', $following['follow_to']);
            $get_follow_user->execute();
            $follow_user_result = $get_follow_user->get_result();
            $follow_user = $follow_user_result->fetch_assoc();

            echo '<li class="trigger" data-href="/users/'. $follow_user['user_name'] .'/posts"><a href="/users/'. $follow_user['user_name'] .'/posts" class="icon-container'. ($follow_user['user_level'] > 1 ? ' verified' : '') . ($follow_user['hide_online'] == 0 ? (strtotime($follow_user['last_online']) > time() - 35 ? ' online' : ' offline') : '') .'"><img src="'. printFace($follow_user['user_face'], 0) .'" id="icon"></a>
				<div class="toggle-button">';

            $check_followed = $dbc->prepare('SELECT * FROM follows WHERE follow_by = ? AND follow_to = ? LIMIT 1');
            $check_followed->bind_param('ii', $_SESSION['user_id'], $follow_user['user_id']);
            $check_followed->execute();
            $followed_result = $check_followed->get_result();

            if (($followed_result->num_rows == 0) && ($_SESSION['user_id'] != $follow_user['user_id'])) {
                echo '<button type="button" data-user-id="'. $follow_user['user_id'] .'" class="follow-button button symbol relationship-button" data-community-id="" data-url-id="" data-track-label="user" data-title-id="" data-track-action="follow" data-track-category="follow">Follow</button>
					<button type="button" class="button follow-done-button relationship-button symbol none" disabled="">Follow</button>';
            }

            echo '</div>
				<div class="body">
				<p class="title">
				<span class="nick-name"><a '. (isset($follow_user['name_color']) ? 'style="color: '. $follow_user['name_color'] .'"' : '') .' href="/users/'. $follow_user['user_name'] .'/posts">'. $follow_user['nickname'] .'</a></span>
				<span class="id-name">'. $follow_user['user_name'] .'</span>
				</p>
				</div></li>';
        }
    } else {
        if (!(isset($_GET['offset']) && is_numeric($_GET['offset']))) {
            echo '<div id="user-page-no-content" class="no-content"><div>
            <p>No followed users.</p>
            </div></div>';
        }
    }
}
