<?php
require_once('lib/htm.php');

if (empty($_SESSION['signed_in'])) {
    return;
}

$get_user = $dbc->prepare('SELECT user_face FROM users WHERE user_id = ?');
$get_user->bind_param('i', $_SESSION['user_id']);
$get_user->execute();
$user_result = $get_user->get_result();
$user = $user_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo '<form id="post-form" method="post" action="/posts/'.$post['id'].'/replies" enctype="multipart/form-data">
    <input type="hidden" name="csrfToken" value="'. $_SESSION['csrfToken'] .'">
	  <div class="post-count-container"> 
	    <div class="textarea-feedback" style="float:left;">
	      <font color="#646464" style="font-size: 13px; padding: 0 3px 0 7px;">2000</font> Characters Remaining
	    </div>
	  </div>';

    if (!strpos($user['user_face'], "imgur") && !strpos($user['user_face'], "cloudinary")) {
        echo '<div class="feeling-selector js-feeling-selector test-feeling-selector"><label class="symbol feeling-button feeling-button-normal checked"><input type="radio" name="feeling_id" value="0" checked=""><span class="symbol-label">normal</span></label><label class="symbol feeling-button feeling-button-happy"><input type="radio" name="feeling_id" value="1"><span class="symbol-label">happy</span></label><label class="symbol feeling-button feeling-button-like"><input type="radio" name="feeling_id" value="2"><span class="symbol-label">like</span></label><label class="symbol feeling-button feeling-button-surprised"><input type="radio" name="feeling_id" value="3"><span class="symbol-label">surprised</span></label><label class="symbol feeling-button feeling-button-frustrated"><input type="radio" name="feeling_id" value="4"><span class="symbol-label">frustrated</span></label><label class="symbol feeling-button feeling-button-puzzled"><input type="radio" name="feeling_id" value="5"><span class="symbol-label">puzzled</span></label></div>';
    }

    echo '<div class="textarea-container"><textarea name="text_data" class="textarea-text textarea mention" maxlength="2000" placeholder="Add a comment here."></textarea></div><label class="file-button-container">
            <span class="input-label">File upload <span>PNG, JPG, BMP, GIF, MP3, and OGG are allowed. Max file size: '. ini_get('upload_max_filesize') .'.</span></span>
            <input type="file" class="file-button" name="image" accept="image/*,.mp3,.ogg">
        </label><div class="form-buttons"><input type="submit" name="submit" class="black-button post-button disabled" value="Send" disabled=""></div></form>';
} else {
    $errors = array();
    $image = null;

    if (empty($_POST['text_data'])) {
        $errors[] = 'Post text cannot be empty.';
    } elseif (mb_strlen($_POST['text_data']) > 2000) {
        $errors[] = 'Replies cannot be longer than 2000 characters.';
    } else {
        $text = $_POST['text_data'];
    }

    if (preg_match_all('/@(.+?(?=( |$)|\r\n))/m', $_POST['text_data']) > 5) {
        $errors[] = 'You can\'t mention more than 5 poeple per post.';
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

    if ($_POST['csrfToken'] != $_SESSION['csrfToken']) {
        $errors[] = 'CSRF token check failed.';
    }

    if (empty($errors)) {
        $text = htmlspecialchars($text, ENT_QUOTES);
        $reply_id = mt_rand(0, 99999999);

        $post_reply = $dbc->prepare('INSERT INTO replies (reply_id, reply_post, reply_by_id, feeling_id, text, reply_image) VALUES (?, ?, ?, ?, ?, ?)');
        $post_reply->bind_param('iiiiss', $reply_id, $id, $_SESSION['user_id'], $_POST['feeling_id'], $text, $image);
        $post_reply->execute();

        $mentioned = array();

        $_POST['text_data'] = preg_replace_callback('/@(.+?(?=( |$)|\r\n))/m', function ($m) {
            global $dbc;
            global $id;
            global $mentioned;
            $get_mention_user = $dbc->prepare('SELECT * FROM users WHERE user_name = ? LIMIT 5');
            $get_mention_user->bind_param('s', $m[1]);
            $get_mention_user->execute();
            $mention_user_result = $get_mention_user->get_result();

            if (!$mention_user_result->num_rows == 0) {
                $mention_user = $mention_user_result->fetch_assoc();

                if ($_SESSION['user_id'] != $mention_user['user_id'] && !in_array($mention_user['user_id'], $mentioned)) {
                    $notify = $dbc->prepare('INSERT INTO notifs (notif_type, notif_by, notif_to, notif_post) VALUES (8, ?, ?, ?)');
                    $notify->bind_param('iii', $_SESSION['user_id'], $mention_user['user_id'], $id);
                    $notify->execute();

                    $mentioned[] = $mention_user['user_id'];
                }
            }
            return $m[0];
        }, $_POST['text_data']);

        $search_post = $dbc->prepare('SELECT * FROM posts WHERE id = ?');
        $search_post->bind_param('i', $id);
        $search_post->execute();
        $post_result = $search_post->get_result();
        $post = $post_result->fetch_assoc();

        if ($_SESSION['user_id'] == $post['post_by_id']) {
            $notif_getcomments = $dbc->prepare('SELECT reply_by_id FROM replies WHERE reply_post = ? AND reply_by_id != ? AND deleted = 0 GROUP BY reply_by_id');
            $notif_getcomments->bind_param('ii', $id, $_SESSION['user_id']);
            $notif_getcomments->execute();
            $result_notif_getcomments = $notif_getcomments->get_result();

            while ($notif_comments = mysqli_fetch_assoc($result_notif_getcomments)) {
                if (!in_array($notif_comments['reply_by_id'], $mentioned)) {
                    notify($notif_comments['reply_by_id'], 3, $id);
                }
            }
        } else {
            if (!in_array($post['post_by_id'], $mentioned)) {
                notify($post['post_by_id'], 2, $id);
            }
        }

        $search_reply = $dbc->prepare('SELECT * FROM replies INNER JOIN users ON user_id = reply_by_id WHERE reply_id = ?');
        $search_reply->bind_param('i', $reply_id);
        $search_reply->execute();
        $reply_result = $search_reply->get_result();
        $reply = $reply_result->fetch_assoc();
        
        echo '<li class="post'. ($reply['reply_by_id'] == $post['post_by_id'] ? ' my' : '') .' trigger" data-href="/replies/'.$reply['reply_id'].'" style="display: none;">';
        printReply($reply);
    } else {
        echo '<script type="text/javascript">alert("'. $errors[0] .'", "<button class=\"ok-button black-button\" type=\"button\" data-event-type=\"ok\">OK</button>");</script>';
    }
}
