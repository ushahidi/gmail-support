<?php

namespace Ushahidi\Gmail\Services;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Psr\Http\Message\RequestInterface;
use Ushahidi\Gmail\Services\Traits\QueryParameters;

class Mailbox
{
    use QueryParameters;

    public $batch = true;

    public $client;

    public $service;

    public $type;

    public $historyId;

    public $pageToken;

    /**
     * Optional parameter for getting single and multiple emails
     *
     * @var array
     */
    protected $params = [];

    public function __construct(Google_Client $client)
    {
        $this->client = $client;
        $this->service = new Google_Service_Gmail($client);
        $this->setUseBatch();
        $this->setSyncType("full");
    }

    /**
     * Declare whether batch calls should be used.
     *
     * @param boolean $useBatch False if the batch support should
     * be disabled. Defaults to True.
     */
    public function setUseBatch($useBatch = true)
    {
        $this->batch = $useBatch;
        return $this;
    }

    /**
     * Set mailbox client sync type
     *
     * @param string $type
     */
    public function setSyncType($type)
    {
        $this->params = [];
        $this->type = $type;
        return $this;
    }

    /**
     * Returns next page if available of messages or an empty collection
     *
     * @return \Illuminate\Support\Collection
     */
    public function next()
    {
        if ($this->pageToken) {
            if (isset($this->historyId)) $this->history($this->historyId);
            return $this->page($this->pageToken)->all();
        } else {
            return collect([]);
        }
    }

    /**
     * Returns boolean if the page token variable is null or not
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return !!$this->pageToken;
    }

    /**
     * @param string|Google_Service_Gmail_Message $message
     * 
     * @return Message
     */
    public function get($message)
    {
        if ($message instanceof Google_Service_Gmail_Message) {
            if (isset($message->historyId)) {
                return new Message($message);
            }

            $message = $message->getId();
        }

        return new Message($this->getMessageRequest($message));
    }

    /**
     * Returns a collection of messages
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        if ($this->type == 'full') {
            $response = $this->listMessagesRequest();
            $list = $response->getMessages();
        } else if ($this->type == 'partial') {
            $response = $this->listHistoryRequest();
            $this->historyId = $response->getHistoryId();
            $list = collect($response->getHistory())->map(function ($history) {
                return collect($history->getMessages())->first();
            });
        } else {
            return collect([]);
        }

        $this->pageToken = method_exists($response, 'getNextPageToken') ? $response->getNextPageToken() : null;

        if ($this->batch) {
            $messages = $this->getBatchMessages($list);
        } else {
            $messages = $this->getMessages($list);
        }

        return $messages;
    }

    protected function getBatchMessages($list)
    {
        $batchMessages = collect([]);

        $chunkMessagesList = collect($list)->chunk(100);

        $this->client->setUseBatch(true);

        foreach ($chunkMessagesList as $chunkMessages) {
            $batch = $this->service->createBatch();
            foreach ($chunkMessages as $key => $message) {
                $batch->add($this->getMessageRequest($message->getId()), $key);
            }
            $response = $batch->execute();
            $batchMessages = $batchMessages->merge($response);
        }

        $this->client->setUseBatch(false);

        return $this->getMessages($batchMessages);
    }

    protected function getMessages($list)
    {
        return collect($list)->map(function ($message) {
            return $this->get($message);
        });
    }

    /**
     * @param $id
     *
     * @return Google_Service_Gmail_Message|RequestInterface
     */
    private function getMessageRequest($id)
    {
        return $this->service->users_messages->get('me', $id);
    }

    /**
     *
     * @return Google_Service_Gmail_ListMessagesResponse
     */
    private function listMessagesRequest()
    {
        return $this->service->users_messages->listUsersMessages('me', $this->params);
    }

    /**
     *
     * @return Google_Service_Gmail_ListHistoryResponse
     */
    private function listHistoryRequest()
    {
        return $this->service->users_history->listUsersHistory('me', $this->params);
    }
}
