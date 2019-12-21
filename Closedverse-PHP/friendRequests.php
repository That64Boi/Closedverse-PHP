<?php
require_once('lib/htm.php');
require_once('lib/htmUsers.php');

$tabTitle = 'Closedverse - Friend Requests';

printHeader(5);

$get_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
$get_user->bind_param('i', $_SESSION['user_id']);
$get_user->execute();
$user_result = $get_user->get_result();
$user = $user_result->fetch_assoc();
echo '<div id="sidebar" class="general-sidebar">';
userContent($user, "");
sidebarSetting();
echo '</div><div class="main-column"><div class="post-list-outline"><h2 class="label">Friend Requests</h2>

<div id="notification-tab-container" class="tab-container">
<div class="tab2">
<a class="tab-icon-my-news" href="/notifications">
<span class="symbol nf"></span>
<span>Updates</span>
</a>
<a class="tab-icon-my-news selected" href="/friend_requests">
<span class="symbol fr"></span>
<span>Friend Requests</span>
</a>
</div>
</div>

<div class="list news-list">';

$get_requests = $dbc->prepare('SELECT * FROM friend_requests INNER JOIN users ON user_id = request_by WHERE request_to = ? ORDER BY request_date DESC');
$get_requests->bind_param('i', $_SESSION['user_id']);
$get_requests->execute();
$requests_result = $get_requests->get_result();

if (!$requests_result->num_rows == 0) {
    while ($request = $requests_result->fetch_array()) {
        ?><div class="dialog mask none" data-modal-types="accept-friend-request" uuid="<?= $request['user_id'] ?>" data-reject-action="/users/<?= $request['user_name'] ?>/friend_reject">
            <div class="dialog-inner">
                <div class="window">
                    <h1 class="window-title">Friend Request from <?= htmlspecialchars($request['nickname'], ENT_QUOTES) ?> at <?= date_format(date_create($request['request_date']), 'm/d/Y g:i A') ?></h1>
                    <div class="window-body">
                        <div id="sidebar-profile-body">
                            <div class="icon-container<?= ($request['user_level'] > 1 ? ' verified' : '') . ($request['hide_online'] == 0 ? (strtotime($request['last_online']) > time() - 35 ? ' online' : ' offline') : '') ?>">
                                <a href="/users/<?= $request['user_name'] ?>/posts">
                                    <img src="<?= printFace($request['user_face'], 0) ?>" id="icon">
                                </a>
                            </div>
                            <a href="/users/<?= $request['user_name'] ?>/posts" class="nick-name"><?= htmlspecialchars($request['nickname'], ENT_QUOTES) ?></a>
                            <p class="id-name"><?= $request['user_name'] ?></p>
                        </div>
                        <pre><i><?= (!empty(trim($request['request_text'])) ? htmlspecialchars($request['request_text'], ENT_QUOTES) : '(No message)') ?></i></pre>
                        <p class="window-body-content">Accept <?= htmlspecialchars($request['nickname'], ENT_QUOTES) ?>'s friend request?</p>
                        <div class="form-buttons three">
                            <button class="olv-modal-close-button gray-button" data-event-type="cancel" type="button">Cancel</button><button class="reject-button gray-button" data-screen-name="<?= htmlspecialchars($request['nickname'], ENT_QUOTES) ?>" type="button">Reject</button><button class="become-friends black-button" data-action="/users/<?= $request['user_name'] ?>/friend_accept" data-event-type="ok" type="button">Accept</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="news-list-content trigger" tabindex="0" id="<?= $request['user_id'] ?>" data-href="/users/<?= $request['user_name'] ?>/posts">
            <a href="/users/<?= $request['user_name'] ?>/posts" class="icon-container<?= ($request['user_level'] > 1 ? ' verified' : '') . ($request['hide_online'] == 0 ? (strtotime($request['last_online']) > time() - 35 ? ' online' : ' offline') : '') ?>"><img src="<?= printFace($request['user_face'], 0) ?>" id="icon"></a>
            <div class="body">
                <a href="/users/<?= $request['user_name'] ?>/posts" class="nick-name"><?= htmlspecialchars($request['nickname'], ENT_QUOTES) ?></a><br><span class="timestamp"><?= humanTiming(strtotime($request['request_date'])) ?></span>
                <button class="button received-request-button" type="button">View friend request</button>
            </div>
        </div><?php
    }
} else {
    echo '<div id="user-page-no-content" class="no-content"><div><p>No friend requests yet.</p></div></div>';
}

$dbc->query('UPDATE friend_requests SET request_read = 1 WHERE request_to = '. $_SESSION['user_id'] .'');
