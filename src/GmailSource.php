<?php

namespace Ushahidi\Gmail;

use Ushahidi\Core\Entity\Contact;
use Ushahidi\Core\Entity\ConfigRepository;
use Ushahidi\App\DataSource\IncomingAPIDataSource;
use Ushahidi\App\DataSource\OutgoingAPIDataSource;
use Ushahidi\App\DataSource\Concerns\MapsInboundFields;
use Ushahidi\App\DataSource\Message\Type as MessageType;

class GmailSource implements IncomingAPIDataSource, OutgoingAPIDataSource
{
    use MapsInboundFields;

    protected $config;

    protected $configRepo;

    protected $page_token; // get mails for a page

    protected $gmail;

    /**
     * Contact type user for this provider
     */
    public $contact_type = Contact::EMAIL;

    public function __construct(Array $config, ConfigRepository $configRepo = null)
    {
        $this->config = $config;
        $this->configRepo = $configRepo;
        $this->gmail = app('gmail', $config);
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

    public function getOptions()
    {
        return [
        ];
    }

    public function getInboundFields()
    {
        return [
            'Subject' => 'text',
            'Date' => 'datetime',
            'Message' => 'text'
        ];
    }

    public function isUserConfigurable()
    {
        return true;
    }

    /**
     * Fetch email messages via gmail api
     *
     * @param  boolean $limit   maximum number of messages to fetch at a time
     * @return int              number of messages fetched
     */
    public function fetch($limit = false)
    {
        $this->initialize();

        if ($limit === false) {
            $limit = 200;
        }

        $mailbox = $this->gmail->mailbox();
        $mailbox->take(200);

        $mails = $mailbox->all($this->page_token);
        $this->page_token = $mailbox->pageToken;
        $messages = [];

        $messages = $mails->map(function($mail) {
            if ($mail) {
                // Save the message
                return [
                    'type'                   => 'email',
                    'contact_type'           => 'email',
                    'from'                   => $this->getEmail($mail->from()),
                    'message'                => $this->getMessage($mail->body()),
                    'to'                     => $this->getEmail($mail->to()),
                    'title'                  => $mail->subject(),
                    'datetime'               => $mail->date(),
                    'data_source_message_id' => $mail->id,
                    'additional_data'        => [],
                ];
            }
    
            return [];
        })->toArray();

        $this->update();
        
        return $messages;
    }

    /**
     * Sanitize the BODY text string
     *
     * @param string $text - message body string from email
     * @return string
     */
    protected function getMessage($text)
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_COMPAT , 'UTF-8');
        $text = html_entity_decode($text, ENT_HTML5, 'UTF-8');
        $text = preg_replace('@<style[^>]*?>.*?</style>@si', '', $text);
        $text = str_replace("|a", "<a", strip_tags(str_replace("<a", "|a", $text)));
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
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

    private function initialize()
    {
        $this->gmail->setUser($this->config['user']);

        $gmailConfig = $this->configRepo->get('gmail');

        isset($gmailConfig->next_page_token) ?
                               $this->pageToken = $gmailConfig->next_page_token:
                               $this->pageToken = null;
    }

    private function update()
    {
        // Store the state in the database config for now
        $gmailConfig = $this->configRepo->get('gmail');

        $gmailConfig->setState([
            'next_page_token' => $this->pageToken,
        ]);

        $this->configRepo->update($gmailConfig);
    }
}
