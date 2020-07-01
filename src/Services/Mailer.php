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

    public $message;

    public function __construct(Google_Client $client)
    {
        $this->client = $client;
        $this->service = new Google_Service_Gmail($client);
    }

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

    public function setMessage($message)
    {
        $this->message = (new Swift_Mime_ContentEncoder_Base64ContentEncoder)
        ->encodeString($message->toString());

        return $this;
    }

    public function send($message = null)
    {
        $message = $message ?: $this->message;

        $gmail_message = new Google_Service_Gmail_Message;

        $gmail_message->setRaw($message);

        return $this->service->users_messages->send("me", $gmail_message);
    }
}