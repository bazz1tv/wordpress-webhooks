<?php

/* This defines the API */
namespace Wordpress;

class Base
{
	public function __construct($request)
	{
		// Put initializers here that are common among Post, Comment, and Page
		// Look for common JSON
	}

	public function submit()
	{
		echo "THIS FUNCTION MUST BE OVERRIDDEN"
	}
}

class Comment extends Base
{
	protected function use_post_id($id)
	{
		global $BLOG_URL;
		$this->postId = $id;
		$this->post_url = $BLOG_URL . "?p=" . $this->postId;
		$this->set_post_title_from_url($this->post_url);
	}

	protected function set_post_title_from_url($url)
	{
		// Retrieve the DOM from a given URL
		$this->html = Sunra\PhpSimple\HtmlDomParser::file_get_html($url);
		// decode HTML codes in title (ie. &nbsp; from experience)
		$tmp = html_entity_decode($this->html->find('.entry-title',0)->innertext);
		// persist only certain HTML codes as per https://api.slack.com/docs/formatting
		//   http://php.net/manual/en/function.htmlspecialchars.php
		$this->post_title = htmlspecialchars($tmp, ENT_NOQUOTES | ENT_HTML401);
	}
}

class Post extends Base
{
	public function __construct($request)
	{
		parent::__construct($request);

		if (array_key_exists('post_title', $request) && array_key_exists('post_url', $request))
		{
			$this->post_url = $request['post_url'];
			$this->post_title = $request['post_title'];
		}
		if (array_key_exists('comment_content', $request))
		{
			$this->content = htmlspecialchars(html_entity_decode($request['comment_content']), ENT_NOQUOTES | ENT_HTML401);
		}
		if (array_key_exists('post_author', $request))
		{
			$this->post_author = htmlspecialchars(html_entity_decode($request['post_author']), ENT_NOQUOTES | ENT_HTML401);
		}
		if (array_key_exists('comment_author_email', $request))
		{
			$this->comment_author_email = htmlspecialchars(html_entity_decode($request['comment_author_email']), ENT_NOQUOTES | ENT_HTML401);
		}

		if (array_key_exists('comment_author_url', $request))
		{
			$this->comment_author_url = htmlspecialchars(html_entity_decode($request['comment_author_url']), ENT_NOQUOTES | ENT_HTML401);
		}

		echo print_r($request);
	}

	var $post_url = null;
	var $post_title = null;
	var $postId = null;
	var $postAuthorId = null;
	var $postAuthor = null;
	var $comment_content = null;
	var $comment_author_email = null;
	var $comment_author_url = null;
}

class Page
{

}

/* SHOULD BE A DIFFERENT FILE */
namespace Slack;
class Base extends Wordpress::Base
{
	public static function slack_link($url, $name)
	{
		return "<$url|$name>";
	}

	public function __construct()
	{
		global $SLACK_WEBHOOK_URL;
		global $DEFAULT_SLACK_SETTINGS;
		$this->client = new Maknz\Slack\Client($SLACK_WEBHOOK_URL, $DEFAULT_SLACK_SETTINGS);
		$this->slackAttachment = new Maknz\Slack\Attachment([]);
		$this->slackFields = array();
	}

	public function submit()
	{
		$message = $this->client->createMessage();
		$this->slackAttachment->setFields($this->slackFields);
		$message->attach($this->slackAttachment);
		$message->send();
	}

	var $slackFields;
  var $attachment;
	var $client;
}

class Post extends Base
{
	public function __construct($request)
	{
		global $SLACK_WEBHOOK_URL;
		global $DEFAULT_SLACK_SETTINGS;
		$this->client = new Maknz\Slack\Client($SLACK_WEBHOOK_URL, $DEFAULT_SLACK_SETTINGS);

		if ($this->post_url && $this->post_title)
		{
			$this->slackAttachment->setTitle("New Wordpress post \"" . slack_link($this->post_url,$this->post_title) . "\"");
			$this->slackAttachment->setFallback("New Wordpress post \"$this->post_title\n$this->post_url\"");
		}
		if ($this->comment_content)
		{
			$field = new Maknz\Slack\AttachmentField([]);
			$field->setTitle("Content");
			$field->setValue($this->comment_content);
			$field->setShort(false);

			$this->slackFields[] = $field;
		}
		if ($this->post_author)
		{
			$field = new Maknz\Slack\AttachmentField([]);
			$field->setTitle("Author");
			$field->setValue($this->post_author);
			$field->setShort(true);

			$this->slackFields[] = $field;
		}
		if ($this->comment_author_email)
		{
			$field = new Maknz\Slack\AttachmentField([]);
			$field->setTitle("Email");
			$email = $this->comment_author_email;
			$field->setValue("<mailto:$email|$email>");
			$field->setShort(true);

			$this->slackFields[] = $field;
		}
		if ($this->comment_author_url)
		{
			$field = new Maknz\Slack\AttachmentField([]);
			$field->setTitle("Website");
			$url = $this->comment_author_url;
			$field->setValue($url);
			$field->setShort("true");

			$this->slackFields[] = $field;
		}
	}

	var $client
}

/*function test($assocArr){
  foreach( $assocArr as $key=>$value ){
    echo $key . ' ' . $value . ' ';
  }
}

test(['hello'=>'world', 'lorem'=>'ipsum']);*/

	

