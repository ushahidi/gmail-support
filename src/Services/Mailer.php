<?php

namespace Ushahidi\Gmail\Services;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Swift_Message;
use Swift_Mime_ContentEncoder_Base64ContentEncoder;

class Mailer
{
    public $client;

    public $service;

    public $mime;

    public $message;

    public function __construct(Google_Client $client)
    {
        $this->client = $client;
        $this->service = new Google_Service_Gmail($client);
        $this->message = new Google_Service_Gmail_Message;
    }

    /**
     * Create a new message
     *
     * @param string $subject
     * @param string $from
     * @param string $to
     * @param string $body
     * 
     * @return Mailer
     */
    public function createMessage($subject = '', $from = '', $to = '', $body = '')
    {
        $message = (new Swift_Message($subject))
            ->setFrom($from)
            ->setTo($to)
            ->setContentType('text/html')
            ->setCharset('utf-8')
            ->setBody($body);

        $this->setMessage($message);

        return $this;
    }

    /**
     * Set the message body
     *
     * @param Swift_Message $message
     * 
     * @return Mailer
     */
    public function setMessage($message)
    {
        $this->mime = base64_encode($message->toString());

        $this->message->setRaw($this->mime);

        return $this;
    }

    public function setHistoryId($historyId)
    {
        $this->message->setHistoryId($historyId);
    }

    public function setThreadId($threadId)
    {
        $this->message->setThreadId($threadId);
    }

    /**
     *  Send the message
     */
    public function send()
    {
        return $this->service->users_messages->send("me", $this->message);
    }
}
