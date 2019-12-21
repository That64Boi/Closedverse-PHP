<?php
require_once('lib/htm.php');

if (empty($_SESSION['signed_in'])) {
    return;
}

$get_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ?');
$get_user->bind_param('i', $_SESSION['user_id']);
$get_user->execute();
$user_result = $get_user->get_result();
$user = $user_result->fetch_assoc();

if (isset($_POST['title_id'])) {
    $get_title = $dbc->prepare('SELECT * FROM titles WHERE title_id = ?');
    $get_title->bind_param('i', $_POST['title_id']);
    $get_title->execute();
    $title_result = $get_title->get_result();
    if ($title_result->num_rows == 0) {
        $title['title_id'] = null;
    } else {
        $title = $title_result->fetch_array();
    }
}

if (isset($title) && $title['title_id'] != null) {
    if (!(($title['perm'] == 1 && $user['user_level'] > 1) || $title['perm'] == null)) {
        return;
    }

    if (($title['owner_only'] == 1) && ($title['title_by'] !== $_SESSION['user_id'])) {
        return;
    }
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo '<form id="post-form" class="folded" method="post" action="/postText.php" enctype="multipart/form-data">
        <input type="hidden" name="csrfToken" value="'. $_SESSION['csrfToken'] .'">
		<div class="post-count-container">
			<input type="hidden" name="title_id" value="'. (isset($title) ? $title['title_id'] : '0') .'">
			<div class="textarea-feedback" style="float:left;">
				<font color="#646464" style="font-size: 13px; padding: 0 3px 0 7px;">2000</font> Characters Remaining
			</div>
		</div>';

    if (!strpos($user['user_face'], "imgur") && !strpos($user['user_face'], "cloudinary")) {
        ?>
        <div class="feeling-selector js-feeling-selector test-feeling-selector">
            <label class="symbol feeling-button feeling-button-normal checked">
                <input type="radio" name="feeling_id" value="0" checked="">
                <span class="symbol-label">normal</span>
            </label>
            <label class="symbol feeling-button feeling-button-happy">
                <input type="radio" name="feeling_id" value="1">
                <span class="symbol-label">happy</span>
            </label>
            <label class="symbol feeling-button feeling-button-like">
                <input type="radio" name="feeling_id" value="2">
                <span class="symbol-label">like</span>
            </label>
            <label class="symbol feeling-button feeling-button-surprised">
                <input type="radio" name="feeling_id" value="3">
                <span class="symbol-label">surprised</span>
            </label>
            <label class="symbol feeling-button feeling-button-frustrated">
                <input type="radio" name="feeling_id" value="4">
                <span class="symbol-label">frustrated</span>
            </label>
            <label class="symbol feeling-button feeling-button-puzzled">
                <input type="radio" name="feeling_id" value="5">
                <span class="symbol-label">puzzled</span>
            </label>
        </div>
        <?php
    }
    
    ?>
        <div class="textarea-container">
            <textarea name="text_data" class="textarea-text textarea mention" maxlength="2000" placeholder="<?= (isset($title) ? 'Share your thoughts in a post to this community.' : 'Share a post to your followers.') ?>"></textarea>
        </div>
        <label class="file-button-container">
            <span class="input-label">File upload <span>PNG, JPG, BMP, GIF, MP3, OGG, and WEBM are allowed. Max file size: <?= ini_get('upload_max_filesize') ?>.</span></span>
            <input type="file" class="file-button" name="image" accept="image/*,.mp3,.ogg,.webm">
        </label>
        <div class="form-buttons">
            <input type="submit" name="submit" class="black-button post-button" value="Send" disabled="">
        </div>
    </form>
    <?php
} else {
    $errors = array();
    $image = null;

    if (empty($_POST['text_data'])) {
        $errors[] = 'Posts cannot be empty.';
    } elseif (mb_strlen($_POST['text_data']) > 2000) {
        $errors[] = 'Posts cannot be longer than 2000 characters.';
    }

    if (empty($_POST['feeling_id']) || strval($_POST['feeling_id']) >= 6) {
        $_POST['feeling_id'] = 0;
    }

    if (preg_match_all('/@(.+?(?=( |$)|\r\n))/m', $_POST['text_data']) > 5) {
        $errors[] = 'You can\'t mention more than 5 poeple per post.';
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
        $id = mt_rand(0, 99999999);
        $post_text = $dbc->prepare('INSERT INTO posts (id, post_by_id, post_title, feeling_id, text, post_image) VALUES (?, ?, ?, ?, ?, ?)');
        $post_text->bind_param('iiiiss', $id, $_SESSION['user_id'], $title['title_id'], $_POST['feeling_id'], $_POST['text_data'], $image);
        $post_text->execute();

        if ($title['title_id'] != null && $title['type'] == 5) {
            $old_announcement = $dbc->prepare('DELETE FROM notifs WHERE notif_type = 6');
            $old_announcement->execute();

            $notify = $dbc->prepare('INSERT INTO notifs (notif_type, notif_to, notif_post) SELECT 6, user_id, ? FROM users');
            $notify->bind_param('i', $id);
            $notify->execute();
        }

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
                    $notify = $dbc->prepare('INSERT INTO notifs (notif_type, notif_by, notif_to, notif_post) VALUES (7, ?, ?, ?)');
                    $notify->bind_param('iii', $_SESSION['user_id'], $mention_user['user_id'], $id);
                    $notify->execute();

                    $mentioned[] = $mention_user['user_id'];
                }
            }
            return $m[0];
        }, $_POST['text_data']);

        $get_posts = $dbc->prepare('SELECT * FROM posts INNER JOIN users ON user_id = post_by_id WHERE id = ?');
        $get_posts->bind_param('i', $id);
        $get_posts->execute();
        $posts_result = $get_posts->get_result();
        $post = $posts_result->fetch_array();

        if ($title['title_id'] != null) {
            echo '<div class="post trigger" data-href="/posts/'. $post['id'] .'" style="display: none;">';
        } else {
            echo '<div data-href="/posts/'. $post['id'] .'" class="post post-subtype-default trigger post-list-outline" tabindex="0" style="display: none;">
    <p class="community-container"><a class="test-community-link"><img src="/assets/img/feed-icon.png" class="community-icon">Activity Feed</a></p>';
        }
        printPost($post, 0);
    } else {
        http_response_code(201);
        echo '<script type="text/javascript">popup("Error", "'. $errors[0] .'", "<button class=\"ok-button black-button\" type=\"button\" data-event-type=\"ok\">OK</button>");</script>';
    }
}
