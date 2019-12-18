<?php
require_once('lib/htm.php');
require_once('lib/htmUsers.php');

$get_user = $dbc->prepare('SELECT * FROM users INNER JOIN profiles ON profiles.user_id = users.user_id WHERE user_name = ? LIMIT 1');
$get_user->bind_param('s', $action);
$get_user->execute();
$user_result = $get_user->get_result();

if ($user_result->num_rows == 0) {
    printHeader('');
    noUser();
} else {
    $user = $user_result->fetch_assoc();

    if (!((isset($_GET['offset']) && is_numeric($_GET['offset'])) && isset($_GET['dateTime']))) {
        $tabTitle = htmlspecialchars($user['nickname'], ENT_QUOTES) .'\'s Posts - Closedverse';

        if (empty($_SESSION['signed_in']) || $_SESSION['user_id'] == $user['user_id']) {
            printHeader(1);
        } else {
            printHeader(0);
        }
        if (!empty($_SESSION['signed_in']) && $_SESSION['user_id'] != $user['user_id']) {
            $check_blocking = $dbc->prepare('SELECT * FROM blocks WHERE block_by = ? AND block_to = ?');
            $check_blocking->bind_param('ii', $_SESSION['user_id'], $user['user_id']);
            $check_blocking->execute();
            $result_blocking = $check_blocking->get_result();
            if (!$result_blocking->num_rows == 0) {
                exit('<div class="no-content track-error" data-track-error="404"><div><p>You are blocking '. htmlspecialchars($user['user_name'], ENT_QUOTES) .'. Unblock them to view their profile.</p></div></div>');
            } else {
                $check_blocked = $dbc->prepare('SELECT * FROM blocks WHERE block_to = ? AND block_by = ?');
                $check_blocked->bind_param('ii', $_SESSION['user_id'], $user['user_id']);
                $check_blocked->execute();
                $result_blocked = $check_blocked->get_result();
                if (!$result_blocked->num_rows == 0) {
                    exit('<div class="no-content track-error" data-track-error="404"><div><p>You were blocked by '. htmlspecialchars($user['user_name'], ENT_QUOTES) .'.</p></div></div>');
                }
            }
        }

        echo '<script>var loadOnScroll=true;</script><div id="sidebar" class="user-sidebar">';

        userContent($user, "posts");

        userSidebarSetting($user, 1);

        userInfo($user);

        echo '</div><div class="main-column"><div class="post-list-outline">
		<h2 class="label">'. htmlspecialchars($user['nickname'], ENT_QUOTES) .'\'s Posts</h2>
		<div class="list post-list js-post-list" data-next-page-url="/users/'. $user['user_name'] .'/posts?offset=1&dateTime='.date("Y-m-d H:i:s").'">';

        if (!empty($_SESSION['signed_in']) && $user['user_id'] == $_SESSION['user_id']) {
            $get_posts = $dbc->prepare('SELECT * FROM posts LEFT JOIN titles ON title_id = post_title WHERE post_by_id = ? AND deleted < 2 ORDER BY posts.date_time DESC LIMIT 25');
        } else {
            $get_posts = $dbc->prepare('SELECT * FROM posts LEFT JOIN titles ON title_id = post_title WHERE post_by_id = ? AND deleted = 0 ORDER BY posts.date_time DESC LIMIT 25');
        }
        $get_posts->bind_param('i', $user['user_id']);
    } else {
        $offset = ($_GET['offset'] * 25);
        $dateTime = htmlspecialchars($_GET['dateTime']);
        if ($user['user_id'] == $_SESSION['user_id']) {
            $get_posts = $dbc->prepare('SELECT * FROM posts LEFT JOIN titles ON title_id = post_title WHERE post_by_id = ? AND posts.date_time < ? AND deleted < 2 ORDER BY posts.date_time DESC LIMIT 25 OFFSET ?');
        } else {
            $get_posts = $dbc->prepare('SELECT * FROM posts LEFT JOIN titles ON title_id = post_title WHERE post_by_id = ? AND posts.date_time < ? AND deleted = 0 ORDER BY posts.date_time DESC LIMIT 25 OFFSET ?');
        }
        $get_posts->bind_param('isi', $user['user_id'], $dateTime, $offset);
    }

    $get_posts->execute();
    $posts_result = $get_posts->get_result();

    if (!$posts_result->num_rows == 0) {
        echo '<div id="user-page-no-content" class="none"></div>';

        while ($posts = $posts_result->fetch_array()) {
            echo '<div data-href="/posts/'. $posts['id'] .'" class="post post-subtype-default trigger">';
            if ($posts['post_title'] != null) {
                echo '<p class="community-container"><a class="test-community-link" href="/titles/'. $posts['title_id'] .'"><img src="'. $posts['title_icon'] .'" class="community-icon">'. htmlspecialchars($posts['title_name'], ENT_QUOTES) .'</a></p>';
            } else {
                echo '<p class="community-container"><a class="test-community-link"><img src="/assets/img/feed-icon.png" class="community-icon">Activity Feed</a></p>';
            }

            printPost(array_merge($posts, $user), 1);
        }
    } else {
        if (!(isset($_GET['offset']) && is_numeric($_GET['offset']) && isset($_GET['dateTime']))) {
            echo '
			<div id="user-page-no-content" class="no-content">
			  <div>
			    <p>No posts have been made yet.</p>
			  </div>
			</div>';
        }
    }
}
