<?php

namespace Mautic\EmailBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Copy;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Form\Type\ConfigType;
use Mautic\EmailBundle\Helper\DTO\AddressDTO;
use Mautic\EmailBundle\Helper\Exception\OwnerNotFoundException;
use Mautic\EmailBundle\Mailer\Exception\BatchQueueMaxException;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportInterface;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Header\MailboxListHeader;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class MailHelper
{
    public const QUEUE_RESET_TO           = 'RESET_TO';

    public const QUEUE_FULL_RESET         = 'FULL_RESET';

    public const QUEUE_DO_NOTHING         = 'DO_NOTHING';

    public const QUEUE_NOTHING_IF_FAILED  = 'IF_FAILED';

    public const QUEUE_RETURN_ERRORS      = 'RETURN_ERRORS';

    public const EMAIL_TYPE_TRANSACTIONAL = 'transactional';

    public const EMAIL_TYPE_MARKETING     = 'marketing';

    private const DEFAULT_BODY            = [
        'content'     => '',
        'contentType' => 'text/html',
        'charset'     => null,
    ];

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var bool|MauticMessage
     */
    public $message;

    protected ?AddressDTO $from = null;

    protected ?AddressDTO $systemFrom = null;

    protected ?string $replyTo = null;

    protected ?string $systemReplyTo = null;

    protected int $addressLengthLimit;

    /**
     * @var string
     */
    protected $returnPath;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array|Lead
     */
    protected $lead;

    /**
     * @var bool
     */
    protected $internalSend = false;

    protected ?string $idHash = null;

    /**
     * @var bool
     */
    protected $idHashState = true;

    /**
     * @var bool
     */
    protected $appendTrackingPixel = false;

    /**
     * @var array
     */
    protected $source = [];

    /**
     * @var Email|null
     */
    protected $email;

    protected ?string $emailType = null;

    /**
     * @var array
     */
    protected $globalTokens = [];

    /**
     * @var array
     */
    protected $eventTokens = [];

    /**
     * Tells the helper that the transport supports tokenized emails (likely HTTP API).
     *
     * @var bool
     */
    protected $tokenizationEnabled = false;

    /**
     * Use queue mode when sending email through this mailer; this requires a transport that supports tokenization and the use of queue/flushQueue.
     *
     * @var bool
     */
    protected $queueEnabled = false;

    /**
     * @var array
     */
    protected $queuedRecipients = [];

    /**
     * @var array
     */
    public $metadata = [];

    /**
     * @var string
     */
    protected $subject              = '';
    private ?string $subjectInitial = null;

    /**
     * @var string
     */
    protected $plainText              = '';
    private ?string $plainTextInitial = null;

    /**
     * @var bool
     */
    protected $plainTextSet = false;

    /**
     * @var array
     */
    protected $assets = [];

    /**
     * @var array
     */
    protected $attachedAssets = [];

    /**
     * @var array
     */
    protected $assetStats = [];

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $body = self::DEFAULT_BODY;

    /**
     * @var array<?string>
     */
    private ?array $bodyInitial = null;

    /**
     * Cache for lead owners.
     *
     * @var array
     */
    protected static $leadOwners = [];

    /**
     * @var bool
     */
    protected $fatal = false;

    protected bool $skip = false;

    /**
     * Simply a md5 of the content so that event listeners can easily determine if the content has been changed.
     */
    private ?string $contentHash = null;

    private array $copies = [];

    private array $embedImagesReplaces = [];

    public function __construct(
        private MailerInterface $mailer,
        private FromEmailHelper $fromEmailHelper,
        private CoreParametersHelper $coreParametersHelper,
        private Mailbox $mailbox,
        private LoggerInterface $logger,
        private MailHashHelper $mailHashHelper,
        private RouterInterface $router,
        private Environment $twig,
        private ThemeHelper $themeHelper,
        private PathsHelper $pathsHelper,
        private EventDispatcherInterface $dispatcher,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private ModelFactory $modelFactory, // Can not inject EmailModel due to circular reference between MailHelper and EmailModel (even through other classes, like UserModel, SendEmailToContact)
        private AssetModel $assetModel,
        private TrackableModel $trackableModel,
        private RedirectModel $redirectModel,
    ) {
        $this->transport  = $this->getTransport();
        $this->returnPath = $coreParametersHelper->get('mailer_return_path');

        $systemFromEmail    = (string) $coreParametersHelper->get('mailer_from_email');
        $systemReplyToEmail = $coreParametersHelper->get('mailer_reply_to_email');
        $systemFromName     = $this->cleanName(
            $coreParametersHelper->get('mailer_from_name')
        );
        $this->addressLengthLimit = (int) $coreParametersHelper->get('mailer_address_length_limit');
        $this->setDefaultFrom(false, new AddressDTO($systemFromEmail, $systemFromName));
        $this->setDefaultReplyTo($systemReplyToEmail, $this->from);

        // Check if batching is supported by the transport
        if ($this->transport instanceof TokenTransportInterface) {
            $this->tokenizationEnabled = true;
        }

        $this->message = $this->getMessageInstance();
    }

    /**
     * @param bool $cleanSlate
     *
     * @return $this
     */
    public function getMailer($cleanSlate = true)
    {
        $this->reset($cleanSlate);

        return $this;
    }

    /**
     * Send the message.
     *
     * @param bool $dispatchSendEvent
     * @param bool $isQueueFlush      (a tokenized/batch send via API such as Mandrill)
     *
     * @return bool
     */
    public function send($dispatchSendEvent = false, $isQueueFlush = false)
    {
        if ($this->tokenizationEnabled && !empty($this->queuedRecipients) && !$isQueueFlush) {
            // This transport uses tokenization and queue()/flushQueue() was not used therefore use them in order
            // properly populate metadata for this transport

            if ($result = $this->queue($dispatchSendEvent)) {
                $result = $this->flushQueue(['To', 'Cc', 'Bcc']);
            }

            return $result;
        }

        // Set from email
        if (!$isQueueFlush) {
            $this->setFromForSingleMessage();
            $this->setReplyToForSingleMessage($this->email);
        } // from is set in flushQueue

        if (empty($this->message->getReplyTo()) && !empty($this->getReplyTo())) {
            $this->setMessageReplyTo($this->getReplyTo());
        }
        // Set system return path if applicable
        if (!$isQueueFlush && ($bounceEmail = $this->generateBounceEmail())) {
            $this->message->returnPath($bounceEmail);
        } elseif (!empty($this->returnPath)) {
            $this->message->returnPath($this->returnPath);
        }

        $this->dispatchPreSendEvent();
        if (empty($this->fatal)) {
            if (!$isQueueFlush) {
                // Search/replace tokens if this is not a queue flush

                // Generate tokens from listeners
                if ($dispatchSendEvent) {
                    $this->dispatchSendEvent();
                }

                // Queue an asset stat if applicable
                $this->queueAssetDownloadEntry();
            }

            $this->message->subject($this->subject);
            // Only set body if not empty or if plain text is empty - this ensures an empty HTML body does not show for
            // messages only with plain text
            if (!empty($this->body['content']) || empty($this->plainText)) {
                $this->message->html($this->body['content'], $this->body['charset'] ?? 'utf-8');
            }
            $this->setMessagePlainText();

            $this->setMessageHeaders();

            if (!$isQueueFlush) {
                // Replace token content
                $tokens = $this->getTokens();

                if ($ownerSignature = $this->fromEmailHelper->getSignature()) {
                    $tokens['{signature}'] = $ownerSignature;
                }

                if ($brandName = $this->coreParametersHelper->get('brand_name')) {
                    $tokens['{brand=name}'] = $brandName;
                }

                // Set metadata if applicable
                foreach ($this->queuedRecipients as $email => $name) {
                    $this->message->addMetadata($email, $this->buildMetadata($name, $tokens));
                }

                // Replace tokens
                $search  = array_keys($tokens);
                $replace = $tokens;

                self::searchReplaceTokens($search, $replace, $this->message);
            }

            if (true === $this->coreParametersHelper->get('mailer_convert_embed_images')) {
                $this->convertEmbedImages();
            }

            // Attach assets
            /** @var Asset $asset */
            foreach ($this->assets as $asset) {
                if (!in_array($asset->getId(), $this->attachedAssets)) {
                    $this->attachedAssets[] = $asset->getId();
                    $this->attachFile(
                        $asset->getFilePath(),
                        $asset->getOriginalFileName(),
                        $asset->getMime()
                    );
                }
            }

            try {
                if (!$this->skip) {
                    $this->mailer->send($this->message);
                    $this->message->clearMetadata();
                }
                $this->skip = false;
            } catch (TransportExceptionInterface $exception) {
                /*
                    The nature of symfony/mailer is working with transactional emails only
                    if a message fails to send, all the contacts on that message will be considered failed
                */
                $failures = $this->tokenizationEnabled ? array_keys($this->message->getMetadata()) : [];
                // Exception encountered when sending so all recipients are considered failures
                $this->errors['failures'] = array_unique(
                    array_merge(
                        $failures,
                        array_keys((array) $this->message->getTo()),
                        array_keys((array) $this->message->getCc()),
                        array_keys((array) $this->message->getBcc())
                    )
                );

                $this->logError($exception->getMessage());
            }
        }

        $error = empty($this->errors);

        if (!$isQueueFlush) {
            $this->createAssetDownloadEntries();
        } // else handled in flushQueue

        return $error;
    }

    /**
     * If batching is supported and enabled, the message will be queued and will on be sent upon flushQueue().
     * Otherwise, the message will be sent to the transport immediately.
     *
     * @param bool   $dispatchSendEvent
     * @param string $returnMode        What should happen post send/queue to $this->message after the email send is attempted.
     *                                  Options are:
     *                                  RESET_TO           resets the to recipients and resets errors
     *                                  FULL_RESET         creates a new MauticMessage instance and resets errors
     *                                  DO_NOTHING         leaves the current errors array and MauticMessage instance intact
     *                                  NOTHING_IF_FAILED  leaves the current errors array MauticMessage instance intact if it fails, otherwise reset_to
     *                                  RETURN_ERROR       return an array of [success, $errors]; only one applicable if message is queued
     *
     * @return bool|array
     */
    public function queue($dispatchSendEvent = false, $returnMode = self::QUEUE_RESET_TO)
    {
        if ($this->tokenizationEnabled) {
            // Dispatch event to get custom tokens from listeners
            if ($dispatchSendEvent) {
                $this->dispatchSendEvent();
            }

            // Metadata has to be set for each recipient
            foreach ($this->queuedRecipients as $email => $name) {
                $from        = $this->fromEmailHelper->getFromAddressConsideringOwner($this->getFrom(), $this->lead, $this->email);
                $fromAddress = $from->getEmail();

                $tokens                = $this->getTokens();
                $tokens['{signature}'] = $this->fromEmailHelper->getSignature();

                if (!isset($this->metadata[$fromAddress])) {
                    $this->metadata[$fromAddress] = [
                        'from'     => $from,
                        'contacts' => [],
                    ];
                }

                $this->metadata[$fromAddress]['contacts'][$email] = $this->buildMetadata($name, $tokens);
            }

            // Reset recipients
            $this->queuedRecipients = [];

            // Assume success
            return (self::QUEUE_RETURN_ERRORS) ? [true, []] : true;
        } else {
            $success = $this->send($dispatchSendEvent);

            // Reset the message for the next
            $this->queuedRecipients = [];

            // Reset message
            switch (strtoupper($returnMode)) {
                case self::QUEUE_RESET_TO:
                    $this->message->to();
                    $this->clearErrors();
                    break;
                case self::QUEUE_NOTHING_IF_FAILED:
                    if ($success) {
                        $this->message->to();
                        $this->clearErrors();
                    }

                    break;
                case self::QUEUE_FULL_RESET:
                    $this->message        = $this->getMessageInstance();
                    $this->attachedAssets = [];
                    $this->clearErrors();
                    break;
                case self::QUEUE_RETURN_ERRORS:
                    $this->message->to();
                    $errors = $this->getErrors();
                    $this->clearErrors();

                    return [$success, $errors];
                case self::QUEUE_DO_NOTHING:
                default:
                    // Nada

                    break;
            }

            return $success;
        }
    }

    /**
     * Send batched mail to mailer.
     *
     * @param array $resetEmailTypes Array of email types to clear after flusing the queue
     *
     * @return bool
     */
    public function flushQueue($resetEmailTypes = ['To', 'Cc', 'Bcc'])
    {
        // Assume true unless there was a fatal error configuring the mailer because if tokenizationEnabled is false, the send happened in queue()
        $flushed = empty($this->fatal);
        if ($this->tokenizationEnabled && count($this->metadata) && $flushed) {
            $errors             = $this->errors;
            $errors['failures'] = [];
            $flushed            = false;

            foreach ($this->metadata as $metadatum) {
                // Whatever is in the message "to" should be ignored as we will send to the contacts grouped by from addresses
                // This prevents mailers such as sparkpost from sending duplicates to contacts
                $this->message->to();
                $this->errors = [];

                $email = $this->getEmail();

                if ($email && $email->getUseOwnerAsMailer()) {
                    $this->setFrom($metadatum['from']->getEmail(), $metadatum['from']->getName());
                    $this->setMessageFrom(new AddressDTO($metadatum['from']->getEmail(), $metadatum['from']->getName()));
                } else {
                    $this->setMessageFrom($this->fromEmailHelper->getFrom($email));
                }

                foreach ($metadatum['contacts'] as $email => $contact) {
                    $this->message->addMetadata($email, $contact);

                    // Add asset stats if applicable
                    if (!empty($contact['leadId'])) {
                        $this->queueAssetDownloadEntry($email, $contact);
                    }
                    $this->message->to(new Address($email, $contact['name'] ?? ''));
                }

                $flushed = $this->send(false, true);

                // Merge errors
                if (isset($this->errors['failures'])) {
                    $errors['failures'] = array_merge($errors['failures'], $this->errors['failures']);
                    unset($this->errors['failures']);
                }

                if (!empty($this->errors)) {
                    $errors = array_merge($errors, $this->errors);
                }

                // Clear metadata for the previous recipients
                $this->message->clearMetadata();
            }

            $this->errors = $errors;

            // Clear queued to recipients
            $this->queuedRecipients = [];
            $this->metadata         = [];
        }

        foreach ($resetEmailTypes as $type) {
            $this->message->{$type}();
        }

        return $flushed;
    }

    /**
     * Resets the mailer.
     *
     * @param bool $cleanSlate
     */
    public function reset($cleanSlate = true): void
    {
        $this->eventTokens      = [];
        $this->queuedRecipients = [];
        $this->errors           = [];
        $this->lead             = null;
        $this->idHash           = null;
        $this->contentHash      = null;
        $this->internalSend     = false;
        $this->fatal            = false;
        $this->idHashState      = true;

        if ($cleanSlate) {
            $this->appendTrackingPixel = false;
            $this->queueEnabled        = false;
            $this->from                = $this->getSystemFrom();
            $this->replyTo             = $this->getSystemReplyTo();
            $this->headers             = [];
            $this->source              = [];
            $this->assets              = [];
            $this->globalTokens        = [];
            $this->attachedAssets      = [];
            $this->email               = null;
            $this->copies              = [];
            $this->message             = $this->getMessageInstance();
            $this->subject             = '';
            $this->subjectInitial      = null;
            $this->plainText           = '';
            $this->plainTextInitial    = null;
            $this->plainTextSet        = false;
            $this->body                = self::DEFAULT_BODY;
            $this->bodyInitial         = null;
        }
    }

    /**
     * Search and replace tokens
     * Adapted from \Swift_Plugins_DecoratorPlugin.
     *
     * @param array $search
     * @param array $replace
     */
    public static function searchReplaceTokens($search, $replace, MauticMessage &$message): void
    {
        // Body
        $body         = $message->getHtmlBody();
        $bodyReplaced = str_ireplace($search, $replace, (string) $body, $updated);
        if ($updated) {
            $message->html($bodyReplaced);
        }
        unset($body, $bodyReplaced);

        // Subject
        $subject      = $message->getSubject();
        $bodyReplaced = str_ireplace($search, $replace, $subject, $updated);

        if ($updated) {
            $message->subject($bodyReplaced);
        }
        unset($subject, $bodyReplaced);

        // Headers
        /** @var HeaderInterface $header */
        foreach ($message->getHeaders()->all() as $header) {
            // It only makes sense to tokenize headers that can be interpreted as text.
            if (!$header instanceof UnstructuredHeader) {
                continue;
            }
            $headerBody   = $header->getBody();
            $bodyReplaced = str_ireplace($search, $replace, $headerBody);
            $header->setBody($bodyReplaced);
        }

        // Parts (plaintext)
        $textBody     = $message->getTextBody() ?? '';
        $bodyReplaced = str_ireplace($search, $replace, $textBody);
        if ($textBody != $bodyReplaced) {
            $textBody = strip_tags($bodyReplaced);
            $message->text($textBody);
        }
    }

    public static function getBlankPixel(): string
    {
        return 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
    }

    /**
     * Add an attachment to email.
     *
     * @param string $filePath
     * @param string $fileName
     * @param string $contentType
     * @param bool   $inline
     */
    public function attachFile($filePath, $fileName = null, $contentType = null, $inline = false): void
    {
        if (true === $inline) {
            $this->message->embedFromPath($filePath, $fileName, $contentType);

            return;
        }
        $this->message->attachFromPath($filePath, $fileName, $contentType);
    }

    /**
     * @param int|Asset $asset
     */
    public function attachAsset($asset): void
    {
        if (!$asset instanceof Asset) {
            $asset = $this->assetModel->getEntity($asset);

            if (null == $asset) {
                return;
            }
        }

        if ($asset->isPublished()) {
            $asset->setUploadDir($this->coreParametersHelper->get('upload_dir'));
            $this->assets[$asset->getId()] = $asset;
        }
    }

    /**
     * Use a template as the body.
     *
     * @param string $template
     * @param array  $vars
     * @param bool   $returnContent
     *
     * @return void|string
     */
    public function setTemplate($template, $vars = [], $returnContent = false, $charset = null)
    {
        $content = $this->twig->render($template, $vars);

        unset($vars);

        if ($returnContent) {
            return $content;
        }

        $this->setBody($content, 'text/html', $charset);
        unset($content);
    }

    public function setSubject($subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set a plain text part.
     */
    public function setPlainText($content): void
    {
        $this->plainText = $content;

        // Update the identifier for the content
        $this->contentHash = md5($this->body['content'].$this->plainText);
    }

    /**
     * @return string
     */
    public function getPlainText()
    {
        return $this->plainText;
    }

    /**
     * Set plain text for $this->message, replacing if necessary.
     */
    protected function setMessagePlainText()
    {
        if ($this->tokenizationEnabled && $this->plainTextSet) {
            // No need to find and replace since tokenization happens at the transport level

            return;
        }
        $this->message->text($this->plainText);
        $this->plainTextSet = true;
    }

    /**
     * @param string $contentType
     * @param bool   $ignoreTrackingPixel
     */
    public function setBody($content, $contentType = 'text/html', $charset = null, $ignoreTrackingPixel = false): void
    {
        if (!$ignoreTrackingPixel && $this->coreParametersHelper->get('mailer_append_tracking_pixel')) {
            // Append tracking pixel
            $trackingImg = '<img height="1" width="1" src="{tracking_pixel}" alt="" />';
            if (str_contains((string) $content, '</body>')) {
                $content = str_replace('</body>', $trackingImg.'</body>', $content);
            } else {
                $content .= $trackingImg;
            }
        }

        // Update the identifier for the content
        $this->contentHash = md5($content.$this->plainText);

        $this->body = [
            'content'     => $content,
            'contentType' => $contentType,
            'charset'     => $charset,
        ];
    }

    private function convertEmbedImages(): void
    {
        $content = $this->message->getHtmlBody();
        $matches = [];
        $content = strtr($content, $this->embedImagesReplaces);
        $tokens  = $this->getTokens();
        if (preg_match_all('/<img.+?src=[\"\'](.+?)[\"\'].*?>/i', $content, $matches) > 0) {
            foreach ($matches[1] as $match) {
                // skip items that already embedded, or have token {tracking_pixel}
                if (str_contains($match, 'cid:') || str_contains($match, '{tracking_pixel}') || array_key_exists($match, $this->embedImagesReplaces)) {
                    continue;
                }

                // skip images with tracking pixel that are already replaced.
                if (isset($tokens['{tracking_pixel}']) && $match === $tokens['{tracking_pixel}']) {
                    continue;
                }

                $path = $match;
                // if the path contains the site url, make it an absolute path, so it can be fetched.
                if (str_starts_with($match, $this->coreParametersHelper->get('site_url'))) {
                    $path = str_replace($this->coreParametersHelper->get('site_url'), '', $match);
                    $path = $this->pathsHelper->getSystemPath('root', true).$path;
                }

                if ($imageContent = file_get_contents($path)) {
                    $this->message->embed($imageContent, md5($match));
                    $this->embedImagesReplaces[$match] = 'cid:'.md5($match);
                }
            }
            $content = strtr($content, $this->embedImagesReplaces);
        }

        $this->message->html($content);
    }

    /**
     * Get a copy of the raw body.
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->body['content'];
    }

    /**
     * Return the content identifier.
     *
     * @return string
     */
    public function getContentHash()
    {
        return $this->contentHash;
    }

    /**
     * Set to address(es).
     */
    public function setTo($addresses, $name = null): bool
    {
        $name = $this->cleanName($name);

        if (!is_array($addresses)) {
            $addresses = [$addresses => $name];
        } elseif (0 === array_keys($addresses)[0]) {
            // We need an array of $email => $name pairs
            $addresses = array_reduce($addresses, function ($address, $item) use ($name) {
                $address[$item] = $name;

                return $address;
            }, []);
        }

        $this->checkBatchMaxRecipients(count($addresses));

        // Convert to array of Address objects
        $toAddresses = array_map(fn (string $address, ?string $name): Address => new Address($address, $name ?? ''), array_keys($addresses), $addresses);

        try {
            $this->message->to(...$toAddresses);
            $this->queuedRecipients = array_merge($this->queuedRecipients, $addresses);

            return true;
        } catch (\Exception $e) {
            $this->logError($e, 'to');

            return false;
        }
    }

    /**
     * Add to address.
     *
     * @param string      $address
     * @param string|null $name
     */
    public function addTo($address, $name = null): bool
    {
        $this->checkBatchMaxRecipients();

        try {
            $fullAddress          = (new AddressDTO($address, $name))->toMailerAddress();
            $encodedAddressLength = strlen((new MailboxListHeader('To', [$fullAddress]))->getBodyAsString());

            if ($encodedAddressLength > $this->addressLengthLimit) {
                // When encoded address with name length doesn't meet the limit, use only the email
                $shortAddress = (new AddressDTO($address))->toMailerAddress();
                $this->message->addTo($shortAddress);
            } else {
                $this->message->addTo($fullAddress);
            }
            $this->queuedRecipients[$address] = $name;

            return true;
        } catch (\Exception $e) {
            $this->logError($e, 'to');

            return false;
        }
    }

    /**
     * Helper method to set CC or BCC recipients.
     *
     * @param string                    $type      Type of recipient (cc or bcc)
     * @param array<string|int,?string> $addresses Array of emails as values or keys
     * @param ?string                   $name      Default name for addresses without specified names
     */
    private function setRecipients(string $type, $addresses, $name = null): bool
    {
        $this->checkBatchMaxRecipients(count($addresses), $type);

        try {
            $recipientAddresses = [];

            foreach ($addresses as $key => $value) {
                // Check if we have an indexed array (numeric keys)
                if (is_numeric($key)) {
                    $address     = $value;
                    $addressName = $name;
                } else {
                    // We have an associative array (email => name)
                    $address     = $key;
                    $addressName = $value ?: $name; // Use provided name or default
                }

                $recipientAddresses[] = (new AddressDTO($address, $addressName))->toMailerAddress();
            }

            if ('cc' === $type) {
                $this->message->cc(...$recipientAddresses);
            } else {
                $this->message->bcc(...$recipientAddresses);
            }

            return true;
        } catch (\Exception $e) {
            $this->logError($e, $type);

            return false;
        }
    }

    /**
     * Set CC address(es).
     *
     * @param array<string|int,?string> $addresses Array of emails as values or keys
     * @param ?string                   $name      Default name for addresses without specified names
     */
    public function setCc($addresses, $name = null): bool
    {
        return $this->setRecipients('cc', $addresses, $name);
    }

    /**
     * Add cc address.
     *
     * @param string  $address
     * @param ?string $name
     */
    public function addCc($address, $name = null): bool
    {
        $this->checkBatchMaxRecipients(1, 'cc');

        try {
            $this->message->addCc((new AddressDTO($address, $name ?? ''))->toMailerAddress());

            return true;
        } catch (\Exception $e) {
            $this->logError($e, 'cc');

            return false;
        }
    }

    /**
     * Set BCC address(es).
     *
     * @param array<string|int,?string> $addresses Array of emails as values or keys
     * @param ?string                   $name      Default name for addresses without specified names
     */
    public function setBcc($addresses, $name = null): bool
    {
        return $this->setRecipients('bcc', $addresses, $name);
    }

    /**
     * Add bcc address.
     *
     * @param string  $address
     * @param ?string $name
     */
    public function addBcc($address, $name = null): bool
    {
        $this->checkBatchMaxRecipients(1, 'bcc');

        try {
            $this->message->addBcc((new AddressDTO($address, $name))->toMailerAddress());

            return true;
        } catch (\Exception $e) {
            $this->logError($e, 'bcc');

            return false;
        }
    }

    /**
     * @param int    $toBeAdded
     * @param string $type
     *
     * @throws BatchQueueMaxException
     */
    protected function checkBatchMaxRecipients($toBeAdded = 1, $type = 'to')
    {
        if ($this->queueEnabled && $this->transport instanceof TokenTransportInterface) {
            // Check if max batching has been hit
            $maxAllowed = $this->transport->getMaxBatchLimit();

            if ($maxAllowed > 0) {
                $currentCount = $this->transport->getBatchRecipientCount($this->message, $toBeAdded, $type);
                if ($currentCount > $maxAllowed) {
                    throw new BatchQueueMaxException();
                }
            }
        }
    }

    /**
     * Set reply to address(es) for this mailer instance.
     *
     * @param array<string>|string $addresses
     * @param string               $name
     */
    public function setReplyTo($addresses, $name = null): void
    {
        $this->replyTo = $addresses;
    }

    /**
     * Set Reply to for the current message we are sending. Can be in the middle of the sending loop.
     */
    private function setMessageReplyTo(string $addresses, string $name = null): void
    {
        if (str_contains($addresses, ',')) {
            $addresses = explode(',', $addresses);
        }

        try {
            foreach ((array) $addresses as $address) {
                $this->message->replyTo((new AddressDTO($address, $name))->toMailerAddress());
            }
        } catch (\Exception $e) {
            $this->logError($e, 'reply to');
        }
    }

    /**
     * Set a custom return path.
     *
     * @param string $address
     */
    public function setReturnPath($address): void
    {
        try {
            $this->message->returnPath($address);
        } catch (\Exception $e) {
            $this->logError($e, 'return path');
        }
    }

    /**
     * Sets FROM for the mailer which can overwrite the system default.
     *
     * @param string|array $fromEmail
     * @param string       $fromName
     */
    public function setFrom($fromEmail, $fromName = null): void
    {
        if (is_array($fromEmail)) {
            $this->from = AddressDTO::fromAddressArray($fromEmail);
            $this->from->setName($fromName);
        } else {
            $this->from = new AddressDTO($fromEmail, $fromName);
        }
    }

    /**
     * Sets FROM for the concreste message that we are currently sending. Can be in the middle of the loop of sending.
     */
    private function setMessageFrom(AddressDTO $from): void
    {
        try {
            $this->message->from($from->toMailerAddress());
        } catch (\Exception $e) {
            $this->logError($e, 'from');
        }
    }

    /**
     * @return string|null
     */
    public function getIdHash()
    {
        return $this->idHash;
    }

    /**
     * @param string|null $idHash
     * @param bool        $statToBeGenerated Pass false if a stat entry is not to be created
     */
    public function setIdHash($idHash = null, $statToBeGenerated = true): void
    {
        if (null === $idHash) {
            $idHash = str_replace('.', '', uniqid('', true));
        }

        $this->idHash      = $idHash;
        $this->idHashState = $statToBeGenerated;

        // Append pixel to body before send
        $this->appendTrackingPixel = true;

        // Add the trackingID to the $message object in order to update the stats if the email failed to send
        $this->message->updateLeadIdHash($idHash);
    }

    /**
     * @return array|Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param array|Lead $lead
     */
    public function setLead($lead, $interalSend = false): void
    {
        $this->lead         = $lead;
        $this->internalSend = $interalSend;
    }

    /**
     * Check if this is not being send directly to the lead.
     *
     * @return bool
     */
    public function isInternalSend()
    {
        return $this->internalSend;
    }

    /**
     * @return array
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param array $source
     */
    public function setSource($source): void
    {
        $this->source = $source;
    }

    public function getEmailType(): ?string
    {
        return $this->emailType;
    }

    public function setEmailType(?string $emailType): void
    {
        $this->emailType = $emailType;
    }

    /**
     * @return Email|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param bool  $allowBcc            Honor BCC if set in email
     * @param array $assetAttachments    Assets to send
     * @param bool  $ignoreTrackingPixel Do not append tracking pixel HTML
     *
     * @return bool Returns false if there were errors with the email configuration
     */
    public function setEmail(Email $email, $allowBcc = true, $assetAttachments = [], $ignoreTrackingPixel = false): bool
    {
        if ($this->coreParametersHelper->get(ConfigType::MINIFY_EMAIL_HTML)) {
            $email->setCustomHtml(InputHelper::minifyHTML($email->getCustomHtml()));
        }

        $this->email = $email;

        $subject = $email->getSubject();

        // Set message settings from the email
        $this->setSubject($subject);

        if ($allowBcc) {
            $bccAddress = $email->getBccAddress();
            if (!empty($bccAddress)) {
                $addresses = array_fill_keys(array_map('trim', explode(',', $bccAddress)), null);
                foreach ($addresses as $bccAddress => $name) {
                    $this->addBcc($bccAddress, $name);
                }
            }
        }

        if ($plainText = $email->getPlainText()) {
            $this->setPlainText($plainText);
        }

        $template   = $email->getTemplate();
        $customHtml = $email->getCustomHtml();
        // Process emails created by Mautic v1
        if (empty($customHtml) && $template) {
            $logicalName = $this->themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/email.html.twig');

            $customHtml = $this->setTemplate($logicalName, [
                'content'  => $email->getContent(),
                'email'    => $email,
                'template' => $template,
            ], true);
        }

        $this->setBody($customHtml, 'text/html', null, $ignoreTrackingPixel);

        // Reset attachments
        $this->assets = $this->attachedAssets = [];
        if (empty($assetAttachments)) {
            if ($assets = $email->getAssetAttachments()) {
                foreach ($assets as $asset) {
                    $this->attachAsset($asset);
                }
            }
        } else {
            foreach ($assetAttachments as $asset) {
                $this->attachAsset($asset);
            }
        }

        // Set custom headers
        if ($headers = $email->getHeaders()) {
            // HTML decode headers
            $headers = array_map('html_entity_decode', $headers);

            foreach ($headers as $name => $value) {
                $this->addCustomHeader($name, $value);
            }
        }

        return empty($this->errors);
    }

    /**
     * Set custom headers.
     *
     * @param bool $merge
     */
    public function setCustomHeaders(array $headers, $merge = true): void
    {
        if ($merge) {
            $this->headers = array_merge($this->headers, $headers);

            return;
        }

        $this->headers = $headers;
    }

    public function addCustomHeader($name, $value): void
    {
        $this->headers[$name] = $value;
    }

    public function getCustomHeaders(): array
    {
        $headers = array_merge($this->headers, $this->getSystemHeaders());

        // Personal and transactional emails do not contain unsubscribe header
        $email = $this->getEmail();
        if (empty($email) || self::EMAIL_TYPE_TRANSACTIONAL === $this->getEmailType()) {
            return $headers;
        }

        $listUnsubscribeHeader = $this->getUnsubscribeHeader();
        if ($listUnsubscribeHeader) {
            if (!empty($headers['List-Unsubscribe'])) {
                if (!str_contains($headers['List-Unsubscribe'], $listUnsubscribeHeader)) {
                    // Ensure Mautic's is always part of this header
                    $headers['List-Unsubscribe'] = $listUnsubscribeHeader.','.$headers['List-Unsubscribe'];
                }
            } else {
                $headers['List-Unsubscribe'] = $listUnsubscribeHeader;
            }
            $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        }

        return $headers;
    }

    /**
     * @return bool|string
     */
    private function getUnsubscribeHeader()
    {
        if ($this->idHash) {
            $lead    = $this->getLead();
            $toEmail = null;
            if (is_array($lead) && array_key_exists('email', $lead) && is_string($lead['email'])) {
                $toEmail = $lead['email'];
            } elseif ($lead instanceof Lead && is_string($lead->getEmail())) {
                $toEmail = $lead->getEmail();
            }

            if ($toEmail) {
                $unsubscribeHash = $this->mailHashHelper->getEmailHash($toEmail);
                $url             = $this->router->generate('mautic_email_unsubscribe',
                    ['idHash' => $this->idHash, 'urlEmail' => $toEmail, 'secretHash' => $unsubscribeHash],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            } else {
                $url             = $this->router->generate('mautic_email_unsubscribe',
                    ['idHash' => $this->idHash],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            return "<$url>";
        }

        if (!empty($this->queuedRecipients) || !empty($this->lead)) {
            return '<{unsubscribe_url}>';
        }

        return false;
    }

    /**
     * Append tokens.
     */
    public function addTokens(array $tokens): void
    {
        $this->globalTokens = array_merge($this->globalTokens, $tokens);
    }

    public function setTokens(array $tokens): void
    {
        $this->globalTokens = $tokens;
    }

    /**
     * @return mixed[]
     */
    public function getTokens(): array
    {
        $tokens = array_merge($this->globalTokens, $this->eventTokens);

        // Include the tracking pixel token as it's auto appended to the body
        if ($this->appendTrackingPixel) {
            $tokens['{tracking_pixel}'] = $this->router->generate(
                'mautic_email_tracker',
                [
                    'idHash' => $this->idHash,
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } else {
            $tokens['{tracking_pixel}'] = self::getBlankPixel();
        }

        return $tokens;
    }

    /**
     * @return array
     */
    public function getGlobalTokens()
    {
        return $this->globalTokens;
    }

    /**
     * Parses html into basic plaintext.
     *
     * @param string $content
     */
    public function parsePlainText($content = null): void
    {
        if (null == $content) {
            if (!$content = $this->message->getHtmlBody()) {
                $content = $this->body['content'];
            }
        }

        $request = $this->requestStack->getMainRequest();
        if (null !== $request) {
            $baseUrl = $request->getSchemeAndHttpHost().$request->getBasePath();
        } else {
            $baseUrl = $this->coreParametersHelper->get('site_url');
        }

        $parser  = new PlainTextHelper([
            'base_url' => $baseUrl,
        ]);

        $this->plainText = $parser->setHtml($content)->getText();
    }

    /**
     * Enables queue mode if the transport supports tokenization.
     *
     * @param bool $enabled
     */
    public function enableQueue($enabled = true): void
    {
        if ($this->tokenizationEnabled) {
            $this->queueEnabled = $enabled;
        }
    }

    /**
     * Dispatch send event to generate tokens.
     */
    public function dispatchSendEvent(): void
    {
        if (null === $this->bodyInitial) {
            $this->bodyInitial = $this->body;
        }

        if (null === $this->plainTextInitial) {
            $this->plainTextInitial = $this->plainText;
        }

        if (null === $this->subjectInitial) {
            $this->subjectInitial = $this->subject;
        }

        // Reset body, text, subject and tokens, so the latter listeners use the same data as the former ones.
        $this->setBody($this->bodyInitial['content'], $this->bodyInitial['contentType'], $this->bodyInitial['charset'], true);
        $this->setPlainText($this->plainTextInitial);
        $this->setSubject($this->subjectInitial);
        $this->eventTokens = [];

        $event = new EmailSendEvent($this);
        $this->dispatcher->dispatch($event, EmailEvents::EMAIL_ON_SEND);
        $this->eventTokens = $event->getTokens(false);
    }

    /**
     * Log exception.
     */
    protected function logError($error, $context = null)
    {
        if ($error instanceof \Exception) {
            $exceptionContext = ['exception' => $error];
            $errorMessage     = $error->getMessage();
            $error            = ('dev' === MAUTIC_ENV) ? (string) $error : $errorMessage;

            // Clean up the error message
            $errorMessage = trim(preg_replace('/(.*?)Log data:(.*)$/is', '$1', $errorMessage));

            $this->fatal = true;
        } else {
            $exceptionContext = [];
            $errorMessage     = trim($error);
        }

        if ($context) {
            $error .= " ($context)";

            if ('send' === $context) {
                $error .= '; '.implode(', ', $this->errors['failures']);
            }
        }

        $this->errors[] = $errorMessage;

        $this->logger->log('error', '[MAIL ERROR] '.$error, $exceptionContext);
    }

    /**
     * Get list of errors.
     *
     * @param bool $reset Resets the error array in preparation for the next mail send or else it'll fail
     *
     * @return array
     */
    public function getErrors($reset = true)
    {
        $errors = $this->errors;

        if ($reset) {
            $this->clearErrors();
        }

        return $errors;
    }

    /**
     * Clears the errors from a previous send.
     */
    public function clearErrors(): void
    {
        $this->errors = [];
        $this->fatal  = false;
    }

    /**
     * @return TransportInterface
     */
    public function getTransport()
    {
        $reflectedMailer     = new \ReflectionClass($this->mailer);
        $reflectedTransports = $reflectedMailer->getProperty('transport');
        $reflectedTransports->setAccessible(true);
        $allTransports = $reflectedTransports->getValue($this->mailer);

        $reflectedTransports = new \ReflectionClass($allTransports);
        $reflectedTransport  = $reflectedTransports->getProperty('transports');

        $reflectedTransport->setAccessible(true);

        $currentTransport = $reflectedTransport->getValue($allTransports);

        return $currentTransport['main'];
    }

    /**
     * Creates a download stat for the asset.
     */
    protected function createAssetDownloadEntries()
    {
        // Nothing was sent out so bail
        if ($this->fatal || empty($this->assetStats)) {
            return;
        }

        if (isset($this->errors['failures'])) {
            // Remove the failures from the asset queue
            foreach ($this->errors['failures'] as $failed) {
                unset($this->assetStats[$failed]);
            }
        }

        // Create a download entry if there is an Asset attachment
        if (!empty($this->assetStats)) {
            foreach ($this->assets as $asset) {
                foreach ($this->assetStats as $stat) {
                    $this->assetModel->trackDownload(
                        $asset,
                        null,
                        200,
                        $stat
                    );
                }

                $this->assetModel->upDownloadCount($asset, count($this->assetStats), true);
            }
        }

        // Reset the stat
        $this->assetStats = [];
    }

    /**
     * Queues the details to note if a lead received an asset if no errors are generated.
     */
    protected function queueAssetDownloadEntry($contactEmail = null, array $metadata = null)
    {
        if ($this->internalSend || empty($this->assets)) {
            return;
        }

        if (null === $contactEmail) {
            if (!$this->lead) {
                return;
            }

            $contactEmail = $this->lead['email'];
            $contactId    = $this->lead['id'];
            $emailId      = $this->email->getId();
            $idHash       = $this->idHash;
        } else {
            $contactId = $metadata['leadId'];
            $emailId   = $metadata['emailId'];
            $idHash    = $metadata['hashId'];
        }

        $this->assetStats[$contactEmail] = [
            'lead'        => $contactId,
            'email'       => $emailId,
            'source'      => ['email', $emailId],
            'tracking_id' => $idHash,
        ];
    }

    /**
     * Returns if the mailer supports and is in tokenization mode.
     *
     * @return bool
     */
    public function inTokenizationMode()
    {
        return $this->tokenizationEnabled;
    }

    /**
     * @return \Mautic\PageBundle\Entity\Redirect|object|null
     */
    public function getTrackableLink($url)
    {
        // Ensure a valid URL and that it has not already been found
        if (!str_starts_with($url, 'http') && !str_starts_with($url, 'ftp')) {
            return null;
        }

        if ($this->email) {
            // Get a Trackable which is channel aware
            $trackable = $this->trackableModel->getTrackableByUrl($url, 'email', $this->email->getId());

            return $trackable->getRedirect();
        }

        return $this->redirectModel->getRedirectByUrl($url);
    }

    /**
     * Create an email stat.
     *
     * @param bool|true   $persist
     * @param string|null $emailAddress
     */
    public function createEmailStat($persist = true, $emailAddress = null, $listId = null): Stat
    {
        $stat = new Stat();
        $stat->setDateSent(new \DateTime());
        $emailExists = $this->email && $this->email->getId();

        if ($emailExists) {
            $stat->setEmail($this->email);
        }

        // Note if a lead
        if (null !== $this->lead) {
            try {
                $stat->setLead($this->entityManager->getReference(Lead::class, $this->lead['id']));
            } catch (ORMException) {
                // keep IDE happy
            }
            $emailAddress = $this->lead['email'];
        }

        // Find email if applicable
        if (null === $emailAddress) {
            // Use the last address set
            $emailAddresses = $this->message->getTo();

            if (count($emailAddresses)) {
                $emailAddress = array_key_last($emailAddresses);
            }
        }
        $stat->setEmailAddress($emailAddress);

        // Note if sent from a lead list
        if (null !== $listId) {
            try {
                $stat->setList($this->entityManager->getReference(\Mautic\LeadBundle\Entity\LeadList::class, $listId));
            } catch (ORMException) {
                // keep IDE happy
            }
        }

        $stat->setTrackingHash($this->idHash);
        if (!empty($this->source)) {
            $stat->setSource($this->source[0]);
            $stat->setSourceId($this->source[1]);
        }

        $stat->setTokens($this->getTokens());

        $emailModel = $this->modelFactory->getModel(EmailModel::class);
        \assert($emailModel instanceof EmailModel);

        // Save a copy of the email - use email ID if available simply to prevent from having to rehash over and over
        $id = $emailExists ? $this->email->getId() : md5($this->subject.$this->body['content']);
        if (!isset($this->copies[$id])) {
            $hash = (32 !== strlen($id)) ? md5($this->subject.$this->body['content']) : $id;

            $copy        = $emailModel->getCopyRepository()->findByHash($hash);
            $copyCreated = false;
            if (null === $copy) {
                $contentToPersist = strtr($this->body['content'], array_flip($this->embedImagesReplaces));
                if (!$emailModel->getCopyRepository()->saveCopy($hash, $this->subject, $contentToPersist, $this->plainText)) {
                    // Try one more time to find the ID in case there was overlap when creating
                    $copy = $emailModel->getCopyRepository()->findByHash($hash);
                } else {
                    $copyCreated = true;
                }
            }

            if ($copy || $copyCreated) {
                $this->copies[$id] = $hash;
            }
        }

        if (isset($this->copies[$id])) {
            try {
                $stat->setStoredCopy($this->entityManager->getReference(Copy::class, $this->copies[$id]));
            } catch (ORMException) {
                // keep IDE happy
            }
        }

        if ($persist) {
            $emailModel->saveEmailStat($stat);
        }

        return $stat;
    }

    /**
     * Check to see if a monitored email box is enabled and configured.
     *
     * @return bool|array
     */
    public function isMontoringEnabled($bundleKey, $folderKey)
    {
        if ($this->mailbox->isConfigured($bundleKey, $folderKey)) {
            return $this->mailbox->getMailboxSettings();
        }

        return false;
    }

    /**
     * Generate bounce email for the lead.
     *
     * @return bool|string
     */
    public function generateBounceEmail($idHash = null)
    {
        $monitoredEmail = false;

        if ($settings = $this->isMontoringEnabled('EmailBundle', 'bounces')) {
            // Append the bounce notation
            [$email, $domain] = explode('@', $settings['address']);
            $email .= '+bounce';
            if ($idHash || $this->idHash) {
                $email .= '_'.($idHash ?: $this->idHash);
            }
            $monitoredEmail = $email.'@'.$domain;
        }

        return $monitoredEmail;
    }

    /**
     * Generate an unsubscribe email for the lead.
     *
     * @return bool|string
     */
    public function generateUnsubscribeEmail($idHash = null)
    {
        $monitoredEmail = false;

        if ($settings = $this->isMontoringEnabled('EmailBundle', 'unsubscribes')) {
            // Append the bounce notation
            [$email, $domain] = explode('@', $settings['address']);
            $email .= '+unsubscribe';
            if ($idHash || $this->idHash) {
                $email .= '_'.($idHash ?: $this->idHash);
            }
            $monitoredEmail = $email.'@'.$domain;
        }

        return $monitoredEmail;
    }

    /**
     * Clean the name - if empty, set as null to ensure pretty headers.
     *
     * @return string|null
     */
    protected function cleanName($name)
    {
        if (null === $name) {
            return $name;
        }

        $name = trim(html_entity_decode($name, ENT_QUOTES));

        // If empty, replace with null so that email clients do not show empty name because of To: '' <email@domain.com>
        if (empty($name)) {
            $name = null;
        }

        return $name;
    }

    /**
     * @return array<string,string>
     */
    private function getSystemHeaders(): array
    {
        /**
         * This section is stopped, because it is preventing global headers from being merged
         *         if ($this->email) {
         *           // We are purposively ignoring system headers if using an Email entity
         *           return [];
         *      }.
         */
        if (!$systemHeaders = $this->coreParametersHelper->get('mailer_custom_headers', [])) {
            return [];
        }

        // HTML decode headers
        $systemHeaders = array_map('html_entity_decode', $systemHeaders);

        return $systemHeaders;
    }

    /**
     * Merge system headers into custom headers if applicable.
     */
    private function setMessageHeaders(): void
    {
        $headers = $this->getCustomHeaders();

        // Set custom headers
        if (!empty($headers)) {
            $tokens = $this->getTokens();
            // Replace tokens
            $messageHeaders = $this->message->getHeaders();
            foreach ($headers as $headerKey => $headerValue) {
                $headerValue = str_ireplace(array_keys($tokens), $tokens, $headerValue);

                if (!$headerValue) {
                    $messageHeaders->remove($headerKey);
                    continue;
                }

                try {
                    if (in_array(strtolower($headerKey), ['from', 'to', 'cc', 'bcc', 'reply-to'])) {
                        // Handling headers that require MailboxListHeader
                        $headerValue = array_map(fn ($address): Address => new Address($address),
                            explode(',', $headerValue));
                    }
                    if ($messageHeaders->has($headerKey)) {
                        $header = $messageHeaders->get($headerKey);
                        $header->setBody($headerValue);
                    } else {
                        $messageHeaders->addHeader($headerKey, $headerValue);
                    }
                } catch (RfcComplianceException) {
                    $messageHeaders->remove($headerKey);
                }
            }
        }

        if (array_key_exists('List-Unsubscribe', $headers)) {
            unset($headers['List-Unsubscribe']);
            $this->setCustomHeaders($headers, false);
        }
    }

    private function buildMetadata($name, array $tokens): array
    {
        return [
            'name'        => $name,
            'leadId'      => (!empty($this->lead)) ? $this->lead['id'] : null,
            'emailId'     => (!empty($this->email)) ? $this->email->getId() : null,
            'emailName'   => (!empty($this->email)) ? $this->email->getName() : null,
            'hashId'      => $this->idHash,
            'hashIdState' => $this->idHashState,
            'source'      => $this->source,
            'tokens'      => $tokens,
            'utmTags'     => (!empty($this->email)) ? $this->email->getUtmTags() : [],
        ];
    }

    /**
     * Validates a given address to ensure RFC 2822, 3.6.2 specs.
     *
     * @deprecated 2.11.0 to be removed in 3.0; use Mautic\EmailBundle\Helper\EmailValidator
     *
     * @throws InvalidEmailException
     */
    public static function validateEmail($address): void
    {
        $invalidChar = strpbrk($address, '\'^&*%');
        if (false !== $invalidChar) {
            throw new InvalidEmailException('Email address ['.$address.'] contains this invalid character: '.substr($invalidChar, 0, 1));
        }
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException('Email address ['.$address.'] is invalid');
        }
    }

    private function setDefaultFrom($overrideFrom, AddressDTO $systemFrom): void
    {
        if (is_array($overrideFrom)) {
            $fromEmail    = key($overrideFrom);
            $fromName     = $this->cleanName($overrideFrom[$fromEmail]);
            $overrideFrom = [$fromEmail => $fromName];
        } elseif (!empty($overrideFrom)) {
            $overrideFrom = [$overrideFrom => null];
        }

        $this->systemFrom = $overrideFrom ?: $systemFrom;
        $this->from       = $this->systemFrom;
    }

    private function setDefaultReplyTo($systemReplyToEmail = null, AddressDTO $systemFromEmail = null): void
    {
        $fromEmail = null;
        if ($systemFromEmail) {
            $fromEmail = $systemFromEmail->getEmail();
        }

        $this->systemReplyTo = $systemReplyToEmail ?: $fromEmail;
        $this->replyTo       = $this->systemReplyTo;
    }

    private function setFromForSingleMessage(): void
    {
        $email = $this->getEmail();

        if ($this->lead && $email && $email->getUseOwnerAsMailer()) {
            if (!isset($this->lead['owner_id'])) {
                $this->lead['owner_id'] = 0;
            }

            $from = $this->fromEmailHelper->getFromAddressConsideringOwner($this->getFrom(), $this->lead, $email);
            $this->setMessageFrom($from);

            return;
        }

        if ($email) {
            $fromEmail = $email->getFromAddress();
            $fromName  = $email->getFromName();
            if (!empty($fromEmail) || !empty($fromName)) {
                if (empty($fromName)) {
                    $fromName = $this->getFrom()->getName();
                } elseif (empty($fromEmail)) {
                    $fromEmail = $this->getFrom()->getEmail();
                }

                $this->from = new AddressDTO($fromEmail, $fromName);
            }
        }

        $from = $this->fromEmailHelper->getFromAddressDto($this->getFrom(), $this->lead, $email);

        $this->setMessageFrom($from);
    }

    private function setReplyToForSingleMessage(?Email $emailToSend): void
    {
        // 1. Set the reply to address from the email "reply-to" setting if set.
        if ($emailToSend && null !== $emailToSend->getReplyToAddress()) {
            $this->setMessageReplyTo($emailToSend->getReplyToAddress());

            return;
        }

        // 2. Set the reply to address from the lead owner if set.
        if (!empty($this->lead['owner_id'])) {
            try {
                $owner = $this->fromEmailHelper->getContactOwner((int) $this->lead['owner_id'], $emailToSend);
                $this->setMessageReplyTo($owner['email']);
            } catch (OwnerNotFoundException) {
                $this->setMessageReplyTo($this->getSystemReplyTo());
            }

            return;
        }

        // 3. Set the reply to address from the email "from" setting if set.
        if ($emailToSend && null !== $emailToSend->getFromAddress() && empty($this->coreParametersHelper->get('mailer_reply_to_email'))) {
            $this->setMessageReplyTo($emailToSend->getFromAddress());

            return;
        }

        // 4. Set the reply to address from the global config if nothing from above is set.
        $this->setMessageReplyTo($this->getReplyTo());
    }

    /**
     * @return bool|array
     *
     * @deprecated
     */
    protected function getContactOwner(&$contact)
    {
        if (!is_array($contact)) {
            return false;
        }

        if (!isset($contact['id'])) {
            return false;
        }

        if (!isset($contact['owner_id'])) {
            $contact['owner_id'] = 0;

            return false;
        }

        try {
            return $this->fromEmailHelper->getContactOwner($contact['owner_id']);
        } catch (OwnerNotFoundException) {
            return false;
        }
    }

    /**
     * @deprecated; use FromEmailHelper::getUserSignature
     */
    protected function getContactOwnerSignature($owner): string
    {
        if (empty($owner['id'])) {
            return '';
        }

        try {
            $this->fromEmailHelper->getContactOwner($owner['id']);
        } catch (OwnerNotFoundException) {
            return '';
        }

        return $this->fromEmailHelper->getSignature();
    }

    private function getMessageInstance(): MauticMessage
    {
        return new MauticMessage();
    }

    private function getReplyTo(): string
    {
        return $this->replyTo ?? $this->getSystemReplyTo();
    }

    private function getSystemReplyTo(): string
    {
        if (!$this->systemReplyTo) {
            $fromEmailAddress    = $this->from ? $this->from->getEmail() : null;
            $this->systemReplyTo = $this->coreParametersHelper->get('mailer_reply_to_email') ?? $fromEmailAddress ?? $this->getSystemFrom()->getEmail();
        }

        return $this->systemReplyTo;
    }

    private function getFrom(): AddressDTO
    {
        return $this->from ?? $this->getSystemFrom();
    }

    private function getSystemFrom(): AddressDTO
    {
        if (!$this->systemFrom || $this->systemFrom->isEmpty()) {
            $this->systemFrom = new AddressDTO($this->coreParametersHelper->get('mailer_from_email'), $this->coreParametersHelper->get('mailer_from_name'));
            $this->fromEmailHelper->setDefaultFrom($this->systemFrom);
        }

        return $this->systemFrom;
    }

    public function dispatchPreSendEvent(): void
    {
        $event = new EmailSendEvent($this);
        $this->dispatcher->dispatch($event, EmailEvents::EMAIL_PRE_SEND);

        $this->skip               = $event->isSkip();
        $this->fatal              = $event->isFatal();
        $errors                   = $event->getErrors();
        if (!empty($errors)) {
            $currentErrors = [];
            if (isset($this->errors['failures']) && is_array($this->errors['failures'])) {
                $currentErrors = $this->errors['failures'];
            }
            $this->errors['failures'] = array_merge($errors, $currentErrors);
        }

        unset($event);
    }
}
