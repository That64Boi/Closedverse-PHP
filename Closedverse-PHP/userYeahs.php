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

    if (!empty($_SESSION['signed_in'])) {
        $check_is_admin = $dbc->prepare('SELECT user_level FROM users WHERE user_id = ?');
        $check_is_admin->bind_param('i', $_SESSION['user_id']);
        $check_is_admin->execute();
        $is_admin_result = $check_is_admin->get_result();
        $is_admin = $is_admin_result->fetch_assoc();
    }
    if ($user['hide_yeahs'] == 1 && $user['user_id'] != $_SESSION['user_id'] && (empty($_SESSION['signed_in']) || $is_admin['user_level'] == 0)) {
        exit();
    }

    if (!((isset($_GET['offset']) && is_numeric($_GET['offset'])) && isset($_GET['dateTime']))) {
        $tabTitle = htmlspecialchars($user['nickname'], ENT_QUOTES) .'\'s Yeahs - Closedverse';

        printHeader('');

        echo '<script>var loadOnScroll=true;</script><div id="sidebar" class="user-sidebar">';

        userContent($user, "yeahs");

        userSidebarSetting($user, 3);

        userInfo($user);

        echo '</div>
        <div class="main-column">
          <div class="post-list-outline">
            <h2 class="label">'. htmlspecialchars($user['nickname'], ENT_QUOTES) .'\'s Yeahs</h2>
            <div class="list post-list js-post-list" data-next-page-url="/users/'. $user['user_name'] .'/yeahs?offset=1&dateTime='.date("Y-m-d H:i:s").'">';

        $get_yeahs = $dbc->prepare('SELECT * FROM yeahs WHERE yeah_by = ? ORDER BY yeah_id DESC LIMIT 20');
        $get_yeahs->bind_param('i', $user['user_id']);
    } else {
        $offset = ($_GET['offset'] * 25);
        $dateTime = htmlspecialchars($_GET['dateTime']);
        $get_yeahs = $dbc->prepare('SELECT * FROM yeahs WHERE yeah_by = ? AND date_time < ? ORDER BY date_time DESC LIMIT 20 OFFSET ?');
        $get_yeahs->bind_param('isi', $user['user_id'], $dateTime, $offset);
    }

    $get_yeahs->execute();
    $yeahs_result = $get_yeahs->get_result();

    if (!$yeahs_result->num_rows == 0) {
        while ($yeahs = $yeahs_result->fetch_array()) {
            if ($yeahs['type'] == "post") {
                $get_posts = $dbc->prepare('SELECT * FROM posts LEFT JOIN titles ON title_id = post_title INNER JOIN users ON user_id = post_by_id WHERE id = ? AND deleted = 0 LIMIT 1');
                $get_posts->bind_param('i', $yeahs['yeah_post']);
                $get_posts->execute();
                $posts_result = $get_posts->get_result();
                if ($posts_result->num_rows==0) {
                    continue;
                }
                $posts = $posts_result->fetch_assoc();

                echo '<div data-href="/posts/'. $posts['id'] .'" class="post post-subtype-default trigger">
                <p class="community-container">';

                if ($posts['post_title'] != null) {
                    echo '<a class="test-community-link" href="/titles/'. $posts['title_id'] .'"><img src="'. $posts['title_icon'] .'" class="community-icon">'. htmlspecialchars($posts['title_name'], ENT_QUOTES) .'</a></p>';
                } else {
                    echo '<a class="test-community-link"><img src="/assets/img/feed-icon.png" class="community-icon">Activity Feed</a></p>';
                }

                printPost($posts, 1);
            } else {
                //replies
                $get_replies = $dbc->prepare('SELECT * FROM replies WHERE reply_id = ? LIMIT 1');
                $get_replies->bind_param('i', $yeahs['yeah_post']);
                $get_replies->execute();
                $replies_result = $get_replies->get_result();
                if ($replies_result->num_rows == 0) {
                    $delete_yeah = $dbc->prepare('DELETE FROM yeahs WHERE yeah_post = ? AND type = ?');
                    $delete_yeah->bind_param('ii', $yeahs['yeah_post'], $yeahs['type']);
                    $delete_yeah->execute();
                    continue;
                }
                $replies = $replies_result->fetch_assoc();

                $get_user_post = $dbc->prepare('SELECT users.* FROM users, posts WHERE users.user_id = posts.post_by_id AND posts.id = ? LIMIT 1');
                $get_user_post->bind_param('i', $replies['reply_post']);
                $get_user_post->execute();
                $user_post_result = $get_user_post->get_result();
                $user_post = $user_post_result->fetch_assoc();

                $get_reply_post = $dbc->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
                $get_reply_post->bind_param('i', $replies['reply_post']);
                $get_reply_post->execute();
                $reply_post_result = $get_reply_post->get_result();
                $reply_post = $reply_post_result->fetch_assoc();

                $get_reply_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
                $get_reply_user->bind_param('i', $replies['reply_by_id']);
                $get_reply_user->execute();
                $reply_user_result = $get_reply_user->get_result();
                $reply_user = $reply_user_result->fetch_assoc();

                echo '<div data-href="/replies/'. $replies['reply_id'] .'" class="post post-subtype-default trigger">
                  <p class="community-container">
                    <a class="test-community-link" href="/posts/'. $replies['reply_post'] .'"><img src="'. printFace($user_post['user_face'], $reply_post['feeling_id']) .'" class="community-icon">Comment on '. htmlspecialchars($user_post['nickname'], ENT_QUOTES) .'\'s Post</a>
                  </p>
                  <a href="/users/'. $reply_user['user_name'] .'/posts" class="icon-container';

                if ($reply_user['user_level'] > 1) {
                    echo ' verified';
                }

                echo '"><img src="'. printFace($reply_user['user_face'], $replies['feeling_id']) .'" id="icon"></a><p class="user-name"><a href="/users/'. $reply_user['user_name'] .'/posts">'. htmlspecialchars($reply_user['nickname'], ENT_QUOTES) .'</a></p><p class="timestamp-container"><a id="timestamp">' .
                humanTiming(strtotime($replies['date_time'])) . '</a></p><div id="body">';

                if (!empty($replies['reply_image'])) {
                    echo '<div class="screenshot-container"><img src="' . $replies['reply_image'] . '"></div>';
                }

                $replies['text'] = preg_replace_callback('/:(.\w+:)/m', function ($m) {
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
                }, $replies['text']);

                echo '<div id="post-body">'. (mb_strlen($replies['text']) > 199 ? mb_substr($replies['text'], 0, 200) . '...' : $replies['text']) .'</div><div id="post-meta">';
                                $yeah_count = $dbc->prepare('SELECT COUNT(yeah_by) FROM yeahs WHERE type = "reply" AND yeah_post = ?');
                $yeah_count->bind_param('i', $replies['reply_id']);
                $yeah_count->execute();
                $result_count = $yeah_count->get_result();
                $yeah_amount = $result_count->fetch_assoc();

                $nah_count = $dbc->prepare('SELECT COUNT(nah_by) FROM nahs WHERE type = 1 AND nah_post = ?');
                $nah_count->bind_param('i', $replies['reply_id']);
                $nah_count->execute();
                $result_count = $nah_count->get_result();
                $nah_amount = $result_count->fetch_assoc();

                $yeahs = $yeah_amount['COUNT(yeah_by)'] - $nah_amount['COUNT(nah_by)'];

                echo '<button class="yeah symbol';

                if (!empty($_SESSION['signed_in']) && checkYeahAdded($replies['reply_id'], 'reply', $_SESSION['user_id'])) {
                    echo ' yeah-added';
                }

                echo '"';

                if (empty($_SESSION['signed_in']) || checkReplyCreator($replies['reply_id'], $_SESSION['user_id'])) {
                    echo ' disabled ';
                }

                echo 'id="'. $replies['reply_id'] .'" data-track-label="reply"><span class="yeah-button-text">';

                if (!empty($_SESSION['signed_in']) && checkYeahAdded($replies['reply_id'], 'reply', $_SESSION['user_id'])) {
                    echo 'Unyeah';
                } else {
                    echo 'Yeah!';
                }

                echo '</span></button>';



                echo '<button class="nah symbol';

                if (!empty($_SESSION['signed_in']) && checkNahAdded($replies['reply_id'], 1, $_SESSION['user_id'])) {
                    echo ' nah-added';
                }

                echo '"';

                if (empty($_SESSION['signed_in']) || checkReplyCreator($replies['reply_id'], $_SESSION['user_id'])) {
                    echo ' disabled ';
                }

                echo 'id="'. $replies['reply_id'] .'" data-track-label="1"><span class="nah-button-text">';

                if (!empty($_SESSION['signed_in']) && checkNahAdded($replies['reply_id'], 1, $_SESSION['user_id'])) {
                    echo 'Un-nah.';
                } else {
                    echo 'Nah...';
                }

                echo '</span></button>';

                echo '<div class="empathy symbol" yeahs="'. $yeah_amount['COUNT(yeah_by)']  .'" nahs="'. $nah_amount['COUNT(nah_by)']  .'" title="'. $yeah_amount['COUNT(yeah_by)'] .' '. ($yeah_amount['COUNT(yeah_by)'] == 1 ? 'Yeah' : 'Yeahs') .' / '. $nah_amount['COUNT(nah_by)'] .' '. ($nah_amount['COUNT(nah_by)'] == 1 ? 'Nah' : 'Nahs') .'"><span class="yeah-count">'. $yeahs .'</span></div>';

                echo '</div></div></div>';
            }
        }
    } else {
        if (!((isset($_GET['offset']) && is_numeric($_GET['offset'])) && isset($_GET['dateTime']))) {
            echo '
            <div id="user-page-no-content" class="no-content"><div>
            <p>There are no posts with Yeahs yet.</p>
            </div></div>
            </div>';
        }
    }
}
