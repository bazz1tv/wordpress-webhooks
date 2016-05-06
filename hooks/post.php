<?php
// https://api.slack.com/docs/attachments
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/utility.php';

/* SUPPORTED 
'post_author',
'post_title',
'post_url',
*/

/* NOT YET SUPPORTED
'ID',
'comment_count',
'comment_status',
'guid',
'menu_order',
'ping_status',
'pinged',

'post_category',
'post_content',
'post_content_filtered',
'post_date',
'post_date_gmt',
'post_excerpt',
'post_mime_type',
'post_modified',
'post_modified_gmt',
'post_name',
'post_parent',
'post_password',
'post_status',

'post_type',

'to_ping',
*/

function new_post($request)
{
	$comment = new WPPost($request);
    $comment->submit();
}

?>

