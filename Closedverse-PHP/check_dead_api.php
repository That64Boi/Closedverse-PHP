<?php
require_once('lib/connect.php');

if (empty($_SESSION['signed_in'])) {
    http_response_code(404);
    exit('Page not found');
}

$check_is_admin = $dbc->prepare('SELECT user_level FROM users WHERE user_id = ?');
$check_is_admin->bind_param('i', $_SESSION['user_id']);
$check_is_admin->execute();
$is_admin_result = $check_is_admin->get_result();
$is_admin = $is_admin_result->fetch_assoc();

if ($is_admin['user_level'] == 0) {
    http_response_code(404);
    exit('Page not found');
}

$get_keys = $dbc->prepare('SELECT * FROM cloudinary_keys ORDER BY key_id ASC');
$get_keys->execute();
$key_result = $get_keys->get_result();

$num_dead_keys = 0;

while ($keys = $key_result->fetch_assoc()) {
    $pvars = array('file' => 'https://mii-secure.cdn.nintendo.net/1aew7lbpmxsnp_surprised_face.png',
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

    if (!@$image=$pms['secure_url']) {
        echo '<p>Dead API ID: '. $keys['key_id'] .'</p>';
        $num_dead_keys++;
    }
}

if ($num_dead_keys == 0) {
    echo 'No dead keys! All good 👍';
}
