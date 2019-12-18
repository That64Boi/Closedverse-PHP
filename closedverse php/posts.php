<?php
require_once('lib/htm.php');

$search_post = $dbc->prepare('SELECT * FROM posts WHERE posts.id = ? LIMIT 1');
$search_post->bind_param('i', $id);
$search_post->execute();
$post_result = $search_post->get_result();

if ($post_result->num_rows == 0) {
    printHeader(0);
    exit('<title>Closedverse - Error</title><div class="no-content track-error" data-track-error="404"><div><p>The post could not be found.</p></div></div>');
}

    $post = $post_result->fetch_assoc();

    $get_user = $dbc->prepare('SELECT * FROM users INNER JOIN profiles ON users.user_id = profiles.user_id WHERE users.user_id = ?');
    $get_user->bind_param('i', $post['post_by_id']);
    $get_user->execute();
    $user_result = $get_user->get_result();
    $user = $user_result->fetch_assoc();

    $tabTitle = htmlspecialchars($user['nickname'], ENT_QUOTES) .'\'s post - Cedar';

    printHeader('');

if (!empty($_SESSION['signed_in']) && $_SESSION['user_id'] != $user['user_id']) {
        $check_blocking = $dbc->prepare('SELECT * FROM blocks WHERE block_by = ? AND block_to = ?');
        $check_blocking->bind_param('ii', $_SESSION['user_id'], $user['user_id']);
        $check_blocking->execute();
        $result_blocking = $check_blocking->get_result();
    if (!$result_blocking->num_rows == 0) {
        exit('<div class="no-content track-error" data-track-error="404"><div><p>You are blocking '. htmlspecialchars($user['user_name'], ENT_QUOTES) .'. Unblock them to view their post.</p></div></div>');
    } else {
        $check_blocked = $dbc->prepare('SELECT * FROM blocks WHERE block_to = ? AND block_by = ?');
        $check_blocked->bind_param('ii', $_SESSION['user_id'], $user['user_id']);
        $check_blocked->execute();
        $result_blocked = $check_blocked->get_result();
        if (!$result_blocked->num_rows == 0) {
            exit('<div class="no-content track-error" data-track-error="404"><div><p>You were blocked by '. htmlspecialchars($user['user_name'], ENT_QUOTES) .' so you cannot view their post.</p></div></div>');
        }
    }
}

if ($post['deleted'] == 1 && $post['post_by_id'] != $_SESSION['user_id']) {
    echo '<div class="no-content track-error" data-track-error="deleted"><div><p class="deleted-message">
            Deleted by administrator.<br>
            Post ID: '. $post['id'] .'
          </p></div></div>';
} elseif ($post['deleted'] == 2) {
    echo '<div class="no-content track-error" data-track-error="deleted"><div><p>Deleted by poster.</p></div></div>';
} else {
    echo '<div class="main-column"><div class="post-list-outline"><div id="post-main">';

    if ($post['post_title'] != null) {
        $get_title = $dbc->prepare('SELECT * FROM titles WHERE title_id = ? LIMIT 1');
        $get_title->bind_param('i', $post['post_title']);
        $get_title->execute();
        $title_result = $get_title->get_result();
        $title = $title_result->fetch_assoc();
    
        echo '<meta property="og:title" content="Post to '. htmlspecialchars($title['title_name'], ENT_QUOTES) .' - Cedar">
        <meta property="og:url" content="https://cedar.doctor/posts/'. $post['id'] .'">
        <meta property="og:description" content="'. htmlspecialchars($user['nickname'], ENT_QUOTES) .' : '. (mb_strlen($post['text']) > 46 ?  htmlspecialchars(mb_substr($post['text'], 0, 47)) .'...' : htmlspecialchars($post['text'], ENT_QUOTES)) .' - Cedar">

        <header class="community-container"><meta http-equiv="Content-Type" content="text/html; charset=gb18030">
        <h1 class="community-container-heading">
        <a href="/titles/'. $title['title_id'] .'"><img src="'. $title['title_icon'] .'" class="community-icon">'. htmlspecialchars($title['title_name'], ENT_QUOTES) .'</a>
        </h1>
        </header>';
    } else {
        echo '<meta property="og:title" content="Post to Activity Feed - Cedar">
        <meta property="og:url" content="https://cedar.doctor/posts/'. $post['id'] .'">
        <meta property="og:description" content="'. htmlspecialchars($user['nickname'], ENT_QUOTES) .' : '. (mb_strlen($post['text']) > 46 ?  htmlspecialchars(mb_substr($post['text'], 0, 47)) .'...' : htmlspecialchars($post['text'], ENT_QUOTES)) .' - Cedar">

        <header class="community-container">
        <h1 class="community-container-heading">
        <a><img src="/assets/img/feed-icon.png" class="community-icon">Activity Feed</a>
        </h1>
        </header>';
    }

    if ($post['post_by_id'] == $_SESSION['user_id'] && $post['deleted'] != 1) {
        echo '<div class="edit-buttons-content">
        <button type="button" class="symbol button edit-button rm-post-button" data-action="/posts/'. $post['id'] .'/delete"><span class="symbol-label">Delete</span></button>
        <button type="button" class="symbol button edit-button edit-post-button"><span class="symbol-label">Edit</span></button>';
        if (!empty($post['post_image'])) {
            if ($user['fav_post'] == $post['id']) {
                echo '<button type="button" class="symbol button edit-button profile-post-button'. ($user['fav_post'] == $post['id'] ? ' done' : '') .'" data-action="/settings/profile_post.unset.json"><span class="symbol-label">Set as favorite post</span></button>';
            } else {
                echo '<button type="button" class="symbol button edit-button profile-post-button" data-action="/posts/'. $post['id'] .'/favorite"><span class="symbol-label">Set as favorite post</span></button>';
            }
        }
        echo '</div>';
    }

        echo '<div id="user-content">
		  <a href="/users/'. $user['user_name'] .'/posts" class="icon-container'. ($user['user_level'] > 1 ? ' verified' : '') . ($user['hide_online'] == 0 ? (strtotime($user['last_online']) > time() - 35 ? ' online' : ' offline') : '') .'">
		    <img src="'. (isset($user['name_color']) ? printFace($user['user_face'], $post['feeling_id'], $user['name_color']) : printFace($user['user_face'], $post['feeling_id'])) .'" id="icon">
		  </a>
		  <div class="user-name-content">
			<p class="user-name">
			  <a href="/users/'. $user['user_name'] .'/posts" '.(isset($user['name_color']) ? 'style="color: '. $user['name_color'] .'"' : '').'>'. htmlspecialchars($user['nickname'], ENT_QUOTES) .'</a><span id="user-id">'. $user['user_name'] .'</span></p><p class="timestamp-container"><span class="timestamp">'. humanTiming(strtotime($post['date_time'])) .'</span>'. ($post['edited'] == 1 ? '<span class="spoiler"> · Edited</span>' : '') .'</p></div></div><div id="main-post-body">';

    echo '<div id="post-edit" class="none">
<form action="/posts/'. $post['id'] .'/edit" id="edit-form" method="post">
<div class="post-count-container">
            <div class="textarea-feedback" style="float:left;">
                <font color="#646464" style="font-size: 13px; padding: 0 3px 0 7px;">2000</font> Characters Remaining
            </div>
        </div>';
    if (!strpos($user['user_face'], "imgur") && !strpos($user['user_face'], "cloudinary")) {
        echo '<div class="feeling-selector js-feeling-selector test-feeling-selector"><label class="symbol feeling-button feeling-button-normal checked"><input type="radio" name="feeling_id" value="0" checked=""><span class="symbol-label">normal</span></label><label class="symbol feeling-button feeling-button-happy"><input type="radio" name="feeling_id" value="1"><span class="symbol-label">happy</span></label><label class="symbol feeling-button feeling-button-like"><input type="radio" name="feeling_id" value="2"><span class="symbol-label">like</span></label><label class="symbol feeling-button feeling-button-surprised"><input type="radio" name="feeling_id" value="3"><span class="symbol-label">surprised</span></label><label class="symbol feeling-button feeling-button-frustrated"><input type="radio" name="feeling_id" value="4"><span class="symbol-label">frustrated</span></label><label class="symbol feeling-button feeling-button-puzzled"><input type="radio" name="feeling_id" value="5"><span class="symbol-label">puzzled</span></label></div>';
    }

    echo '<div class="textarea-container">
<textarea name="body" class="textarea-text textarea " maxlength="2000" placeholder="Edit your post." data-required="">'. $post['text'] .'</textarea>
</div>
<div class="post-form-footer-options">
</div>
<div class="form-buttons">
<button type="button" class="cancel-button gray-button">Cancel</button>
<button type="submit" class="post-button black-button disabled" disabled="">Submit</button>
</div>
</form>
</div>';

    echo '<div id="the-post">';

    if ($post['deleted'] == 1) {
        echo '<p class="deleted-message">
            Deleted by administrator.<br>
            Post ID: '. $post['id'] .'
          </p>';
    }

    $post['text'] = htmlspecialchars($post['text'], ENT_QUOTES);

    $post['text'] = preg_replace("/^&gt;(.*)\n|^&gt;(.*)/m", '<span class="gt">$0</span>', $post['text']);

    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $post['text'], $video_id);

    $post['text'] = preg_replace('|([\w\d]*)\s?(https?://([\d\w\.-]+\.[\w\.]{2,6})[^\s\]\[\<\>]*/?)|i', '$1 <a href="$2" target="_blank" class="post-link">$2</a>', $post['text']);

    $post['text'] = preg_replace_callback('/@(.+?(?=( |$)|\r\n))/m', function ($m) {
        global $dbc;
        $get_mention_user = $dbc->prepare('SELECT * FROM users WHERE user_name = ? LIMIT 5');
        $get_mention_user->bind_param('s', $m[1]);
        $get_mention_user->execute();
        $mention_user_result = $get_mention_user->get_result();

        if (!$mention_user_result->num_rows == 0) {
            $mention_user = $mention_user_result->fetch_assoc();
            return '<a class="mention-link" href="/users/'. $mention_user['user_name'] .'/posts">@'. $mention_user['user_name'] .'</a>';
        } else {
            return $m[0];
        }
    }, $post['text']);

    $post['text'] = preg_replace_callback('/:(.\w+:)/m', function ($m) {
        $emoji_name = mb_substr($m[1], 0, -1);
        global $dbc;
        $get_emoji = $dbc->prepare('SELECT * FROM emojis WHERE emoji_name = ? LIMIT 1');
        $get_emoji->bind_param('s', $emoji_name);
        $get_emoji->execute();
        $emoji_result = $get_emoji->get_result();

        if (!$emoji_result->num_rows == 0) {
            $emoji = $emoji_result->fetch_assoc();
            return '<img class="emoji" draggable="false" title=":'. $emoji['emoji_name'] .':" alt=":'. $emoji['emoji_name'] .':" src="'. htmlspecialchars($emoji['emoji_url'], ENT_QUOTES) .'">';
        } else {
            return $m[0];
        }
    }, $post['text']);

    $post['text'] = preg_replace_callback('/( |^)#(.+?(?=( |$)|\r\n))/m', function ($m) use ($post) {
        return '<a class="mention-link" href="/titles/'. $post['post_title'] .'?q=%23'. $m[2] .'"> #'. $m[2] .'</a>';
    }, $post['text']);

    echo '<div id="post-body">'. nl2br($post['text'], ENT_QUOTES) .'</div>';

    if (!empty($post['post_image'])) {
        if (mb_substr($post['post_image'], -4) == '.mp3' || mb_substr($post['post_image'], -4) == '.ogg') {
            echo '<div class="screenshot-container still-image">
            <audio controls="" preload="none">
            <source src="'. $post['post_image'] .'">
            Your browser does not support the audio element.
            </audio>
            <script>$("audio").each(function(){ this.volume = 0.2; });</script>
            </div>';
        } elseif (mb_substr($post['post_image'], -5) == '.webm') {
            echo '<div class="screenshot-container still-image">
            <video controls="" preload="metadata">
            <source src="'. $post['post_image'] .'#t=0">
            Your browser does not support the video element.
            </video>
            <script>$("video").each(function(){ this.volume = 0.2; });</script>
            </div>';
        } else {
            echo '<div class="screenshot-container still-image"><img src="'. $post['post_image'] .'"></div>';
        }
    }

    if (!empty($video_id[1])) {
        echo '<div class="screenshot-container video"><iframe class="youtube-player" type="text/html" width="490" height="276" src="https://www.youtube.com/embed/'. $video_id[1] .'?rel=0&amp;modestbranding=1&amp;iv_load_policy=3" allowfullscreen frameborder="0"></iframe></div>';
    }

    echo '<div id="post-meta">';

    $yeah_count = $dbc->prepare('SELECT COUNT(yeah_by) FROM yeahs WHERE type = "post" AND yeah_post = ?');
    $yeah_count->bind_param('i', $post['id']);
    $yeah_count->execute();
    $result_count = $yeah_count->get_result();
    $yeah_amount = $result_count->fetch_assoc();

    $nah_count = $dbc->prepare('SELECT COUNT(nah_by) FROM nahs WHERE type = 0 AND nah_post = ?');
    $nah_count->bind_param('i', $post['id']);
    $nah_count->execute();
    $result_count = $nah_count->get_result();
    $nah_amount = $result_count->fetch_assoc();

    $yeahs = $yeah_amount['COUNT(yeah_by)'] - $nah_amount['COUNT(nah_by)'];

        echo '<button class="yeah symbol';

    if (!empty($_SESSION['signed_in']) && checkYeahAdded($post['id'], 'post', $_SESSION['user_id'])) {
        echo ' yeah-added';
    }

    echo '"';

    if (empty($_SESSION['signed_in']) || checkPostCreator($post['id'], $_SESSION['user_id'])) {
        echo ' disabled ';
    }

    echo 'id="'. $post['id'] .'" data-track-label="post"><span class="yeah-button-text">';

    if (!empty($_SESSION['signed_in']) && checkYeahAdded($post['id'], 'post', $_SESSION['user_id'])) {
        echo 'Unyeah';
    } else {
        echo 'Yeah!';
    }

    echo '</span></button>';







    echo '<button class="nah symbol';

    if (!empty($_SESSION['signed_in']) && checkNahAdded($post['id'], 0, $_SESSION['user_id'])) {
        echo ' nah-added';
    }

    echo '"';

    if (empty($_SESSION['signed_in']) || checkPostCreator($post['id'], $_SESSION['user_id'])) {
        echo ' disabled ';
    }

    echo 'id="'. $post['id'] .'" data-track-label="0"><span class="nah-button-text">';

    if (!empty($_SESSION['signed_in']) && checkNahAdded($post['id'], 0, $_SESSION['user_id'])) {
        echo 'Un-nah.';
    } else {
        echo 'Nah...';
    }

    echo '</span></button>';


    echo '<div class="empathy symbol" yeahs="'. $yeah_amount['COUNT(yeah_by)']  .'" nahs="'. $nah_amount['COUNT(nah_by)']  .'" title="'. $yeah_amount['COUNT(yeah_by)'] .' '. ($yeah_amount['COUNT(yeah_by)'] == 1 ? 'Yeah' : 'Yeahs') .' / '. $nah_amount['COUNT(nah_by)'] .' '. ($nah_amount['COUNT(nah_by)'] == 1 ? 'Nah' : 'Nahs') .'"><span class="yeah-count">'. $yeahs .'</span></div>';

    $reply_count = $dbc->prepare('SELECT COUNT(reply_id) FROM replies WHERE reply_post = ? AND deleted = 0');
    $reply_count->bind_param('i', $post['id']);
    $reply_count->execute();
    $result_count = $reply_count->get_result();
    $reply_amount = $result_count->fetch_assoc();

    echo '<div class="reply symbol"><span id="reply-count">'. $reply_amount['COUNT(reply_id)'] .'</span></div>
		</div></div></div></div>';

    //yeah content

    if ($post['deleted'] != 1) {
        $get_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ?');
        $get_user->bind_param('i', $_SESSION['user_id']);
        $get_user->execute();
        $user_result = $get_user->get_result();
        $user = $user_result->fetch_assoc();

        if (empty($yeah_amount['COUNT(yeah_by)'])) {
            echo '<div id="yeah-content" class="none">';
        } else {
            echo '<div id="yeah-content">' ;
        }

        if (!checkYeahAdded($post['id'], 'post', $_SESSION['user_id'])) {
            echo '<a href="/users/'. $user['user_name'] .'/posts" class="icon-container visitor'. ($user['user_level'] > 1 ? ' verified' : '') .'" style="display: none;">
				<img src="'. (isset($user['name_color']) ? printFace($user['user_face'], $post['feeling_id'], $user['name_color']) : printFace($user['user_face'], $post['feeling_id'])) .'" id="icon"></a>';
        } else {
            echo '<a href="/users/'. $user['user_name'] .'/posts" class="icon-container visitor'. ($user['user_level'] > 1 ? ' verified' : '') .'">
				<img src="'. (isset($user['name_color']) ? printFace($user['user_face'], $post['feeling_id'], $user['name_color']) : printFace($user['user_face'], $post['feeling_id'])) .'" id="icon"></a>';
        }

        if (!empty($_SESSION['signed_in'])) {
            $yeahs_by = $dbc->prepare('SELECT * FROM users, yeahs WHERE users.user_id = yeahs.yeah_by AND yeahs.yeah_post = ? AND NOT users.user_id = ? ORDER BY date_time DESC LIMIT 30');
            $yeahs_by->bind_param('ii', $post['id'], $_SESSION['user_id']);
        } else {
            $yeahs_by = $dbc->prepare('SELECT * FROM users, yeahs WHERE users.user_id = yeahs.yeah_by AND yeahs.yeah_post = ? ORDER BY date_time DESC LIMIT 30');
            $yeahs_by->bind_param('i', $post['id']);
        }
        $yeahs_by->execute();
        $yeahs_by_result = $yeahs_by->get_result();

        while ($yeah_by = $yeahs_by_result->fetch_array()) {
            echo '<a href="/users/'. $yeah_by['user_name'] .'/posts" class="icon-container'. ($yeah_by['user_level'] > 1 ? ' verified' : '') .'">
				  <img src="'. (isset($yeah_by['name_color']) ? printFace($yeah_by['user_face'], $post['feeling_id'], $yeah_by['name_color']) : printFace($yeah_by['user_face'], $post['feeling_id'])) .'" id="icon"></a>';
        }

        echo '</div>';

        //comments
        echo '<div id="reply-content"><h2 class="reply-label">Comments</h2><ul class="list reply-list test-reply-list">';
        $search_replies = $dbc->prepare('SELECT * FROM replies INNER JOIN users ON user_id = reply_by_id INNER JOIN profiles ON users.user_id = profiles.user_id WHERE reply_post = ? AND deleted < 2 ORDER BY date_time ASC');
        $search_replies->bind_param('i', $id);
        $search_replies->execute();
        $replies_result = $search_replies->get_result();

        if ($replies_result->num_rows == 0) {
            echo '<div class="no-reply-content"><div><p>This post has no comments.</p></div></div>';
        } else {
            $number_of_replies = 0;
            while ($replies = $replies_result->fetch_array()) {
                if (!empty($_SESSION['signed_in']) && $_SESSION['user_id'] != $replies['user_id']) {
                    $check_blocking = $dbc->prepare('SELECT * FROM blocks WHERE block_by = ? AND block_to = ?');
                    $check_blocking->bind_param('ii', $_SESSION['user_id'], $replies['user_id']);
                    $check_blocking->execute();
                    $result_blocking = $check_blocking->get_result();
                    if (!$result_blocking->num_rows == 0) {
                        continue;
                    } else {
                        $check_blocked = $dbc->prepare('SELECT * FROM blocks WHERE block_to = ? AND block_by = ?');
                        $check_blocked->bind_param('ii', $_SESSION['user_id'], $replies['user_id']);
                        $check_blocked->execute();
                        $result_blocked = $check_blocked->get_result();
                        if (!$result_blocked->num_rows == 0) {
                            continue;
                        }
                    }
                }

                echo '<li class="post'. ($replies['reply_by_id'] == $post['post_by_id']?' my' : '') .' trigger" data-href="/replies/'. $replies['reply_id'] .'">';
                printReply($replies);
                $number_of_replies++;
            }
            if ($number_of_replies == 0) {
                echo '<div class="no-reply-content"><div><p>This post has no comments.</p></div></div>';
            }
        }

        echo '</ul></div><h2 class="reply-label">Add a comment</h2>';

        include 'postReply.php';

        echo '

	        </div></div></div>';
    }
}
