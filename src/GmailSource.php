<?php

namespace Ushahidi\Gmail;

use Exception;
use Ushahidi\Core\Entity\Contact;
use Ushahidi\Core\Entity\ConfigRepository;
use Ushahidi\App\DataSource\IncomingAPIDataSource;
use Ushahidi\App\DataSource\OutgoingAPIDataSource;
use Ushahidi\App\DataSource\Concerns\MapsInboundFields;
use Ushahidi\App\DataSource\Message\Status as MessageStatus;
use Ushahidi\App\DataSource\Message\Type as MessageType;

class GmailSource implements IncomingAPIDataSource, OutgoingAPIDataSource
{
    use MapsInboundFields;

    protected $config;

    protected $configRepo;

    protected $pageToken; // get mails for a page

    protected $gmail;

    /**
     * Contact type user for this provider
     */
    public $contact_type = Contact::EMAIL;

    public function __construct(array $config, ConfigRepository $configRepo = null, \Closure $connectionFactory = null)
    {
        $this->config = $config;
        $this->configRepo = $configRepo;
        $this->connectionFactory = $connectionFactory;
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
        return [];
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

    public function fetch($limit = false)
    {
        $this->initialize();

        if ($limit === false) {
            $limit = 200;
        }

        $gmail = $this->connect();

        if (!$gmail) {
            // The connection didn't succeed, but this is not fatal to the application flow
            // Just return 0 messages fetched
            return [];
        }

        $mailbox = $gmail->mailbox();
        $mailbox->take(200);

        $mails = $mailbox->all($this->pageToken);
        $this->pageToken = $mailbox->pageToken;
        $messages = [];

        $messages = $mails->map(function ($mail) {
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

    public function send($to, $message, $title = '')
    {
        $gmail = $this->connect();

        if (!$gmail) {
            return [MessageStatus::FAILED, false];
        }
        
        $from = isset($this->config['user']) ? $this->config['user'] : $gmail->getUser();

        $mailer = $gmail->mailer();
        try {
            $response =  $mailer->createMessage($title, $from, $to, $message)->send();
            if (!isset($response->id)) {
                app('log')->error("Twitter: Send failed", ['response' => $response]);
                return [MessageStatus::FAILED, false];
            }
            return [MessageStatus::SENT, $response->id];
        } catch (Exception $e) {
            app('log')->error($e->getMessage());
            return [MessageStatus::FAILED, false];
        }
    }

    /**
     * Sanitize the BODY email text string
     *
     * @param string $text - message text string from email
     * @return string
     */
    protected function getMessage($text)
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_COMPAT, 'UTF-8');
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
        $gmailConfig = $this->configRepo->get('gmail');

        isset($gmailConfig->next_page_token) ?
            $this->pageToken = $gmailConfig->next_page_token :
            $this->pageToken = null;
    }

    private function update()
    {
        $gmailConfig = $this->configRepo->get('gmail');

        $gmailConfig->setState([
            'next_page_token' => $this->pageToken,
        ]);

        $this->configRepo->update($gmailConfig);
    }

    private function connect()
    {
        // Check we have the required config
        if (
            !isset($this->config['user']) ||
            !isset($this->config['client_id']) ||
            !isset($this->config['client_secret']) ||
            !isset($this->config['redirect_uri'])
        ) {
            app('log')->warning('Could not connect to gmail, incomplete config');
            return;
        }

        $connection = ($this->connectionFactory)(
            $this->config['user'],
            [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'redirect_uri' => $this->config['redirect_uri']
            ]
        );

        $connection->setStorage(new TokenConfigStorage($this->configRepo));

        return $connection;
    }
}
