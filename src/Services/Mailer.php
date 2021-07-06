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

    public function __construct(Google_Client $client)
    {
        $this->client = $client;
        $this->service = new Google_Service_Gmail($client);
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
     * @return Mailer
     */
    public function setMessage($message)
    {
        // $this->mime = (new Swift_Mime_ContentEncoder_Base64ContentEncoder)
        //     ->encodeString($message->toString());

        $this->mime = base64_encode($message->toString());

        return $this;
    }

    /**
     *  Send the message
     */
    public function send()
    {
        $mime = $this->mime;

        $message = new Google_Service_Gmail_Message;

        $message->setRaw($mime);

        return $this->service->users_messages->send("me", $message);
    }
}
