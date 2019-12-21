<?php
require_once('lib/htm.php');
require_once('lib/connect.php');

$get_title = $dbc->prepare('SELECT * FROM titles WHERE title_id = ?');
$get_title->bind_param('i', $title_id);
$get_title->execute();
$title_result = $get_title->get_result();

if ($title_result->num_rows == 0) {
    printHeader(3);
    exit('<title>Closedverse - Error</title><div class="no-content track-error" data-track-error="404"><div><p>The community could not be found.</p></div></div>');
}

$title = $title_result->fetch_array();

if ((isset($_GET['offset']) && is_numeric($_GET['offset'])) && isset($_GET['dateTime'])) {
    //change this back to 50 when we have better servers
    $offset = ($_GET['offset'] * 25);
    $dateTime = htmlspecialchars($_GET['dateTime']);

    if (isset($_GET['q'])) {
        $query = '%/'. $_GET['q'] .'%';

        $get_posts = $dbc->prepare('SELECT * FROM posts INNER JOIN users ON user_id = post_by_id WHERE post_title = ? AND text COLLATE UTF8MB4_GENERAL_CI LIKE ? ESCAPE "/" AND date_time < ? AND deleted = 0 ORDER BY date_time DESC LIMIT 25 OFFSET ?');
        $get_posts->bind_param('issi', $title_id, $query, $dateTime, $offset);
    } else {
        $get_posts = $dbc->prepare('SELECT * FROM posts INNER JOIN users ON user_id = post_by_id WHERE post_title = ? AND date_time < ? AND deleted = 0 ORDER BY date_time DESC LIMIT 25 OFFSET ?');
        $get_posts->bind_param('isi', $title_id, $dateTime, $offset);
    }
} else {
    $tabTitle = $title['title_name'] .' - Cedar';
    printHeader(3);

    echo '<script>var loadOnScroll=true; var atBottom = false;</script>
	<div id="sidebar">
	  <section class="sidebar-container" id="sidebar-community">
	    <span id="sidebar-cover">
	      <a href="/titles/'. $title['title_id'] .'">
	        <img src="'. $title['title_banner'] .'">
	      </a>
	    </span>
	    <header id="sidebar-community-body">
	    <span id="sidebar-community-img">
	      <span class="icon-container">
	    	<a href="/titles/'. $title['title_id'] .'">
	    	  <img src="'. $title['title_icon'] .'" id="icon">
	    	</a>
	      </span>
        <span class="platform-tag">';

    switch ($title['type']) {
        case 1:
            echo '<img src="/assets/img/platform-tag-wiiu.png">';
            break;
        case 2:
            echo '<img src="/assets/img/platform-tag-3ds.png">';
            break;
        case 3:
            echo '<img src="/assets/img/platform-tag-wiiu-3ds.png">';
            break;
    }

    echo '</span>
    </span>
    '. ($title['type'] == 5 ? '<span class="news-community-badge">Announcement Community</span>' : '') .'
		'. ($title['user_made'] == 1 ? '<span class="news-community-badge">User-Created Community</span>' : '') . ($title['owner_only'] == 1 ? '<span class="news-community-badge">Private
</span>' : '') .'
    <h1 class="community-name"><a href="/titles/'. $title['title_id'] .'">'. htmlspecialchars($title['title_name'], ENT_QUOTES) .'</a></h1>
    </header>
      <div class="community-description js-community-description">
		<p class="text js-truncated-text">'. nl2br(htmlspecialchars($title['title_desc'], ENT_QUOTES)) .'</p>';
    if (!empty($title['title_by'])) {
        $get_title_owner = $dbc->prepare('SELECT * FROM users WHERE user_id = ?');
        $get_title_owner->bind_param('i', $title['title_by']);
        $get_title_owner->execute();
        $title_owner_result = $get_title_owner->get_result();
        $title_owner = $title_owner_result->fetch_array();
        echo '<p style="text-align: center;">Community owner: <a href="/users/'. htmlspecialchars($title_owner['user_name'], ENT_QUOTES) .'/posts">'. $title_owner['user_name'] .'</a></p>';
    }
      echo '</div>';

    if (!empty($_SESSION['signed_in'])) {
        if ($_SESSION['user_id'] == $title['title_by']) {
            echo '<div id="edit-title"><a class="button symbol" href="/titles/'. $title['title_id'] .'/edit">Community Settings</a></div>';
        }
        echo '<button type="button" class="symbol button favorite-button';

        $check_favorite = $dbc->prepare('SELECT * FROM favorite_titles WHERE user_id = ? AND title_id = ?');
        $check_favorite->bind_param('ii', $_SESSION['user_id'], $title['title_id']);
        $check_favorite->execute();
        $favorite_result = $check_favorite->get_result();

        if (!$favorite_result->num_rows == 0) {
            echo ' checked ';
        }

        echo '"data-title-id="'. $title['title_id'] .'"><span class="favorite-button-text">Favorite</span></button>';
    }

    ?><div class="sidebar-setting"><div class="sidebar-post-menu"></div></div></section>

    </div><div class="main-column"><div class="post-list-outline"><?php

    if ($title['perm'] < 1) {
        echo '<div id="posts-filter-tab-container" class="tab-container ">
		<div class="tab3">
			<a id="posts-filter-anchor" class="selected" href="/titles/'. $title['title_id'] .'">
				<span class="new-posts">All Posts</span>
			</a>
			<a class="" href="/titles/'. $title['title_id'] .'/popular">Popular <span class="long">posts</span></a>
			<a class="" href="/titles/'. $title['title_id'] .'/controversial">Controversial <span class="long">posts</span></a>
		  </div>
		</div>';
    }
    echo '<form class="search" style="margin:10px auto;width:90%" onsubmit="event.preventDefault();$.pjax({url: location.pathname+\'?q=\'+encodeURIComponent($(\'#dv\').val()), container: \'#main-body\'});">
    <input name="q" id="dv" placeholder="Search posts in this community" type="text"'. (isset($_GET['q']) ? ' value="'. $_GET['q'] .'"' : '') .'><input title="Search" type="submit" value="q"></form>';

    include 'postText.php';
    echo '<div class="body-content" id="community-post-list"><div class="list post-list" data-next-page-url="/titles/'. $title['title_id'] .'?offset=1&dateTime='. date("Y-m-d H:i:s") . (isset($_GET['q']) ? '&q=' . $_GET['q'] : '') .'">';

    if (isset($_GET['q'])) {
        $query = '%/'. $_GET['q'] .'%';

        $get_posts = $dbc->prepare('SELECT * FROM posts INNER JOIN users ON user_id = post_by_id INNER JOIN profiles ON users.user_id = profiles.user_id WHERE post_title = ? AND text COLLATE UTF8MB4_GENERAL_CI LIKE ? ESCAPE "/" AND deleted = 0 ORDER BY date_time DESC LIMIT 25');
        $get_posts->bind_param('is', $title_id, $query);
    } else {
        $get_posts = $dbc->prepare('SELECT * FROM posts INNER JOIN users ON user_id = post_by_id INNER JOIN profiles ON users.user_id = profiles.user_id WHERE post_title = ? AND deleted = 0 ORDER BY date_time DESC LIMIT 25');
        $get_posts->bind_param('i', $title_id);
    }
}
$get_posts->execute();
$posts_result = $get_posts->get_result();

if (!$posts_result->num_rows == 0) {
    while ($row = $posts_result->fetch_array()) {
        if (!empty($_SESSION['signed_in']) && $_SESSION['user_id'] != $row['user_id']) {
            $check_blocking = $dbc->prepare('SELECT * FROM blocks WHERE block_by = ? AND block_to = ?');
            $check_blocking->bind_param('ii', $_SESSION['user_id'], $row['user_id']);
            $check_blocking->execute();
            $result_blocking = $check_blocking->get_result();
            if (!$result_blocking->num_rows == 0) {
                continue;
            } else {
                $check_blocked = $dbc->prepare('SELECT * FROM blocks WHERE block_to = ? AND block_by = ?');
                $check_blocked->bind_param('ii', $_SESSION['user_id'], $row['user_id']);
                $check_blocked->execute();
                $result_blocked = $check_blocked->get_result();
                if (!$result_blocked->num_rows == 0) {
                    continue;
                }
            }
        }

        echo '<div class="post trigger" data-href="/posts/'. $row['id'] .'">';
        printPost($row, 1);
    }
    echo '</div></div>';
    if (isset($_GET['offset']) && is_numeric($_GET['offset']) && isset($_GET['dateTime'])) {
        echo '<script>reloadYeahTooltip();</script>';
    }
} else {
    if (!(isset($_GET['offset']) && is_numeric($_GET['offset']) && isset($_GET['dateTime']))) {
        echo '<script>var aTbottom=true;</script><div class="no-content"><div><p>This community doesn\'t have any posts yet.</p></div></div>';
    }
}
