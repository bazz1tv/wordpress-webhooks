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

	if (array_key_exists('approval', $request))
	{
		if (strcasecmp($request['approval'], 'spam') == 0)
		{
	    	return;	// do not notify Slack of spam comments
		}
	}	

	$comment = new WPComment($request);
    $comment->submit();
}

class WPComment extends WP {
	public function __construct($request)
	{
		parent::__construct($client);

		if (array_key_exists('comment_post_ID', $request))
		{
			$this->use_post_id($request['comment_post_ID']);

			$this->slackAttachment->setTitle("New Wordpress comment on \"" . slack_link($this->post_url,$this->post_title) . "\"");
			$this->slackAttachment->setFallback("New Wordpress comment on \"$this->post_title\n$this->post_url\"");
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

	private function use_post_id($id)
	{
		$this->postId = $id;
		// setpost_url()
		global $BLOG_URL;
		$this->post_url = $BLOG_URL . "?p=" . $this->postId;
		//
		$this->set_post_title_from_url($this->post_url);
	}

	private function set_post_title_from_url($url)
	{
		// Retrieve the DOM from a given URL
		$this->html = Sunra\PhpSimple\HtmlDomParser::file_get_html($url);
		// decode HTML codes in title (ie. &nbsp; from experience)
		$this->post_titleNoSpecialChars = html_entity_decode($this->html->find('.entry-title',0)->innertext);
		// persist only certain HTML codes as per https://api.slack.com/docs/formatting
		//   http://php.net/manual/en/function.htmlspecialchars.php
		$this->post_title = htmlspecialchars($this->post_titleNoSpecialChars, ENT_NOQUOTES | ENT_HTML401);
	}
 
	var $html;
	var $post_url = null;
	var $post_title = null;
	var $post_titleNoSpecialChars;
	var $postId = null;
}

?>

