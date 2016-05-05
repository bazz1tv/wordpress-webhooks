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
	global $SLACK_WEBHOOK_URL;
	global $defaultSlackSettings;

	$client = new Maknz\Slack\Client($SLACK_WEBHOOK_URL, $defaultSlackSettings);
	$comment = new WPPost($client, $request/*,$comment_fields*/);
    $comment->submit();
}

class WPPost extends WP {
	public function __construct(&$client, $request)
	{
		parent::__construct($client);

		if (array_key_exists('post_title', $request) && array_key_exists('post_url', $request))
		{
			//$this->usePostId($request['post_title']);
			$this->postUrl = $request['post_url'];
			$this->postTitle = $request['post_title'];
			$this->slackAttachment->setTitle("New Wordpress post \"" . slack_link($this->postUrl,$this->postTitle) . "\"");
			$this->slackAttachment->setFallback("New Wordpress post \"$this->postTitle\n$this->postUrl\"");
		}
		if (array_key_exists('comment_content', $request))
		{
			//$this->slackAttachment->setText(
			$content = htmlspecialchars(html_entity_decode($request['comment_content']), ENT_NOQUOTES | ENT_HTML401);

			$field = new Maknz\Slack\AttachmentField([]);
			$field->setTitle("Content");
			$field->setValue($content);
			$field->setShort(false);

			$this->slackFields[] = $field;
		}
		if (array_key_exists('post_author', $request))
		{
			$field = new Maknz\Slack\AttachmentField([]);
			$field->setTitle("Author");
			$field->setValue(
				htmlspecialchars(html_entity_decode($request['comment_author']), ENT_NOQUOTES | ENT_HTML401));
			$field->setShort(true);

			$this->slackFields[] = $field;
		}
		if (array_key_exists('comment_author_email', $request))
		{
			$field = new Maknz\Slack\AttachmentField([]);
			$field->setTitle("Email");
			$email = htmlspecialchars(html_entity_decode($request['comment_author_email']), ENT_NOQUOTES | ENT_HTML401);
			$field->setValue("<mailto:$email|$email>");
			$field->setShort(true);

			$this->slackFields[] = $field;
		}

		if (array_key_exists('comment_author_url', $request))
		{
			$field = new Maknz\Slack\AttachmentField([]);
			$field->setTitle("Website");
			$url = htmlspecialchars(html_entity_decode($request['comment_author_url']), ENT_NOQUOTES | ENT_HTML401);
			$field->setValue($url);
			$field->setShort("true");

			$this->slackFields[] = $field;
		}

		echo print_r($request);
	}

	private function usePostId($id)
	{
		$this->postId = $id;
		// setPostUrl()
		global $BLOG_URL;
		$this->postUrl = $BLOG_URL . ?author=//"?p=" . $this->postId;
		//
		$this->setPostTitleFromUrl($this->postUrl);
	}

	private function setPostTitleFromUrl($url)
	{
		// Retrieve the DOM from a given URL
		$this->html = Sunra\PhpSimple\HtmlDomParser::file_get_html($url);
		// decode HTML codes in title (ie. &nbsp; from experience)
		$this->postTitleNoSpecialChars = html_entity_decode($this->html->find('.entry-title',0)->innertext);
		// persist only certain HTML codes as per https://api.slack.com/docs/formatting
		//   http://php.net/manual/en/function.htmlspecialchars.php
		$this->postTitle = htmlspecialchars($this->postTitleNoSpecialChars, ENT_NOQUOTES | ENT_HTML401);
	}

	var $html;
	var $postUrl = null;
	var $postTitle = null;
	var $postTitleNoSpecialChars;
	var $postId = null;
	var $postAuthorId;
	var $postAuthor;
}

?>

