<?php
require_once('lib/htm.php');

if (!empty($_SESSION['signed_in'])) {
    if (isset($id)) {
        //if ($_GET['postType'] == "post") {
        if (checkPostExists($id) && checkPostCreator($id, $_SESSION['user_id'])) {
            $delete = $dbc->prepare('UPDATE posts SET deleted = 2 WHERE id = ?');
            $delete->bind_param('i', $id);
            $delete->execute();
            echo 'success';
        }
        //} else {
            /*if (checkReplyExists($_GET['postId']) && checkReplyCreator($_GET['postId'], $_SESSION['user_id'])) {
                $delete = $dbc->prepare('UPDATE replies SET deleted = 2 WHERE reply_id = ?');
                $delete->bind_param('i', $_GET['postId']);
                $delete->execute();
                echo 'success';
            }*/
    }
}
