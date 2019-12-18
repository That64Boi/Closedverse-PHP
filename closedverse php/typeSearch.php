<?php
require_once('lib/htm.php');

$query = '%/'. htmlspecialchars($_GET['q']) .'%';

$get_users = $dbc->prepare('SELECT user_name, nickname, user_face, last_online, hide_online FROM users WHERE  nickname COLLATE UTF8MB4_GENERAL_CI LIKE ? ESCAPE "/" OR user_name COLLATE UTF8MB4_GENERAL_CI LIKE ? ESCAPE "/" ORDER BY last_online DESC LIMIT 5');
$get_users->bind_param('ss', $query, $query);
$get_users->execute();
$users_result = $get_users->get_result();

if (!$users_result->num_rows == 0) {
    $user_name_output = array();
    while ($user = $users_result->fetch_array()) {
        array_push($user_name_output, array('user_name' => htmlspecialchars($user['user_name'], ENT_QUOTES), 'nickname' => htmlspecialchars($user['nickname'], ENT_QUOTES), 'user_face' => htmlspecialchars(printFace($user['user_face'], 0), ENT_QUOTES), 'online' => ($user['hide_online'] == 1 ? '' : (strtotime($user['last_online']) > time() - 35 ? ' online' : ' offline'))));
    }
    echo json_encode($user_name_output);
} else {
    echo json_encode(array());
}
