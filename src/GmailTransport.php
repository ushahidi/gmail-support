<?php

namespace Ushahidi\Gmail;

use Swift_Mime_SimpleMessage;
use Illuminate\Mail\Transport\Transport;

class GmailTransport extends Transport
{

    public function __construct()
    {

    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param string[]|null $failedRecipients
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        // TODO: Implement send() method.
    }
}
