<?php

namespace Mautic\EmailBundle\Helper;

use Doctrine\ORM\ORMException;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Form\Type\ConfigType;
use Mautic\EmailBundle\Mailer\Exception\BatchQueueMaxException;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    protected $transport;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var null
     */
    protected $dispatcher;

    /**
     * @var MauticMessage
     */
    public $message;

    /**
     * @var string|array<string, string>
     */
    protected $from;

    protected $systemFrom;

    /**
     * @var string
     */
    protected $replyTo;

    /**
     * @var string
     */
    protected $systemReplyTo;

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

    /**
     * @var null
     */
    protected $idHash;

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
    protected $subject = '';

    /**
     * @var string
     */
    protected $plainText = '';

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
    protected $body = [
        'content'     => '',
        'contentType' => 'text/html',
        'charset'     => null,
    ];

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

    /**
     * Simply a md5 of the content so that event listeners can easily determine if the content has been changed.
     */
    private ?string $contentHash = null;

    private array $copies = [];

    private array $embedImagesReplaces = [];

    public function __construct(
        protected MauticFactory $factory,
        protected MailerInterface $mailer,
        $from = null
    ) {
        $this->transport = $this->getTransport();

        $systemFromEmail    = $factory->getParameter('mailer_from_email');
        $systemReplyToEmail = $factory->getParameter('mailer_reply_to_email');
        $systemFromName     = $this->cleanName(
            $factory->getParameter('mailer_from_name')
        );
        $this->setDefaultFrom($from, [$systemFromEmail => $systemFromName]);
        $this->setDefaultReplyTo($systemReplyToEmail, $this->from);

        $this->returnPath = $factory->getParameter('mailer_return_path');

        // Check if batching is supported by the transport
        if ($this->transport instanceof TokenTransportInterface) {
            $this->tokenizationEnabled = true;
        }

        $this->message = $this->getMessageInstance();
    }

    /**
     * Mirrors previous MauticFactory functionality.
     *
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
     * Mirrors previous MauticFactory functionality.
     *
     * @param bool $cleanSlate
     *
     * @return $this
     */
    public function getSampleMailer($cleanSlate = true)
    {
        return $this->getMailer($cleanSlate);
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
        $ownerSignature = false;
        if (!$isQueueFlush) {
            $emailToSend = $this->getEmail();
            if (!empty($emailToSend)) {
                if ($emailToSend->getUseOwnerAsMailer()) {
                    $owner = $this->getContactOwner($this->lead);
                    if (!empty($owner)) {
                        $this->setFrom($owner['email'], $owner['first_name'].' '.$owner['last_name']);
                        $ownerSignature = $this->getContactOwnerSignature($owner);
                        if (null !== $emailToSend->getReplyToAddress()) {
                            $this->setReplyTo($emailToSend->getReplyToAddress());
                        } else {
                            $this->setReplyTo($owner['email']);
                        }
                    } else {
                        $this->setFrom($this->systemFrom, null);
                        $this->setReplyTo($this->replyTo);
                    }
                } elseif (!empty($emailToSend->getFromAddress())) {
                    $this->setFrom($emailToSend->getFromAddress(), $emailToSend->getFromName());
                } else {
                    $this->setFrom($this->from, null);
                }
            } else {
                $this->setFrom($this->from, null);
            }
        } // from is set in flushQueue

        if (empty($this->message->getReplyTo()) && !empty($this->replyTo)) {
            $this->setReplyTo($this->replyTo);
        }
        // Set system return path if applicable
        if (!$isQueueFlush && ($bounceEmail = $this->generateBounceEmail())) {
            $this->message->returnPath($bounceEmail);
        } elseif (!empty($this->returnPath)) {
            $this->message->returnPath($this->returnPath);
        }

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
                if ($ownerSignature) {
                    $tokens['{signature}'] = $ownerSignature;
                }

                // Set metadata if applicable
                foreach ($this->queuedRecipients as $email => $name) {
                    $this->message->addMetadata($email, $this->buildMetadata($name, $tokens));
                }

                if (!empty($tokens)) {
                    // Replace tokens
                    $search  = array_keys($tokens);
                    $replace = $tokens;

                    self::searchReplaceTokens($search, $replace, $this->message);
                }
            }

            if (true === $this->factory->getParameter('mailer_convert_embed_images')) {
                $this->convertEmbedImages();
            }

            // Attach assets
            /** @var \Mautic\AssetBundle\Entity\Asset $asset */
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
                $this->mailer->send($this->message);
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
                $fromKey = 'default';
                $tokens  = $this->getTokens();

                if ($owner = $this->getContactOwner($this->lead)) {
                    $fromKey = $owner['email'];

                    // Override default signature with owner
                    if ($ownerSignature = $this->getContactOwnerSignature($owner)) {
                        $tokens['{signature}'] = $ownerSignature;
                    }
                }

                if (!isset($this->metadata[$fromKey])) {
                    $this->metadata[$fromKey] = [
                        'from'     => $owner,
                        'contacts' => [],
                    ];
                }

                $this->metadata[$fromKey]['contacts'][$email] = $this->buildMetadata($name, $tokens);
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

            foreach ($this->metadata as $fromKey => $metadatum) {
                // Whatever is in the message "to" should be ignored as we will send to the contacts grouped by from addresses
                // This prevents mailers such as sparkpost from sending duplicates to contacts
                $this->message->to();
                $this->errors = [];

                $email = $this->getEmail();

                if (!empty($email)) {
                    if ($email->getUseOwnerAsMailer() && 'default' !== $fromKey) {
                        $this->setFrom($metadatum['from']['email'], $metadatum['from']['first_name'].' '.$metadatum['from']['last_name']);
                    } elseif (!empty($email->getFromAddress())) {
                        $this->setFrom($email->getFromAddress(), $email->getFromName());
                    } else {
                        $this->setFrom($this->systemFrom, null);
                    }
                } else {
                    $this->setFrom($this->from, null);
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
            $this->from                = $this->systemFrom;
            $this->replyTo             = $this->systemReplyTo;
            $this->headers             = [];
            $this->source              = [];
            $this->assets              = [];
            $this->globalTokens        = [];
            $this->attachedAssets      = [];
            $this->email               = null;
            $this->copies              = [];
            $this->message             = $this->getMessageInstance();
            $this->subject             = '';
            $this->plainText           = '';
            $this->plainTextSet        = false;
            $this->body                = [
                'content'     => '',
                'contentType' => 'text/html',
                'charset'     => null,
            ];
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
        $bodyReplaced = str_ireplace($search, $replace, $body, $updated);
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
        $model = $this->factory->getModel('asset');

        if (!$asset instanceof Asset) {
            $asset = $model->getEntity($asset);

            if (null == $asset) {
                return;
            }
        }

        if ($asset->isPublished()) {
            $asset->setUploadDir($this->factory->getParameter('upload_dir'));
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
        if (null == $this->twig) {
            $this->twig = $this->factory->getTwig();
        }

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
        if (!$ignoreTrackingPixel && $this->factory->getParameter('mailer_append_tracking_pixel')) {
            // Append tracking pixel
            $trackingImg = '<img height="1" width="1" src="{tracking_pixel}" alt="" />';
            if (str_contains($content, '</body>')) {
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
                if (str_starts_with($match, $this->factory->getParameter('site_url'))) {
                    $path = str_replace($this->factory->getParameter('site_url'), '', $match);
                    $path = $this->factory->getSystemPath('root', true).$path;
                }

                if ($file_content = file_get_contents($path)) {
                    $this->message->embed($file_content, md5($match));
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
     *
     * @return bool
     */
    public function setTo($addresses, $name = null)
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
     *
     * @return bool
     */
    public function addTo($address, $name = null)
    {
        $this->checkBatchMaxRecipients();

        try {
            $name = $this->cleanName($name);
            $this->message->addTo(new Address($address, $name ?? ''));
            $this->queuedRecipients[$address] = $name;

            return true;
        } catch (\Exception $e) {
            $this->logError($e, 'to');

            return false;
        }
    }

    /**
     * Set CC address(es).
     *
     * @param mixed  $addresses
     * @param string $name
     *
     * //TODO: there is a bug here, the name is not passed in CC nor in the array of addresses, we do not handle names for CC
     *
     * @return bool
     */
    public function setCc($addresses, $name = null)
    {
        $this->checkBatchMaxRecipients(count($addresses), 'cc');

        try {
            $name        = $this->cleanName($name);
            $ccAddresses = [];
            // The email addresses are stored in the array keys not the values
            // The name of the CC is passed in the function and not in the array
            foreach ($addresses as $address => $noName) {
                $ccAddresses[] = new Address($address, $name ?? '');
            }
            $this->message->cc(...$ccAddresses);

            return true;
        } catch (\Exception $e) {
            $this->logError($e, 'cc');

            return false;
        }
    }

    /**
     * Add cc address.
     *
     * @param mixed $address
     *
     * @return bool
     */
    public function addCc($address, $name = null)
    {
        $this->checkBatchMaxRecipients(1, 'cc');

        try {
            $name = $this->cleanName($name);
            $this->message->addCc(new Address($address, $name ?? ''));

            return true;
        } catch (\Exception $e) {
            $this->logError($e, 'cc');

            return false;
        }
    }

    /**
     * Set BCC address(es).
     *
     * @param mixed  $addresses
     * @param string $name
     *
     * //TODO: same bug for the name as the one we have in setCc
     *
     * @return bool
     */
    public function setBcc($addresses, $name = null)
    {
        $this->checkBatchMaxRecipients(count($addresses), 'bcc');

        try {
            $name         = $this->cleanName($name);
            $bccAddresses = [];
            // The email addresses are stored in the array keys not the values
            // The name of the Bcc is passed in the function and not in the array
            foreach ($addresses as $address => $noName) {
                $bccAddresses[] = new Address($address, $name ?? '');
            }

            $this->message->bcc(...$bccAddresses);

            return true;
        } catch (\Exception $e) {
            $this->logError($e, 'bcc');

            return false;
        }
    }

    /**
     * Add bcc address.
     *
     * @param string $address
     *
     * @return bool
     */
    public function addBcc($address, $name = null)
    {
        $this->checkBatchMaxRecipients(1, 'bcc');

        try {
            $name = $this->cleanName($name);
            $this->message->addBcc(new Address($address, $name ?? ''));

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
        if ($this->queueEnabled) {
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
     * Set reply to address(es).
     *
     * @param array<string>|string $addresses
     * @param string               $name
     */
    public function setReplyTo($addresses, $name = null): void
    {
        try {
            $name      = $this->cleanName($name);
            $addresses = (array) $addresses; // This will cast $addresses to an array
            foreach ($addresses as $address) {
                $this->message->replyTo(new Address($address, $name ?? ''));
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
     * Set from email address and name (defaults to determining automatically unless isGlobal is true).
     *
     * @param string|array $fromEmail
     * @param string       $fromName
     */
    public function setFrom($fromEmail, $fromName = null): void
    {
        $address = null;

        if (is_array($fromEmail)) {
            $fromName   = $this->cleanName($fromEmail[key($fromEmail)]);
            $address    = new Address(key($fromEmail), $fromName ?? '');
            $this->from = $fromEmail;
        } else {
            $fromName   = $this->cleanName($fromName);
            $address    = new Address($fromEmail, $fromName ?? '');
            $this->from = [$fromEmail => $fromName];
        }

        try {
            $this->message->from($address);
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
     * @param array $slots               Slots configured in theme
     * @param array $assetAttachments    Assets to send
     * @param bool  $ignoreTrackingPixel Do not append tracking pixel HTML
     *
     * @return bool Returns false if there were errors with the email configuration
     */
    public function setEmail(Email $email, $allowBcc = true, $slots = [], $assetAttachments = [], $ignoreTrackingPixel = false): bool
    {
        if ($this->factory->getParameter(ConfigType::MINIFY_EMAIL_HTML)) {
            $email->setCustomHtml(InputHelper::minifyHTML($email->getCustomHtml()));
        }

        $this->email = $email;

        $subject = $email->getSubject();

        // Convert short codes to emoji
        $subject = EmojiHelper::toEmoji($subject, 'short');

        // Set message settings from the email
        $this->setSubject($subject);

        $fromEmail = $email->getFromAddress();
        $fromName  = $email->getFromName();
        if (!empty($fromEmail) || !empty($fromName)) {
            if (empty($fromName)) {
                $fromName = array_values($this->from)[0];
            } elseif (empty($fromEmail)) {
                $fromEmail = key($this->from);
            }

            $this->setFrom($fromEmail, $fromName);
            $this->from = [$fromEmail => $fromName];
        } else {
            $this->from = $this->systemFrom;
        }

        $this->replyTo = $email->getReplyToAddress();
        if (empty($this->replyTo)) {
            if (!empty($fromEmail) && empty($this->factory->getParameter('mailer_reply_to_email'))) {
                $this->replyTo = $fromEmail;
            } else {
                $this->replyTo = $this->systemReplyTo;
            }
        }
        if (!empty($this->replyTo)) {
            $addresses = explode(',', $this->replyTo);

            // Only a single email is supported
            $this->setReplyTo($addresses[0]);
        }

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
            if (empty($slots)) {
                $slots    = $this->factory->getTheme($template)->getSlots('email');
            }

            if (isset($slots[$template])) {
                $slots = $slots[$template];
            }

            $this->processSlots($slots, $email);

            $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate('@themes/'.$template.'/html/email.html.twig');

            $customHtml = $this->setTemplate($logicalName, [
                'slots'    => $slots,
                'content'  => $email->getContent(),
                'email'    => $email,
                'template' => $template,
            ], true);
        }

        // Convert short codes to emoji
        $customHtml = EmojiHelper::toEmoji($customHtml, 'short');

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
                    $headers['List-Unsubscribe'] .= ','.$listUnsubscribeHeader;
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
            $url = $this->factory->getRouter()->generate('mautic_email_unsubscribe', ['idHash' => $this->idHash], UrlGeneratorInterface::ABSOLUTE_URL);

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
            $tokens['{tracking_pixel}'] = $this->factory->getRouter()->generate(
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

        $request = $this->factory->getRequest();
        $parser  = new PlainTextHelper([
            'base_url' => $request->getSchemeAndHttpHost().$request->getBasePath(),
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
        if (null == $this->dispatcher) {
            $this->dispatcher = $this->factory->getDispatcher();
        }

        $event = new EmailSendEvent($this);

        $this->dispatcher->dispatch($event, EmailEvents::EMAIL_ON_SEND);

        $this->eventTokens = array_merge($this->eventTokens, $event->getTokens(false));

        unset($event);
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

        $this->factory->getLogger()->log('error', '[MAIL ERROR] '.$error, $exceptionContext);
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
     * Return transport.
     *
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
            /** @var \Mautic\AssetBundle\Model\AssetModel $assetModel */
            $assetModel = $this->factory->getModel('asset');
            foreach ($this->assets as $asset) {
                foreach ($this->assetStats as $stat) {
                    $assetModel->trackDownload(
                        $asset,
                        null,
                        200,
                        $stat
                    );
                }

                $assetModel->upDownloadCount($asset, count($this->assetStats), true);
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
            /** @var \Mautic\PageBundle\Model\TrackableModel $trackableModel */
            $trackableModel = $this->factory->getModel('page.trackable');
            $trackable      = $trackableModel->getTrackableByUrl($url, 'email', $this->email->getId());

            return $trackable->getRedirect();
        }

        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

        return $redirectModel->getRedirectByUrl($url);
    }

    /**
     * Create an email stat.
     *
     * @param bool|true   $persist
     * @param string|null $emailAddress
     */
    public function createEmailStat($persist = true, $emailAddress = null, $listId = null): Stat
    {
        // create a stat
        $stat = new Stat();
        $stat->setDateSent(new \DateTime());
        $stat->setEmail($this->email);

        // Note if a lead
        if (null !== $this->lead) {
            try {
                $stat->setLead($this->factory->getEntityManager()->getReference(\Mautic\LeadBundle\Entity\Lead::class, $this->lead['id']));
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
                $stat->setList($this->factory->getEntityManager()->getReference(\Mautic\LeadBundle\Entity\LeadList::class, $listId));
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

        /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
        $emailModel = $this->factory->getModel('email');

        // Save a copy of the email - use email ID if available simply to prevent from having to rehash over and over
        $id = (null !== $this->email) ? $this->email->getId() : md5($this->subject.$this->body['content']);
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
                $stat->setStoredCopy($this->factory->getEntityManager()->getReference(\Mautic\EmailBundle\Entity\Copy::class, $this->copies[$id]));
            } catch (ORMException) {
                // keep IDE happy
            }
        }

        if ($persist) {
            $emailModel->getStatRepository()->saveEntity($stat);
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
        /** @var \Mautic\EmailBundle\MonitoredEmail\Mailbox $mailboxHelper */
        $mailboxHelper = $this->factory->getHelper('mailbox');

        if ($mailboxHelper->isConfigured($bundleKey, $folderKey)) {
            return $mailboxHelper->getMailboxSettings();
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
     * @param Email $entity
     */
    public function processSlots($slots, $entity): void
    {
        /** @var \Mautic\CoreBundle\Twig\Helper\SlotsHelper $slotsHelper */
        $slotsHelper = $this->factory->getHelper('template.slots');

        $content = $entity->getContent();

        foreach ($slots as $slot => $slotConfig) {
            if (is_numeric($slot)) {
                $slot       = $slotConfig;
                $slotConfig = [];
            }

            $value = $content[$slot] ?? '';
            $slotsHelper->set($slot, $value);
        }
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
     * @return bool|array
     */
    protected function getContactOwner(&$contact)
    {
        $owner = false;
        $email = $this->getEmail();

        if (!empty($email)) {
            if ($email->getUseOwnerAsMailer() && is_array($contact) && isset($contact['id'])) {
                if (!isset($contact['owner_id'])) {
                    $contact['owner_id'] = 0;
                } elseif (isset($contact['owner_id'])) {
                    $leadModel = $this->factory->getModel('lead');
                    \assert($leadModel instanceof LeadModel);
                    if (isset(self::$leadOwners[$contact['owner_id']])) {
                        $owner = self::$leadOwners[$contact['owner_id']];
                    } elseif ($owner = $leadModel->getRepository()->getLeadOwner($contact['owner_id'])) {
                        self::$leadOwners[$owner['id']] = $owner;
                    }
                }
            }
        }

        return $owner;
    }

    /**
     * @return mixed
     */
    protected function getContactOwnerSignature($owner)
    {
        return empty($owner['signature'])
            ? false
            : EmojiHelper::toHtml(
                str_replace('|FROM_NAME|', $owner['first_name'].' '.$owner['last_name'], nl2br($owner['signature']))
            );
    }

    /**
     * @return array
     */
    private function getSystemHeaders()
    {
        /**
         * This section is stopped, because it is preventing global headers from being merged
         *         if ($this->email) {
         *           // We are purposively ignoring system headers if using an Email entity
         *           return [];
         *      }.
         */
        if (!$systemHeaders = $this->factory->getParameter('mailer_custom_headers', [])) {
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
            $messageHeaders = $this->message->getHeaders();
            foreach ($headers as $headerKey => $headerValue) {
                if ($messageHeaders->has($headerKey)) {
                    $header = $messageHeaders->get($headerKey);
                    $header->setBody($headerValue);
                } else {
                    $messageHeaders->addTextHeader($headerKey, $headerValue);
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

    private function setDefaultFrom($overrideFrom, array $systemFrom): void
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

    private function setDefaultReplyTo($systemReplyToEmail = null, $systemFromEmail = null): void
    {
        $fromEmail = null;
        if (is_array($systemFromEmail)) {
            $fromEmail = key($systemFromEmail);
        } elseif (!empty($systemFromEmail)) {
            $fromEmail = $systemFromEmail;
        }

        $this->systemReplyTo = $systemReplyToEmail ?: $fromEmail;
        $this->replyTo       = $this->systemReplyTo;
    }

    private function getMessageInstance(): MauticMessage
    {
        return new MauticMessage();
    }
}
