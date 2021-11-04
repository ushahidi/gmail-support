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

    /**
     * Contact type user for this provider
     */
    public $contact_type = Contact::EMAIL;

    protected $config;
    protected $configRepo;

    protected $pageToken; // get mails for a page
    protected $firstSyncDate;
    protected $lastSyncDate;
    protected $lastHistoryId;

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
                'description' => 'In order to receive posts by gmail, connect your google account above ',
            ],
            'client_id' => [
                'label' => 'Client Id',
                'input' => 'text',
                'description' => 'Add the client id from your Gmail credentials. ',
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

    /**
     * Fetch messages from provider
     *
     * For services where we have to poll for message (Twitter, Email, FrontlineSMS) this should
     * poll the service and return an array of messages
     *
     * @param  boolean|int $limit   maximum number of messages to fetch at a time
     * @return array            array of messages
     */
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

        /**
         * Fetch all mails via a partial or full synchronization
         *
         * Read More: https://developers.google.com/gmail/api/guides/sync
         */
        $mails = isset($this->lastHistoryId)
            ? $this->partialSync($mailbox, $limit)
            : $this->fullSync($mailbox, $limit);

        // Check if the Mailbox has more mails and try to fetch them
        while ($mailbox->hasNextPage()) {
            $mails = $mails->merge($mails, $mailbox->next());
        }

        $this->pageToken = $mailbox->pageToken;

        $this->lastHistoryId = optional($mails->first())->historyId ?? $mailbox->historyId;

        $this->lastSyncDate = Carbon::now()->format("Y-m-d H:i:s");

        $messages = [];

        $messages = $mails->map(function ($mail) {
            if ($mail && !empty($mail->bodyParts)) {
                // Save the message
                return [
                    'type'                   => 'email',
                    'contact_type'           => 'email',
                    'from'                   => $this->getEmail($mail->from()),
                    'message'                => $mail->body('markdown'),
                    'to'                     => $this->getEmail($mail->to()),
                    'title'                  => $mail->subject(),
                    'datetime'               => $mail->date(),
                    'data_source_message_id' => $mail->id,
                    'additional_data'        => [
                        'history_id' => $mail->historyId
                    ],
                ];
            }

            return [];
        })
            ->filter(function ($message) {
                return !empty($message['message']);
            })
            ->all();

        $this->update();

        return $messages;
    }

    /**
     * @param  string  to Phone number to receive the message
     * @param  string  message Message to be sent
     * @param  string  title   Message title
     * @param  string  contact_type type of the contact to reach out to
     *                              (for multi-channel datasources)
     *
     * @return array   Array of message status, and tracking ID.
     */
    public function send($to, $message, $title = '', $contact_type = null)
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
                app('log')->error("Gmail: Send failed", ['response' => $response]);
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
     * @param \Ushahidi\Gmail\Services\Mailbox $mailbox
     * @param int $limit
     *
     * @return \Illuminate\Support\Collection
     */
    protected function fullSync($mailbox, $limit)
    {
        $mails = $mailbox->setSyncType("full");

        if (isset($this->lastSyncDate)) {
            $mails->after(Carbon::parse($this->lastSyncDate)->timestamp);
        } elseif (isset($this->firstSyncDate)) {
            $mails->after(Carbon::parse($this->firstSyncDate)->timestamp);
        }

        return $mails->label('INBOX')
            ->take($limit)
            ->all();
    }

    /**
     * Perform a partial synchronization
     *
     * @param \Ushahidi\Gmail\Services\Mailbox $mailbox
     * @param int $limit
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
            report($e);
            $mails = $this->fullSync($mailbox, $limit);
        }

        return $mails;
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

    private function initialize()
    {
        $config = $this->configRepo->get('gmail');

        isset($config->next_page_token)
            ? $this->pageToken = $config->next_page_token
            : $this->pageToken = null;

        isset($config->last_history_id)
            ? $this->lastHistoryId = $config->last_history_id
            : $this->lastHistoryId = null;

        isset($config->last_sync_date)
            ? $this->lastSyncDate = $config->last_sync_date
            : $this->lastSyncDate = null;

        isset($config->first_sync_date)
            ? $this->firstSyncDate = $config->first_sync_date
            : $this->firstSyncDate = null;
    }

    private function update()
    {
        $config = $this->configRepo->get('gmail');

        $config->setState([
            'next_page_token' => $this->pageToken,
            'last_history_id' => $this->lastHistoryId,
            'last_sync_date'  => $this->lastSyncDate
        ]);

        $this->configRepo->update($config);
    }

    /**
     * Make Gmail Connection
     *
     * @return Gmail
     */
    private function connect()
    {
        $user = $this->config['email'];

        $credentials = [
            'client_id' => $this->config['client_id'] ?? config('services.gmail.client_id'),
            'client_secret' => $this->config['client_secret'] ?? config('services.gmail.client_secret'),
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
