<?php
require_once('lib/htm.php');

$query = '/'. htmlspecialchars($_GET['q']) .'%';

$get_emojis = $dbc->prepare('SELECT * FROM emojis WHERE emoji_name LIKE ? ESCAPE "/"LIMIT 6');
$get_emojis->bind_param('s', $query);
$get_emojis->execute();
$emojis_result = $get_emojis->get_result();

if (!$emojis_result->num_rows == 0) {
    $emoji_name_output = array();
    while ($emoji = $emojis_result->fetch_array()) {
        array_push($emoji_name_output, array('emoji_name' => htmlspecialchars($emoji['emoji_name'], ENT_QUOTES), 'emoji_url' => htmlspecialchars($emoji['emoji_url'], ENT_QUOTES)));
    }
    echo json_encode($emoji_name_output);
} else {
    echo json_encode(array());
}
