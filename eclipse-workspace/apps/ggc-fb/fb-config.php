<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$fb = new Facebook\Facebook([
    'app_id' => '292663425362349',
    'app_secret' => 'f31b48982c6de0dfcb0ad10b6b70b3d4',
    'default_graph_version' => 'v2.10',
    'cookie' => true,
]);

$helper = $fb->getRedirectLoginHelper();
?>