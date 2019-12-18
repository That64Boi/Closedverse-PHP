<?php
require_once 'lib/connect.php';

$clear_notifs = $dbc->prepare('DELETE FROM notifs WHERE notif_to = ?');
$clear_notifs->bind_param('i', $_SESSION['user_id']);
$clear_notifs->execute();
