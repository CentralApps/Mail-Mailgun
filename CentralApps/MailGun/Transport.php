<?php
namespace CentralApps\MailGun;

class Transport implements \CentralApps\Mail\Transport {
	
	protected $apiKey;
	protected $domain;
	protected $apiEndPoint = 'https://api.mailgun.net/v2';
	protected $permittedAttachmentTypes = array();
	protected $maxAttachmentSize = -1;
	protected $maxNumAttachments = -1;
	
	protected $configurationMappings = array( 'api_key' => 'apiKey', 'domain' => 'domain', 'campaign' => 'campaign', 'dkim' => 'dkim', 'test_mode' => 'testMode', 'tracking' => 'tracking', 'tracking_clicks' => 'trackingClicks', 'tracking_opens' => 'trackingOpens');
	
	protected $errors = array();
	
	// transport specific settings
	protected $campaign = null;
	protected $dkim = null;
	protected $testMode = null;
	protected $tracking = null;
	protected $trackingClicks = null;
	protected $trackingOpens = null;
	
	public function __construct(\CentralApps\Mail\Configuration $configuration)
	{
		foreach( $this->configurationMappings as $key => $mapping ) {
			if( array_key_exists( $key, $configuration ) ) {
				 $this->$mapping = $configuration[ $key ];
			}
		}
	}
	
	public function interimAttachmentCheck(\splFileInfo $attachment, \CentralApps\Mail\Message $message)
	{
		$errors = array();
		return $errors;
	}
	
	protected function attachmentsCheck(Message $message)
	{
		$attachments = $message->getAttachments();
		foreach( $attachments as $attachment ) {
			if( ! $this->attachmentCheckFileTypes($attachment) ) {
				$this->errors[] = "The attachment " . $attachment->getFilename() . " is not permitted to be sent via MailGun";
			}
		}
		$this->attachmentsCheckTooMany($attachments);
		$this->attachmentCheckTooBig($attachments);
		
	}
	
	protected function attachmentsCheckTooMany($attachments)
	{
		if( $this->maxNumAttachments >= 0 ) {
			if(count($attachments) > $this->maxNumAttachments) {
				$this->errors[] = "Too many attachments. MailGun app only permits " . $this->maxNumAttachments . " attachments per message";
			}
		}
	}
	
	public function getAttachmentsSize($attachments)
	{
		$size = 0;
		foreach($attachments as $attachment) {
			$size += $attachment->getSize();
		}
		return $size;
	}
	
	protected function attachmentCheckTooBig($attachments)
	{
		$size = $this->getAttachmentsSize($attachments);
		
		if( $size > $this->maxAttachmentSize && $this->maxAttachmentSize >= 0 ) {
			$this->errors[] = "Attachments too large, you have exceeded the attachment size limit for MailGun";
		}
	}
	
	protected function attachmentCheckFileTypes($attachment)
	{
		return true;
	}
	
	public function prepare($message)
	{
		$this->attachmentsCheck($message);
	}
	
	protected function mailgunSpecificTransport($sendableArray)
	{
		$message = $sendableArray;
		if( ! (is_null($this->testMode) || $this->testMode == false ) ) {
			$message['o:testmode'] = 'yes';
		}
		if( ! is_null($this->tracking) ) {
			$message['o:tracking'] = ($this->tracking == true) ? 'yes' : 'no';
		}
		if( ! is_null($this->trackingClicks) ) {
			$message['o:tracking-clicks'] = $this->trackingClicks;
		}
		if( ! is_null($this->trackingOpens) ) {
			$message['o:tracking-opens'] = ($this->trackingOpens == true) ? 'yes' : 'no';
		}
		if(!is_null($this->dkim)) {
			$message['o:dkim'] = ($this->dkim == true) ? 'yes' : 'no';
		}
		if(!is_null($this->campaign)) {
			$message['o:campaign'] = $this->campaign;
		}
		return $message;
	}
	
	public function send(\CentralApps\Mail\Message $message)
	{
		$this->prepare($message);
		$email = $message->generateSendableArray();
		$email = $this->mailgunSpecificTransport($email);
		if(empty($this->errors)) {
			// do something
			$this->sendViaMailGun($email);
		} else {
			// throw something
		}
	}
	
	protected function sendViaMailGun($sendableEmail)
	{
		$ch = curl_init();
		curl_setopt($ch, \CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, \CURLOPT_USERPWD, 'api:' . $this->apiKey );
		curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
		
		curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, \CURLOPT_URL, $this->apiEndPoint . '/' . $this->domain . '/messages');
		curl_setopt($ch, \CURLOPT_POSTFIELDS, $sendableEmail);
		
		$response = curl_exec($ch);
		$error = curl_error($ch);
		$cleaned_response = json_decode( $response );
		//echo '<pre>' . print_r($response,true) . '</pre>';
		if( curl_getinfo($ch, \CURLINFO_HTTP_CODE) == 200 )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
}
