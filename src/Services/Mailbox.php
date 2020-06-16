<?php

namespace Ushahidi\Gmail\Services;

use Google_Service_Gmail;
use Ushahidi\Gmail\Gmail;

class Mailbox
{
    protected $service;

    protected $pageToken;

    /**
     * Optional parameter for getting single and multiple emails
     *
     * @var array
     */
    protected $params = [];


    public function __construct(Gmail $client, $params = [])
    {
        $this->service = new Google_Service_Gmail($client);
        $this->params = $params;
    }

    /**
     * Returns a collection of messages
     *
     * @param null|string $pageToken
     *
     * @return \Illuminate\Support\Collection
     */
    public function all($pageToken = null)
    {
        $mailbox = $this->getMessages();
        $this->pageToken = method_exists($response, 'getNextPageToken') ? $mailbox->getNextPageToken() : null;

        $messages = $mailbox->getMessages();

        return $messages;
    }

    /**
     * @param $id
     *
     * @return Message
     */
    public function get($id)
    {
        $message = $this->getMessage($id);

        return new Message($message);
    }

    /**
     * @param $id
     *
     * @return \Google_Service_Gmail_Message
     */
    private function getMessage($id)
    {
        return $this->service->users_messages->get('me', $id);
    }

    /**
     * @return \Google_Service_Gmail_ListMessagesResponse|object
     */
    private function getMessages()
    {
        return $this->service->users_messages->listUsersMessages('me', $this->params);
    }
}
