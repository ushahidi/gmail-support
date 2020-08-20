<?php


namespace Ushahidi\Gmail\Services;

use Google_Service_Gmail_Message;
use League\HTMLToMarkdown\HtmlConverter;

class Mail
{
    public $id;

    public $historyId;

    public $payload;

    public $snippet;

    public $headers;

    public $bodyParts;

    public function __construct(Google_Service_Gmail_Message $message)
    {     
        $this->id = $message->getId();
        $this->historyId = $message->getHistoryId();

        $this->payload = $message->getPayload();
        $this->snippet = $message->getSnippet();

        $this->headers = $this->getHeaders();
        $this->bodyParts = $this->getBodyParts();
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
        return trim($this->headers->get('Subject'));
    }

    public function date()
    {
        return $this->headers->get('Date');
    }

    public function body($type = 'plain')
    {
        switch ($type) {
            case 'plain':
                return $this->bodyParts->get('plain');
            case 'html':
                return $this->bodyParts->get('html');
            case 'markdown':
                return $this->toMarkdown();
        }
    }

    protected function toMarkdown()
    {
        $html = $this->bodyParts->get('html');
        $converter = new HtmlConverter();
        $converter->getConfig()->setOption('strip_tags', true);
        $converter->getConfig()->setOption('remove_nodes', 'style script');

        $markdown = $converter->convert($html);

        return $markdown;
    }

    /**
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getHeaders()
    {
        return collect($this->payload->getHeaders())
            ->mapWithKeys(function ($header) {
                return [$header->name => $header->value];
        });
    }

    /**
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getBodyParts()
    {
        $parts = collect($this->payload->getParts());
        $bodyParts = [];

        if (!empty($parts)) {
            foreach ($parts as $part) {
                if ($part->getMimeType() == 'text/html') {
                    $bodyParts['html'] = $this->decodeBody($part->getBody()->getData());
                } elseif ($part->getMimeType() == 'text/plain') {
                    $bodyParts['plain'] = $this->decodeBody($part->getBody()->getData());
                }
            }
        } else {
            $bodyParts['plain'] = $this->decodeBody($this->payload->getBody()->getData());
        }

        return collect($bodyParts);
    }

    protected function decodeBody($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data)%4, '=', STR_PAD_RIGHT));
    }
}
