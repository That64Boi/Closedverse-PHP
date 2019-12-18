<?php
require_once('lib/htm.php');
require_once('lib/htmUsers.php');

if ((isset($_GET['offset']) && is_numeric($_GET['offset'])) && isset($_GET['dateTime'])) {
    $offset = ($_GET['offset'] * 25);
    $dateTime = htmlspecialchars($_GET['dateTime']);

    $get_titles = $dbc->prepare('SELECT * FROM titles WHERE user_made = 1 AND time_created < ? ORDER BY time_created DESC LIMIT 25 OFFSET ?');
    $get_titles->bind_param('si', $dateTime, $offset);
} else {
    $tabTitle = 'All User Communities - Closedverse';

    printHeader(3);

    echo '<script>var loadOnScroll=true; var atBottom = false;</script><div id="sidebar" class="general-sidebar">';

    if (!empty($_SESSION['signed_in'])) {
        $get_user = $dbc->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
        $get_user->bind_param('i', $_SESSION['user_id']);
        $get_user->execute();
        $user_result = $get_user->get_result();
        $user = $user_result->fetch_assoc();
        userContent($user, "");
    }

    sidebarSetting();
    echo '</div>';

    echo '
<div class="main-column">
  <div class="post-list-outline">
    <div class="body-content" id="community-top" data-region="USA">
      <h2 class="label">All Communities</h2>
      <ul class="list community-list" data-next-page-url="/communities/user?offset=1&dateTime='. date("Y-m-d H:i:s") .'">';

    $get_titles = $dbc->prepare('SELECT * FROM titles WHERE user_made = 1 ORDER BY time_created DESC LIMIT 25');
}
$get_titles->execute();
$titles_result = $get_titles->get_result();

while ($titles = $titles_result->fetch_assoc()) {
    printTitleInfo($titles);
}
