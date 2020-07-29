<?php

namespace Ushahidi\Gmail;

use Closure;
use Exception;
use Carbon\Carbon;
use Google_Service_Exception;
use Ushahidi\Core\Entity\Contact;
use Ushahidi\Core\Entity\ConfigRepository;
use Ushahidi\App\DataSource\IncomingAPIDataSource;
use Ushahidi\App\DataSource\OutgoingAPIDataSource;
use Ushahidi\App\DataSource\Concerns\MapsInboundFields;
use Ushahidi\App\DataSource\Message\Type as MessageType;
use Ushahidi\App\DataSource\Message\Status as MessageStatus;

class GmailSource implements IncomingAPIDataSource, OutgoingAPIDataSource
{
    use MapsInboundFields;

    protected $config;

    protected $configRepo;

    protected $lastHistoryId;
    protected $lastSync;
    protected $pageToken; // get mails for a page

    /**
     * Contact type user for this provider
     */
    public $contact_type = Contact::EMAIL;

    public function __construct(array $config, ConfigRepository $configRepo = null, Closure $connectionFactory = null)
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
        return [
            'intro_text' => [
                'label' => '',
                'input' => 'read-only-text',
                'description' => 'In order to receive posts by gmail, please input your account settings below and connect your gmail account above ',
            ],
            'email' => [
                'label' => 'Email Address',
                'input' => 'text',
                'description' => '',
                'placeholder' => 'johndoe@gmail.com',
                'rules' => ['required', 'email']
            ],            
            'date' => [
                'label' => 'Fetch Email From',
                'input' => 'text',
                'rules' => ['date']
            ],
            'client_id' => [
                'label' => 'Client Id',
                'input' => 'text',
                'description' => 'Add the cliend id from your Gmail credentials. ',
                'rules' => [],
                'is_gmail_support' => true // This option can be provided via external configuration
            ],
            'client_secret' => [
                'label' => 'Client Secret',
                'input' => 'text',
                'description' => 'Add the client secret from your Gmail credentials. ',
                'rules' => [],
                'is_gmail_support' => true
            ],
            'redirect_uri' => [
                'label' => 'Redirect URL',
                'input' => 'text',
                'rules' => [],
                'is_gmail_support' => true
            ]
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

    public function fetch($limit = false)
    {
        $this->initialize();

        $gmail = $this->connect();

        if (!$gmail) {
            // The connection didn't succeed, but this is not fatal to the application flow
            // Just return 0 messages fetched
            return [];
        }

        $mailbox = $gmail->mailbox();

        if ($limit == false) {
            $limit = 200;
        }

        $mails = isset($this->lastHistoryId)
                    ? $this->partialSync($mailbox, $limit)
                    : $this->fullSync($mailbox, $limit);
        while ($mailbox->hasNextPage()) {
            $mails = $mails->merge($mails, $mailbox->next());
        }

        $this->lastHistoryId = optional($mails->first())->historyId ?? $mailbox->historyId;

        $this->pageToken = $mailbox->pageToken;

        $this->lastSync = Carbon::now()->timestamp;

        $messages = [];

        $messages = $mails->map(function ($mail) {
            if ($mail && !empty($mail->body)) {
                // Save the message
                return [
                    'type'                   => 'email',
                    'contact_type'           => 'email',
                    'from'                   => $this->getEmail($mail->from()),
                    'message'                => $this->getMessage($mail->body()),
                    'to'                     => $this->getEmail($mail->to()),
                    'title'                  => $this->sanitize($mail->subject()),
                    'datetime'               => $mail->date(),
                    'data_source_message_id' => $mail->id,
                    'additional_data'        => [
                        'history_id' => $mail->historyId
                    ],
                ];
            }

            return [];
        })
            ->filter()
            ->all();

        $this->update();

        return $messages;
    }

    public function send($to, $message, $title = '')
    {
        $gmail = $this->connect();

        if (!$gmail) {
            return [MessageStatus::FAILED, false];
        }

        $from = isset($this->config['email']) ? $this->config['email'] : $gmail->getUser();

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
     * Perform a full synchronization
     *
     * @return \Illuminate\Support\Collection
     */
    protected function fullSync($mailbox, $limit)
    {
        $mails = $mailbox->setSyncType("full");

        if (isset($this->lastSync)) {
            $mails->after($this->lastSync);
        } elseif (isset($this->config['date'])) {
            $mails->after(Carbon::parse($this->config['date'])->timestamp);
        }

        return $mails->label('INBOX')
            ->take($limit)
            ->all();
    }

    /**
     * Perform a partial synchronization
     *
     * @return \Illuminate\Support\Collection
     */
    protected function partialSync($mailbox, $limit)
    {
        try {
            $mails = $mailbox->setSyncType("partial")
                ->history($this->lastHistoryId)
                ->historyTypes("messageAdded")
                ->take($limit)
                ->all();
        } catch (Google_Service_Exception $e) {
            $mails = $this->fullSync($mailbox, $limit);
        }

        return $mails;
    }

    /**
     * Sanitize the BODY email text string
     *
     * @param string $text - message text string from email
     * @return string
     */
    protected function getMessage($text)
    {
        $text = htmlspecialchars($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_COMPAT, 'UTF-8');
        $text = html_entity_decode($text, ENT_HTML5, 'UTF-8');
        /** $text = preg_replace('@<style[^>]*?>.*?</style>@si', '', $text);
        * $text = str_replace("|a", "<a", strip_tags(str_replace("<a", "|a", $text)));
        **/
        return $this->sanitize($text);
    }

    /**
     * Extract the FROM email address string
     *
     * @param string $from - from address string from email
     * @return string email address or NULL
     */
    protected function getEmail($from)
    {
        /**
         * Source: https://stackoverflow.com/a/2934602/9852028
         * 
         * Decodes Unicode escape sequences like “\u00ed” to proper UTF-8 encoded characters
         */
        $string = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $from);

        $pattern = "/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i";

        if (preg_match_all($pattern, $string, $emails)) {
            foreach ($emails as $key => $value) {
                if (isset($value[0])) {
                    return $value[0];
                }
            }
        }

        return $string;
    }

    protected function sanitize($text)
    {
        return trim(preg_replace('/[^a-z\d ]/i', '', $text));
    }

    private function initialize()
    {
        $gmailConfig = $this->configRepo->get('gmail');

        isset($gmailConfig->next_page_token)
            ? $this->pageToken = $gmailConfig->next_page_token
            : $this->pageToken = null;

        isset($gmailConfig->last_history_id)
            ? $this->lastHistoryId = $gmailConfig->last_history_id
            : $this->lastHistoryId = null;

        isset($gmailConfig->last_sync)
            ? $this->lastSync = $gmailConfig->last_sync
            : $this->lastSync = null;
    }

    private function update()
    {
        $gmailConfig = $this->configRepo->get('gmail');

        $gmailConfig->setState([
            'next_page_token' => $this->pageToken,
            'last_history_id' => $this->lastHistoryId,
            'last_sync'       => $this->lastSync
        ]);

        $this->configRepo->update($gmailConfig);
    }

    /**
     * Make Gmail Connection
     *
     * @return Gmail
     */
    private function connect()
    {
        $user        = $this->config['email'];
        $credentials = [
            'client_id' => $this->config['client_id'] ?? config('services.gmail.client_id'),
            'client_secret' => $this->config['client_secret'] ?? config('services.gmail.client_secret') ,
            'redirect_uri' => $this->config['redirect_uri'] ?? config('services.gmail.redirect_uri')
        ];

        // Check we have the required config
        if (
            !isset($user) ||
            !isset($credentials['client_id']) ||
            !isset($credentials['client_secret']) ||
            !isset($credentials['redirect_uri'])
        ) {
            app('log')->warning('Could not connect to gmail, incomplete config');
            return;
        }

        $connection = ($this->connectionFactory)(
            $user,
            $credentials
        );

        $connection->setStorage(new TokenConfigStorage($this->configRepo));

        return $connection;
    }
}
