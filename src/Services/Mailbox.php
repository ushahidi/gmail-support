<?php

namespace Ushahidi\Gmail\Services;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;

class Mailbox
{
    public $client;

    public $service;

    public $pageToken;

    /**
     * Optional parameter for getting single and multiple emails
     *
     * @var array
     */
    protected $params = [];


    public function __construct(Google_Client $client, $params = [])
    {
        $this->client = $client;
        $this->params = $params;
        $this->service = new Google_Service_Gmail($client);
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
        if(isset($pageToken)) $this->page($pageToken);

        $list = $this->service->users_messages->listUsersMessages('me', $this->params);
        $this->pageToken = method_exists($list, 'getNextPageToken') ? $list->getNextPageToken() : null;

        return $this->getMessages($list);
    }

    /**
     * @param $message
     * @return Message
     */
    public function get($message)
    {
        if ($message instanceof Google_Service_Gmail_Message) {
            $message = $message->getId();
        }

        $message = $this->service->users_messages->get('me', $message);

        return new Message($message);
    }

    /**
     * Returns next page if available of messages or an empty collection
     *
     * @return \Illuminate\Support\Collection
     * @throws \Google_Exception
     */
    public function next()
    {
        if ($this->pageToken) {
            return $this->all($this->pageToken);
        } else {
            return collect([]);
        }
    }

    /**
	 * Specify the maximum number of messages to return
	 *
	 * @param  int  $number
	 *
	 * @return Mailbox
	 */
	public function take($number)
	{
		$this->params['maxResults'] = abs((int) $number);

		return $this;
    }

    /**
	 * Set the page token to retrieve a specific page of results in the list.
	 *
	 * @param  string  $token
	 *
	 * @return Mailbox
	 */
    public function page($token)
	{
		$this->params['pageToken'] = $token;

		return $this;
	}

    protected function getMessages($list)
    {
        return collect($list->getMessages())->map(function ($message) {
            return $this->get($message);
        });
    }
}
