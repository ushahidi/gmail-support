<?php

namespace Ushahidi\Gmail;

use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Swift_Mime_SimpleMessage;
use Swift_Mime_ContentEncoder_Base64ContentEncoder;
use Illuminate\Mail\Transport\Transport;

class GmailTransport extends Transport
{
    protected $gmail;

    public function __construct(Gmail $client)
    {
        $this->gmail = new Google_Service_Gmail($client);
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param string[]|null $failedRecipients
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $rawMessage = (new Swift_Mime_ContentEncoder_Base64ContentEncoder())
            ->encodeString($message->toString());

        $gmailMessage = new Google_Service_Gmail_Message;
        $gmailMessage->setRaw($rawMessage);
        $this->gmail->users_messages->send("me", $gmailMessage);

        return $this->numberOfRecipients($message);
    }
}
