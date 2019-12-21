<?php
require_once('lib/htm.php');
require_once('lib/htmUsers.php');

$get_friend = $dbc->prepare('SELECT * FROM users WHERE user_name = ?');
$get_friend->bind_param('s', $action);
$get_friend->execute();
$friend_result = $get_friend->get_result();
if ($friend_result->num_rows == 0) {
    exit();
}

$friend = $friend_result->fetch_assoc();

// Check if they're really friends.

$check_friends = $dbc->prepare('SELECT * FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_two = ? AND user_one = ?)');
$check_friends->bind_param('iiii', $_SESSION['user_id'], $friend['user_id'], $_SESSION['user_id'], $friend['user_id']);
$check_friends->execute();
$check_friends_result = $check_friends->get_result();
if ($check_friends_result->num_rows == 0) {
    exit();
}

$friends = $check_friends_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = array();
    $image = null;

    if (empty($_POST['text_data'])) {
        $errors[] = 'Messages cannot be empty.';
    } elseif (mb_strlen($_POST['text_data']) > 2000) {
        $errors[] = 'Messages cannot be longer than 2000 characters.';
    }

    if (empty($_POST['feeling_id']) || strval($_POST['feeling_id']) >= 6) {
        $_POST['feeling_id'] = 0;
    }

    if (empty($_POST['pasted-image'])) {
        if (!empty($_FILES['image'])) {
            $img = $_FILES['image'];

            if (!empty($img['name'])) {
                //imageUpload() returns 1 if it fails and the image URL if successful
                $image = uploadImage($img, null, null);
                if ($image == 1) {
                    $errors[] = 'Image upload failed.';
                }
            }
        }
    } else {
        $get_keys = $dbc->prepare('SELECT * FROM cloudinary_keys ORDER BY RAND() LIMIT 1');
        $get_keys->execute();
        $key_result = $get_keys->get_result();
        $keys = $key_result->fetch_assoc();

        $pvars = array('file' => $_POST['pasted-image'],
        'api_key' => $keys['api_key'],
        'upload_preset' => $keys['preset']);
        $timeout = 30;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.cloudinary.com/v1_1/'. $keys['site_name'] .'/auto/upload');
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $pvars);
        $out = curl_exec($curl);
        curl_close($curl);
        $pms = json_decode($out, true);

        if (@$image=$pms['secure_url']) {
        } else {
            $errors[] = 'Image upload failed.';
        }
    }

    if (empty($errors)) {
        $id = mt_rand(0, 99999999);
        $send_message = $dbc->prepare('INSERT INTO messages (message_id, conversation_id, message_by, body, feeling, message_image) VALUES (?, ?, ?, ?, ?, ?)');
        $send_message->bind_param('iiisis', $id, $friends['conversation_id'], $_SESSION['user_id'], $_POST['text_data'], $_POST['feeling_id'], $image);
        $send_message->execute();

        $get_message = $dbc->prepare('SELECT * FROM messages INNER JOIN users ON user_id = message_by WHERE message_id = ?');
        $get_message->bind_param('i', $id);
        $get_message->execute();
        $message_result = $get_message->get_result();
        $message = $message_result->fetch_array();

        $update_last_message = $dbc->prepare('UPDATE conversations SET last_message = NOW() WHERE conversation_id = ?');
        $update_last_message->bind_param('i', $friends['conversation_id']);
        $update_last_message->execute();

        $message['body'] = htmlspecialchars($message['body'], ENT_QUOTES);

        $message['body'] = preg_replace("/^&gt;(.*)\n|^&gt;(.*)/m", '<span class="gt">$0</span>', $message['body']);

        $message['body'] = preg_replace('|([\w\d]*)\s?(https?://([\d\w\.-]+\.[\w\.]{2,6})[^\s\]\[\<\>]*/?)|i', '$1 <a href="$2" target="_blank" class="post-link">$2</a>', $message['body']);

        echo '<div class="post scroll'. ($message['message_by'] == $_SESSION['user_id'] ? ' my' : '') .'" style="display: none;">
            <a href="/users/'. htmlspecialchars($message['user_name'], ENT_QUOTES) .'/posts" class="icon-container'. ($message['user_level'] > 1 ? ' verified' : '') . ($message['hide_online'] == 0 ? (strtotime($message['last_online']) > time() - 35 ? ' online' : '') : '') .'"><img src="'. printFace($message['user_face'], $message['feeling']) .'" id="icon"></a>
            <p class="timestamp-container">
            <span class="timestamp">'. humanTiming(strtotime($message['message_date'])) .'</span>
            '. ($message['message_by'] == $_SESSION['user_id'] ? '<button type="button" class="symbol button edit-button rm-post-button" data-action="/messages/'. $message['message_id'] .'/rm"><span class="symbol-label">Delete</span></button>' : '') .'
            </p>
            <div class="post-body">
            <div class="post-content-text"><p>'. nl2br($message['body']) .'</p></div>';

        if (!empty($message['message_image'])) {
            if (mb_substr($message['message_image'], -4) == '.mp3' || mb_substr($message['message_image'], -4) == '.ogg') {
                echo '<div class="screenshot-container still-image">
                <audio controls="" preload="none">
                <source src="'. $message['message_image'] .'">
                Your browser does not support the audio element.
                </audio>
                <script>$("audio").each(function(){ this.volume = 0.2; });</script>
                </div>';
            } else {
                echo '<div class="screenshot-container still-image"><img src="'. $message['message_image'] .'"></div>';
            }
        }

            echo '</div>
            </div>';
    } else {
        http_response_code(201);
        echo '<script type="text/javascript">popup("Error", "'. $errors[0] .'");</script>';
    }
} else {
    if ((isset($_GET['offset']) && is_numeric($_GET['offset'])) && isset($_GET['dateTime'])) {
        $offset = ($_GET['offset'] * 30);
        $dateTime = htmlspecialchars($_GET['dateTime']);

        $get_messages = $dbc->prepare('SELECT * FROM messages WHERE conversation_id = ? AND deleted = 0 AND message_date < ? ORDER BY message_date DESC LIMIT 30 OFFSET ?');
        $get_messages->bind_param('isi', $friends['conversation_id'], $dateTime, $offset);
    } else {
        $tabTitle = 'Conversation with '. htmlspecialchars($friend['nickname'], ENT_QUOTES) .' ('. htmlspecialchars($friend['user_name'], ENT_QUOTES) .') - Cedar';

        printHeader(4);

        $get_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
        $get_user->bind_param('i', $_SESSION['user_id']);
        $get_user->execute();
        $user_result = $get_user->get_result();
        $user = $user_result->fetch_assoc();
        echo '<script>var loadOnScroll=true; var atBottom = false;</script><div id="sidebar" class="general-sidebar">';
        userContent($user, "");
        sidebarSetting();
        ?></div><?php

        echo '<div class="main-column messages"><div class="post-list-outline">
        <h2 class="label">Conversation with '. htmlspecialchars($friend['nickname'], ENT_QUOTES) .' ('. htmlspecialchars($friend['user_name'], ENT_QUOTES) .')
        <button class="button msg-update">Update</button>
        </h2>';

        ?>
        <form id="post-form" class="folded" method="post" action="" enctype="multipart/form-data">
            <div class="post-count-container">
                <input type="hidden" name="title_id" value="69420">
                <div class="textarea-feedback" style="float:left;">
                    <font color="#646464" style="font-size: 13px; padding: 0 3px 0 7px;">2000</font> Characters Remaining
                </div>
            </div><?php

            if (!strpos($user['user_face'], "imgur") && !strpos($user['user_face'], "cloudinary")) {
        echo '<div class="feeling-selector js-feeling-selector test-feeling-selector"><label class="symbol feeling-button feeling-button-normal checked"><input type="radio" name="feeling_id" value="0" checked=""><span class="symbol-label">normal</span></label><label class="symbol feeling-button feeling-button-happy"><input type="radio" name="feeling_id" value="1"><span class="symbol-label">happy</span></label><label class="symbol feeling-button feeling-button-like"><input type="radio" name="feeling_id" value="2"><span class="symbol-label">like</span></label><label class="symbol feeling-button feeling-button-surprised"><input type="radio" name="feeling_id" value="3"><span class="symbol-label">surprised</span></label><label class="symbol feeling-button feeling-button-frustrated"><input type="radio" name="feeling_id" value="4"><span class="symbol-label">frustrated</span></label><label class="symbol feeling-button feeling-button-puzzled"><input type="radio" name="feeling_id" value="5"><span class="symbol-label">puzzled</span></label></div>';
    }

            ?>
            <div class="textarea-container">
                <textarea name="text_data" class="textarea-text textarea" maxlength="2000" placeholder="Write a message here."></textarea>
            </div>
            <label class="file-button-container">
                <span class="input-label">File upload <span>PNG, JPG, BMP, GIF, MP3, and OGG are allowed. Max file size: <?= ini_get('upload_max_filesize') ?>.</span></span>
                <input type="file" class="file-button" name="image" accept="image/*,.mp3,.ogg">
            </label>
            <div class="form-buttons">
                <input type="submit" name="submit" class="black-button post-button disabled" value="Send" disabled="">
            </div>
        </form>
        <?php

        echo '<div class="list messages" data-next-page-url="/messages/'. htmlspecialchars($friend['user_name'], ENT_QUOTES) .'?offset=1&dateTime='. date("Y-m-d H:i:s") .'">';

        $get_messages = $dbc->prepare('SELECT * FROM messages WHERE conversation_id = ? AND deleted = 0 ORDER BY message_date DESC LIMIT 30');
        $get_messages->bind_param('i', $friends['conversation_id']);
    }
        $get_messages->execute();
        $messages_result = $get_messages->get_result();

    if (!$messages_result->num_rows == 0) {
        while ($message = $messages_result->fetch_array()) {
            $get_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
            $get_user->bind_param('i', $message['message_by']);
            $get_user->execute();
            $user_result = $get_user->get_result();
            $user = $user_result->fetch_assoc();

            $message['body'] = htmlspecialchars($message['body'], ENT_QUOTES);

            $message['body'] = preg_replace("/^&gt;(.*)\n|^&gt;(.*)/m", '<span class="gt">$0</span>', $message['body']);

            $message['body'] = preg_replace('|([\w\d]*)\s?(https?://([\d\w\.-]+\.[\w\.]{2,6})[^\s\]\[\<\>]*/?)|i', '$1 <a href="$2" target="_blank" class="post-link">$2</a>', $message['body']);

            echo '<div class="post scroll'. ($message['message_by'] == $_SESSION['user_id'] ? ' my' : '') .'">
                <a href="/users/'. htmlspecialchars($user['user_name'], ENT_QUOTES) .'/posts" class="icon-container'. ($user['user_level'] > 1 ? ' verified' : '') . ($user['hide_online'] == 0 ? (strtotime($user['last_online']) > time() - 35 ? ' online' : ' offline') : '') .'"><img src="'. printFace($user['user_face'], $message['feeling']) .'" id="icon"></a>
                <p class="timestamp-container">
                <span class="timestamp">'. humanTiming(strtotime($message['message_date'])) .'</span>
                '. ($message['message_by'] == $_SESSION['user_id'] ? '<button type="button" class="symbol button edit-button rm-post-button" data-action="/messages/'. $message['message_id'] .'/rm"><span class="symbol-label">Delete</span></button>' : '') .'
                </p>
                <div class="post-body">
                <div class="post-content-text"><p>'. nl2br($message['body']) .'</p></div>';
            if (!empty($message['message_image'])) {
                if (mb_substr($message['message_image'], -4) == '.mp3' || mb_substr($message['message_image'], -4) == '.ogg') {
                    echo '<div class="screenshot-container still-image">
                    <audio controls="" preload="none">
                    <source src="'. $message['message_image'] .'">
                    Your browser does not support the audio element.
                    </audio>
                    <script>$("audio").each(function(){ this.volume = 0.2; });</script>
                    </div>';
                } else {
                    echo '<div class="screenshot-container still-image"><img src="'. $message['message_image'] .'"></div>';
                }
            }
            echo '</div>
                </div>';
        }

        $read_messages = $dbc->prepare('UPDATE messages SET message_read = 1 WHERE conversation_id = ? AND message_by != ?');
        $read_messages->bind_param('ii', $friends['conversation_id'], $_SESSION['user_id']);
        $read_messages->execute();
    }
}