<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
require __DIR__ . '/hooks.php';

$hook = $_REQUEST['hook'];
$print = print_r($_REQUEST,true);
$log = fopen('test.log', 'a');

switch ($hook) {
    case 'comment_post':
    	new_comment($_REQUEST);
        break;
    case 'publish_page':
        fwrite($log, $hook);
        fwrite($log, $print);
        //new_page($message, $_REQUEST);
        break;
    case 'publish_post':
        fwrite($log, $hook);
        fwrite($log, $print);
    	new_post($message, $_REQUEST);
        break;

    default:
        break;
}