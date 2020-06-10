<?php

namespace Ushahidi\Gmail\Services;

use Ushahidi\Gmail\Gmail;

class Mailbox
{
    protected $service;

    /**
     * Optional parameter for getting single and multiple emails
     *
     * @var array
     */
    protected $params = [];


    public function __construct(Gmail $client, $params = [])
    {
        $this->service = $client->getService();
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
        $mailbox = $this->listMessages();
       // $this->pageToken = method_exists($response, 'getNextPageToken') ? $mailbox->getNextPageToken() : null;

        $messages = $mailbox->getMessages();

        return new MessageCollection($messages);
    }

    /**
     * @param $id
     *
     * @return Mail
     */
    public function get($id)
    {
        $message = $this->getRequest($id);

        return new Message($message);
    }

    /**
     * @return \Google_Service_Gmail_ListMessagesResponse|object
     */
    private function listMessages()
    {
        return $this->service->users_messages->listUsersMessages('me', $this->params);
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
}
