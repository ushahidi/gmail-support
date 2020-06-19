<?php


namespace Ushahidi\Gmail\Services;

use Google_Service_Gmail_Message;

class Message
{
    public $id;

    public $payload;

    public $parts;

    public $headers;

    public function __construct(Google_Service_Gmail_Message $message)
    {
        $this->id = $message->getId();
        $this->payload = $message->getPayload();
        $this->parts = collect($this->payload->getParts());
        $this->headers = $this->buildHeaders();
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
        $message = '';

        if (!empty($this->parts)) {
            foreach ($this->parts as $part) {
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

    protected function buildHeaders()
    {
        return collect($this->payload->getHeaders())->mapWithKeys(function ($header) {
            return [$header->name => $header->value];
        });
    }

    protected function decodeBody($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data)%4, '=', STR_PAD_RIGHT));
    }
}
