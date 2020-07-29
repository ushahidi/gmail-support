<?php

namespace Ushahidi\Gmail;

use Swift_Mime_SimpleMessage;
use Illuminate\Mail\Transport\Transport;

class GmailTransport extends Transport
{
    protected $gmail;

    public function __construct(Gmail $client)
    {
        $this->gmail = $client;
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param string[]|null $failedRecipients
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);
        
        $this->gmail->mailer()->setMessage($message)->send();

        return $this->numberOfRecipients($message);
    }
}
