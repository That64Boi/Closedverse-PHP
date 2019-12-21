<?php
require_once('lib/htm.php');
printHeader(0);

$search_reply = $dbc->prepare('SELECT * FROM replies WHERE reply_id = ? LIMIT 1');
$search_reply->bind_param('i', $id);
$search_reply->execute();
$reply_result = $search_reply->get_result();

if ($reply_result->num_rows == 0) {
    exit('<title>Cedar - Error</title><div class="no-content track-error" data-track-error="404"><div><p>The reply could not be found.</p></div></div>');
}

$reply = $reply_result->fetch_assoc();

if ($reply['deleted'] == 1 && $reply['reply_by_id'] != $_SESSION['user_id']) {
    exit('<div class="no-content track-error" data-track-error="deleted"><div><p class="deleted-message">
        Deleted by administrator.<br>
        Reply ID: '. $reply['reply_id'] .'
        </p></div></div>');
} elseif ($reply['deleted'] == 2) {
    exit('<div class="no-content track-error" data-track-error="deleted"><div><p>Deleted by the author of the comment.</p></div></div>');
}

$search_post = $dbc->prepare('SELECT id, post_title, text, feeling_id, nickname, user_face, name_color FROM posts INNER JOIN users ON user_id = post_by_id WHERE id = ? LIMIT 1');
$search_post->bind_param('i', $reply['reply_post']);
$search_post->execute();
$post_result = $search_post->get_result();
$post = $post_result->fetch_assoc();

$get_title = $dbc->prepare('SELECT title_id, title_name, title_icon FROM titles WHERE title_id = ? LIMIT 1');
$get_title->bind_param('i', $post['post_title']);
$get_title->execute();
$title_result = $get_title->get_result();
$title = $title_result->fetch_assoc();

$get_user = $dbc->prepare('SELECT * FROM users INNER JOIN profiles ON profiles.user_id = users.user_id WHERE users.user_id = ?');
$get_user->bind_param('i', $reply['reply_by_id']);
$get_user->execute();
$user_result = $get_user->get_result();
$user = $user_result->fetch_assoc();

if (!empty($_SESSION['signed_in']) && $_SESSION['user_id'] != $user['user_id']) {
        $check_blocking = $dbc->prepare('SELECT * FROM blocks WHERE block_by = ? AND block_to = ?');
        $check_blocking->bind_param('ii', $_SESSION['user_id'], $user['user_id']);
        $check_blocking->execute();
        $result_blocking = $check_blocking->get_result();
    if (!$result_blocking->num_rows == 0) {
        exit('<div class="no-content track-error" data-track-error="404"><div><p>You are blocking '. htmlspecialchars($user['user_name'], ENT_QUOTES) .'. Unblock them to view their reply.</p></div></div>');
    } else {
        $check_blocked = $dbc->prepare('SELECT * FROM blocks WHERE block_to = ? AND block_by = ?');
        $check_blocked->bind_param('ii', $_SESSION['user_id'], $user['user_id']);
        $check_blocked->execute();
        $result_blocked = $check_blocked->get_result();
        if (!$result_blocked->num_rows == 0) {
            exit('<div class="no-content track-error" data-track-error="404"><div><p>You were blocked by '. htmlspecialchars($user['user_name'], ENT_QUOTES) .' so you cannot view their reply.</p></div></div>');
        }
    }
}

$yeah_count = $dbc->prepare('SELECT COUNT(yeah_by) FROM yeahs WHERE type = "reply" AND yeah_post = ?');
$yeah_count->bind_param('i', $reply['reply_id']);
$yeah_count->execute();
$result_count = $yeah_count->get_result();
$yeah_amount = $result_count->fetch_assoc();

$nah_count = $dbc->prepare('SELECT COUNT(nah_by) FROM nahs WHERE type = 1 AND nah_post = ?');
$nah_count->bind_param('i', $reply['reply_id']);
$nah_count->execute();
$result_count = $nah_count->get_result();
$nah_amount = $result_count->fetch_assoc();

$yeahs = $yeah_amount['COUNT(yeah_by)'] - $nah_amount['COUNT(nah_by)'];

echo '
    	<div class="main-column"><div class="post-list-outline">
    	  <a class="post-permalink-button info-ticker" href="/posts/'. $post['id'] .'">
    	    <span class="icon-container"><img src="'. (isset($post['name_color']) ? printFace($post['user_face'], $post['feeling_id'], $post['name_color']) : printFace($post['user_face'], $post['feeling_id'])) .'" id="icon"></span>
    	    <span>View <span class="post-user-description"'. (isset($post['name_color']) ? 'style="color: '. $post['name_color'] .'"' : '') .'>'. htmlspecialchars($post['nickname'], ENT_QUOTES) .'\'s post ('. (mb_strlen($post['text']) > 17 ? htmlspecialchars(mb_substr($post['text'], 0, 17), ENT_QUOTES) . '...' : htmlspecialchars($post['text'], ENT_QUOTES)) .')</span> for this comment.</span>
    	  </a>
    	</div>
    	<div class="post-list-outline">
    	  <div id="post-main" class="reply-permalink-post">';
if ($post['post_title'] != null) {
    echo '<p class="community-container">
    	      <a href="/titles/'. $title['title_id'] .'"><img src="'. $title['title_icon'] .'" class="community-icon">'. htmlspecialchars($title['title_name'], ENT_QUOTES) .'</a>
              </p>';
} else {
    echo '<p class="community-container"><a><img src="/assets/img/feed-icon.png" class="community-icon">Activity Feed</a></p>';
}

if ($reply['reply_by_id'] == $_SESSION['user_id'] && $reply['deleted'] != 1) {
    echo '<div class="edit-buttons-content">
            <button type="button" class="symbol button edit-button rm-post-button" data-action="/replies/'. $reply['reply_id'] .'/delete"><span class="symbol-label">Delete</span></button>
            <button type="button" class="symbol button edit-button edit-post-button"><span class="symbol-label">Edit</span></button>
            </div>';
}

              echo '<div id="user-content">
              <title>Cedar - '. htmlspecialchars($user['nickname'], ENT_QUOTES) .'\'s Comment</title>
        <a href="/users/'. $user['user_name'] .'/posts" class="icon-container'. ($user['user_level'] > 1 ? ' verified' : '') . ($user['hide_online'] == 0 ? (strtotime($user['last_online']) > time() - 35 ? ' online' : ' offline') : '') .'"><img src="'. (isset($user['name_color']) ? printFace($user['user_face'], $reply['feeling_id'], $user['name_color']) : printFace($user['user_face'], $reply['feeling_id'])) .'" id="icon"></a>
        <div class="user-name-content">
          <p class="user-name"><a href="/users/'. $user['user_name'] .'/posts" '.(isset($user['name_color']) ? 'style="color: '. $user['name_color'] .'"' : '').'>'. htmlspecialchars($user['nickname'], ENT_QUOTES) .'</a></p>
          <p class="timestamp-container">
            <span class="timestamp">'. humanTiming(strtotime($reply['date_time'])) .'</span>'. ($reply['edited'] == 1 ? '<span class="spoiler"> · Edited</span>' : '') .'
          </p>
        </div>
      </div>';

if ($reply['deleted'] == 1) {
    echo '<p class="deleted-message">
    Deleted by administrator.<br>
    Reply ID: '. $reply['reply_id'] .'
    </p>';
}

echo '<div id="body">';

echo '<div id="post-edit" class="none">
<form action="/replies/'. $reply['reply_id'] .'/edit" id="edit-form" method="post">
<div class="post-count-container">
            <div class="textarea-feedback" style="float:left;">
                <font color="#646464" style="font-size: 13px; padding: 0 3px 0 7px;">2000</font> Characters Remaining
            </div>
        </div>';
if (!strpos($user['user_face'], "imgur") && !strpos($user['user_face'], "cloudinary")) {
    echo '<div class="feeling-selector js-feeling-selector test-feeling-selector"><label class="symbol feeling-button feeling-button-normal checked"><input type="radio" name="feeling_id" value="0" checked=""><span class="symbol-label">normal</span></label><label class="symbol feeling-button feeling-button-happy"><input type="radio" name="feeling_id" value="1"><span class="symbol-label">happy</span></label><label class="symbol feeling-button feeling-button-like"><input type="radio" name="feeling_id" value="2"><span class="symbol-label">like</span></label><label class="symbol feeling-button feeling-button-surprised"><input type="radio" name="feeling_id" value="3"><span class="symbol-label">surprised</span></label><label class="symbol feeling-button feeling-button-frustrated"><input type="radio" name="feeling_id" value="4"><span class="symbol-label">frustrated</span></label><label class="symbol feeling-button feeling-button-puzzled"><input type="radio" name="feeling_id" value="5"><span class="symbol-label">puzzled</span></label></div>';
}

    echo '<div class="textarea-container">
<textarea name="body" class="textarea-text textarea " maxlength="2000" placeholder="Edit your comment." data-required="">'. $reply['text'] .'</textarea>
</div>
<div class="post-form-footer-options">
</div>
<div class="form-buttons">
<button type="button" class="cancel-button gray-button">Cancel</button>
<button type="submit" class="post-button black-button disabled" disabled="">Submit</button>
</div>
</form>
</div>';

$reply['text'] = preg_replace("/^&gt;(.*)\n|^&gt;(.*)/m", '<span class="gt">$0</span>', $reply['text']);

$reply['text'] = preg_replace('|([\w\d]*)\s?(https?://([\d\w\.-]+\.[\w\.]{2,6})[^\s\]\[\<\>]*/?)|i', '$1 <a href="$2" target="_blank" class="post-link">$2</a>', $reply['text']);

$reply['text'] = preg_replace_callback('/@(.+?(?=( |$)|\r\n))/m', function ($m) {
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
}, $reply['text']);

$reply['text'] = preg_replace_callback('/:(.\w+:)/m', function ($m) {
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
}, $reply['text']);

echo '<div id="the-post">
<p class="reply-content-text">'. nl2br($reply['text']) .'</p>';

if (!empty($reply['reply_image'])) {
    if (substr($reply['reply_image'], -4) == '.mp3' || substr($reply['reply_image'], -4) == '.ogg') {
                echo '<div class="screenshot-container still-image">
                <audio controls="" preload="none">
                <source src="'. $reply['reply_image'] .'">
                Your browser does not support the audio element.
                </audio>
                <script>$("audio").each(function(){ this.volume = 0.2; });</script>
                </div>';
    } else {
        echo '<div class="screenshot-container still-image"><img src="'. $reply['reply_image'] .'"></div>';
    }
}

//yeahs
echo '<div id="post-meta">
<button class="yeah symbol';

if (!empty($_SESSION['signed_in']) && checkYeahAdded($reply['reply_id'], 'reply', $_SESSION['user_id'])) {
    echo ' yeah-added';
}

echo '"';

if (empty($_SESSION['signed_in']) || checkReplyCreator($reply['reply_id'], $_SESSION['user_id'])) {
    echo ' disabled ';
}

echo 'id="'. $reply['reply_id'] .'" data-track-label="reply"><span class="yeah-button-text">';

if (!empty($_SESSION['signed_in']) && checkYeahAdded($reply['reply_id'], 'reply', $_SESSION['user_id'])) {
    echo 'Unyeah';
} else {
    echo 'Yeah!';
}

echo '</span></button>
<button class="nah symbol';

if (!empty($_SESSION['signed_in']) && checkNahAdded($reply['reply_id'], 1, $_SESSION['user_id'])) {
    echo ' nah-added';
}

echo '"';

if (empty($_SESSION['signed_in']) || checkReplyCreator($reply['reply_id'], $_SESSION['user_id'])) {
    echo ' disabled ';
}

echo 'id="'. $reply['reply_id'] .'" data-track-label="1"><span class="nah-button-text">';

if (!empty($_SESSION['signed_in']) && checkNahAdded($reply['reply_id'], 1, $_SESSION['user_id'])) {
    echo 'Un-nah.';
} else {
    echo 'Nah...';
}

echo '</span></button>
<div class="empathy symbol" yeahs="'. $yeah_amount['COUNT(yeah_by)']  .'" nahs="'. $nah_amount['COUNT(nah_by)']  .'" title="'. $yeah_amount['COUNT(yeah_by)'] .' '. ($yeah_amount['COUNT(yeah_by)'] == 1 ? 'Yeah' : 'Yeahs') .' / '. $nah_amount['COUNT(nah_by)'] .' '. ($nah_amount['COUNT(nah_by)'] == 1 ? 'Nah' : 'Nahs') .'"><span class="yeah-count">'. $yeahs .'</span></div></div>';

//yeah content
$get_user = $dbc->prepare('SELECT * FROM users WHERE users.user_id = ?');
$get_user->bind_param('s', $_SESSION['user_id']);
$get_user->execute();
$user_result = $get_user->get_result();
$user = $user_result->fetch_assoc();

if (empty($yeah_amount['COUNT(yeah_by)'])) {
    echo '<div id="yeah-content" class="none">';
} else {
    echo '<div id="yeah-content">';
}

if (!checkYeahAdded($reply['reply_id'], 'reply', $_SESSION['user_id'])) {
    echo '
    <a href="/users/'. $user['user_name'] .'/posts" class="icon-container'. ($user['user_level'] > 1 ? ' verified' : '') .' visitor" style="display: none;"><img src="'. (isset($user['name_color']) ? printFace($user['user_face'], $reply['feeling_id'], $user['name_color']) : printFace($user['user_face'], $reply['feeling_id'])) .'" id="icon"></a>';
} else {
    echo '<a href="/users/'. $user['user_name'] .'/posts" class="icon-container'. ($user['user_level'] > 1 ? ' verified' : '') .' visitor">
    <img src="'. (isset($user['name_color']) ? printFace($user['user_face'], $reply['feeling_id'], $user['name_color']) : printFace($user['user_face'], $reply['feeling_id'])) .'" id="icon"></a>';
}

if (!empty($_SESSION['signed_in'])) {
    $yeahs_by = $dbc->prepare('SELECT * FROM users, yeahs WHERE users.user_id = yeahs.yeah_by AND yeahs.yeah_post = ? AND NOT users.user_id = ? LIMIT 14');
    $yeahs_by->bind_param('ii', $reply['reply_id'], $_SESSION['user_id']);
} else {
    $yeahs_by = $dbc->prepare('SELECT * FROM users, yeahs WHERE users.user_id = yeahs.yeah_by AND yeahs.yeah_post = ?');
    $yeahs_by->bind_param('i', $reply['reply_id']);
}

$yeahs_by->execute();
$yeahs_by_result = $yeahs_by->get_result();

while ($yeah_by = $yeahs_by_result->fetch_array()) {
    echo '<a href="/users/'. $yeah_by['user_name'] .'/posts" class="icon-container'. ($yeah_by['user_level'] > 1 ? ' verified' : '') .'">
    <img src="' . (isset($yeah_by['name_color']) ? printFace($yeah_by['user_face'], $reply['feeling_id'], $yeah_by['name_color']) : printFace($yeah_by['user_face'], $reply['feeling_id'])) . '" id="icon">
    </a>';
}

echo '</div></div>';

?>
</div>