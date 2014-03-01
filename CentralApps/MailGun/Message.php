<?php
namespace CentralApps\MailGun;

class Message extends \CentralApps\Mail\Message
{
    protected $tag = null;

    public function generateSendableArray()
    {
        $sendable = array();
        $sendable['from'] = (string) $this->sender;
        $sendable['subject'] = $this->subject;
        $headers = $this->generateHeadersArray();
        if (empty($headers)) {
            foreach ($headers as $header) {
                $sendable['h:' . $header->name ] = $header->value;
            }
        }

        if (count($this->attachments) > 0) {
            $i = 1;
            foreach ($this->attachments as $attachment) {
                $sendable['attachment[' . $i . ']'] = '@' . $attachment->getPath() . '/' . $attachment->getFilename();
            }
        }

        if (!is_null($this->tag)) {
            $sendable['o:tag'] = $tag;
        }

        if (!is_null($this->replyTo)) {
            $sendable['h:Reply-To'] = (string) $this->replyTo;
        }

        if (!is_null($this->plainTextMessage)) {
            $sendable['text'] = $this->plainTextMessage;
        }

        if (!is_null($this->htmlMessage)) {
            $sendable['html'] = $this->htmlMessage;
        }

        $sendable['to'] = implode(', ', $this->to->flattern());

        if (count($this->bcc) > 0) {
            $sendable['bcc'] = implode(', ', $this->bcc->flattern());
        }

        if (count($this->cc) > 0) {
            $sendable['cc'] = implode(', ', $this->cc->flattern());
        }

        return $sendable;
    }

    protected function generateHeadersArray()
    {
        $headers = array();
        foreach ($this->headers as $header) {
            $headers[] = array('Name' => $header->name, 'Value' => $header->value);
        }

        return $headers;
    }
}
