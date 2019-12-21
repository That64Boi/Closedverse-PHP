<?php
require_once('lib/htm.php');

if (!empty($_SESSION['signed_in'])) {
    if (isset($id)) {
        if (checkReplyExists($id) && checkReplyCreator($id, $_SESSION['user_id'])) {
            $delete = $dbc->prepare('UPDATE replies SET deleted = 2 WHERE reply_id = ?');
            $delete->bind_param('i', $id);
            $delete->execute();
            echo 'success';
        }
    }
}
