<?php
// https://api.slack.com/docs/attachments
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/utility.php';

/* SUPPORTED 
'comment_post_ID',
'comment_content',
'comment_author',
'comment_author_email',
'comment_author_url',
'approval',   [avoids spam]
*/

/* NOT YET SUPPORTED
'comment_ID',
'comment_agent',
'comment_approved',

'comment_author_IP',

'comment_date',
'comment_date_gmt',
'comment_karma',
'comment_parent',

'comment_type',
'user_id'
*/

function new_comment($request)
{
	global $SLACK_WEBHOOK_URL;
	global $defaultSlackSettings;

	if (array_key_exists('approval', $request))
	{
		if (strcasecmp($request['approval'], 'spam') == 0)
		{
	    	return;	// do not notify Slack of spam comments
		}
	}
	

	$client = new Maknz\Slack\Client($SLACK_WEBHOOK_URL, $defaultSlackSettings);
	$comment = new WPComment($client, $request/*,$comment_fields*/);
    $comment->submit();
}

class WPComment extends WP {
	public function __construct(&$client, $request)
	{
		parent::__construct($client);

		if (array_key_exists('comment_post_ID', $request))
		{
			$this->usePostId($request['comment_post_ID']);

			$this->slackAttachment->setTitle("New Wordpress comment on \"" . slack_link($this->postUrl,$this->postTitle) . "\"");
			$this->slackAttachment->setFallback("New Wordpress comment on \"$this->postTitle\n$this->postUrl\"");
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
		if (array_key_exists('comment_author', $request))
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
		$this->postUrl = $BLOG_URL . "?p=" . $this->postId;
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
}

?>

