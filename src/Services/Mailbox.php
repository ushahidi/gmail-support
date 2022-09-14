<?php

namespace Ushahidi\Gmail\Services;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Google\Service\Exception as Google_Service_Exception;
use Illuminate\Support\Collection;

class Mailbox
{
    use Traits\QueryParameters;

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
        if ($this->historyId && $this->pageToken) {
            $this->history($this->historyId);
            $this->page($this->pageToken);
            return $this->all();
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
     * Get a Mailbox Message
     * 
     * @param Google_Service_Gmail_Message|Google_Service_Exception $message
     * 
     * @return Mail
     */
    public function get($message)
    {
        // if Message Format is MINIMAL, we need to get the full message
        if ($message instanceof Google_Service_Gmail_Message && !isset($message->historyId)) {
            $message = $this->getMessageRequest($message->getId());
        }

        return new Mail($message);
    }

    /**
     * Returns a collection of messages
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        switch ($this->type) {
            case 'full':
                $response = $this->listMessagesRequest();
                $list = $response->getMessages();
                break;
            case 'partial':
                $response = $this->listHistoryRequest();
                $this->historyId = $response->getHistoryId();
                $list = collect($response->getHistory())->map(function ($history) {
                    return collect($history->getMessages())->first();
                });
            default:
                $messages = collect([]);
                break;
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

        /**
         * Limit of batch requests because Larger batch sizes are likely to trigger rate limiting. 
         * Sending batches larger than 50 requests is not recommended.
         * 
         * From: https://developers.google.com/gmail/api/guides/batch#overview
         */
        $chunkMessagesList = collect($list)->chunk(50); 

        $this->client->setUseBatch(true);

        foreach ($chunkMessagesList as $chunkMessages) {
            $batch = $this->service->createBatch();
            
            foreach ($chunkMessages as $key => $message) {
                $batch->add(
                    $this->getMessageRequest($message->getId()), 
                    $key
                );
            }
            $response = $batch->execute();
            $batchMessages = $batchMessages->merge($response);
        }

        $this->client->setUseBatch(false);

        return $this->getMessages($batchMessages);
    }

    /**
     * 
     * @param \Google_Service_Gmail_Message|Google_Service_Exception[] $list 
     * 
     * @return Collection 
     */
    protected function getMessages($list)
    {
        return collect($list)->map(function ($message) {

            if ($message instanceof Google_Service_Exception) {
                return;
            }
            
            return $this->get($message);
        });
    }

    /**
     * @param $id
     *
     * @return \Google_Service_Gmail_Message|\Psr\Http\Message\RequestInterface
     */
    private function getMessageRequest($id)
    {
        return $this->service->users_messages->get('me', $id);
    }

    /**
     *
     * @return \Google_Service_Gmail_ListMessagesResponse
     */
    private function listMessagesRequest()
    {
        return $this->service->users_messages->listUsersMessages('me', $this->params);
    }

    /**
     *
     * @return \Google_Service_Gmail_ListHistoryResponse
     */
    private function listHistoryRequest()
    {
        return $this->service->users_history->listUsersHistory('me', $this->params);
    }
}
