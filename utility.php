<?php
function slack_link($url, $name)
{
	return "<$url|$name>";
}

class WP
{
	public function __construct(&$client)
	{
		$this->client = $client;
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