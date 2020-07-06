<?php


namespace Ushahidi\Gmail\Services;

use Google_Service_Gmail_Message;

class Message
{
    public $id;

    public $historyId;

    public $payload;

    public $headers;

    public $body;

    public $message;

    public function __construct(Google_Service_Gmail_Message $message)
    {
        $this->message = $message;
        $this->id = $this->message->getId();
        $this->historyId = $this->message->getHistoryId();
        $this->payload = $this->message->getPayload();
        $this->body = $this->getMessageBody();
        $this->headers = $this->getHeaders();
    }

    public function to()
    {
        return $this->headers->get('To');
    }

    public function from()
    {
        return $this->headers->get('From');
    }

    public function subject()
    {
        return $this->headers->get('Subject');
    }

    public function date()
    {
        return $this->headers->get('Date');
    }

    public function body()
    {
        return $this->body;
    }

    protected function getHeaders()
    {
        return collect($this->payload->getHeaders())
            ->mapWithKeys(function ($header) {
                return [$header->name => $header->value];
        });
    }

    protected function getMessageBody()
    {
        $parts = collect($this->payload->getParts());
        $message = '';

        if (!empty($parts)) {
            foreach ($parts as $part) {
                if ($part->getMimeType() == 'text/html') {
                    $message = $this->decodeBody($part->getBody()->getData());
                } elseif ($part->getMimeType() == 'text/plain') {
                    $message = $this->decodeBody($part->getBody()->getData());
                }
            }
        } else {
            $message = $this->decodeBody($this->payload->getBody()->getData());
        }

        return $message;
    }

    protected function decodeBody($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data)%4, '=', STR_PAD_RIGHT));
    }
}
