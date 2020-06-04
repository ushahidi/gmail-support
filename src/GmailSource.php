<?php

namespace Ushahidi\Gmail;

use Ushahidi\Core\Entity\Contact;
use Ushahidi\App\DataSource\IncomingAPIDataSource;
use Ushahidi\App\DataSource\Message\Type as MessageType;

class GmailSource implements IncomingAPIDataSource
{
    /**
     * Contact type user for this provider
     */
    public $contact_type = Contact::EMAIL;

    public function __construct()
    {
        # code...
    }

    public function getId()
    {
        return strtolower($this->getName());
    }

    public function getName()
    {
        return 'Gmail';
    }

    public function getServices()
    {
        return [MessageType::EMAIL];
    }

    /**
     * Fetch email messages via gmail api
     *
     * @param  boolean $limit   maximum number of messages to fetch at a time
     * @return int              number of messages fetched
     */
    public function fetch($limit = false)
    {

    }

    /**
     * Extract the FROM email address string
     *
     * @param string $from - from address string from email
     * @return string email address or NULL
     */
    protected function getEmail($from)
    {
        $pattern = '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i';

        if (preg_match_all($pattern, $from, $emails)) {
            foreach ($emails as $key => $value) {
                if (isset($value[0])) {
                    return $value[0];
                }
            }
        }

        return null;
    }
}
