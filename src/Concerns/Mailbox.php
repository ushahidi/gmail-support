<?php

namespace Ushahidi\Gmail\Concerns;

trait Mailbox
{
        /**
     * Returns a collection of Mail instances
     *
     * @param null|string $pageToken
     *
     * @return \Illuminate\Support\Collection
     * @throws \Google_Exception
     */
    public function all($pageToken = null)
    {
        $mailbox = $this->listMessages();
        $this->pageToken = method_exists($response, 'getNextPageToken') ? $mailbox->getNextPageToken() : null;

        $getMessages = $mailbox->getMessages();

        foreach ($allMessages as $message) {
            $messages[] = new Mail($message, $this->preload);
        }
    }

    /**
     * @return \Google_Service_Gmail_ListMessagesResponse|object
     * @throws \Google_Exception
     */
    private function listMessages()
    {
        $response = $this->service->users_messages->listUsersMessages('me', $this->params);

        return $response;
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
