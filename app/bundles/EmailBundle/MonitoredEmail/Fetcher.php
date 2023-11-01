<?php

namespace Mautic\EmailBundle\MonitoredEmail;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor;
use Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Fetcher
{
    /**
     * @var Mailbox
     */
    private $imapHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $mailboxes;

    /**
     * @var array
     */
    private $log = [];

    /**
     * @var int
     */
    private $processedMessageCounter = 0;

    /**
     * Fetcher constructor.
     */
    public function __construct(Mailbox $imapHelper, EventDispatcherInterface $dispatcher, TranslatorInterface $translator)
    {
        $this->imapHelper = $imapHelper;
        $this->dispatcher = $dispatcher;
        $this->translator = $translator;
    }

    /**
     * @return $this
     */
    public function setMailboxes(array $mailboxes)
    {
        $this->mailboxes = $mailboxes;

        return $this;
    }

    /**
     * @param int $limit
     */
    public function fetch($limit = null)
    {
        /** @var ParseEmailEvent $event */
        $event = $this->dispatcher->dispatch(EmailEvents::EMAIL_PRE_FETCH, new ParseEmailEvent());

        // Get a list of criteria and group by it
        $organizer = new MailboxOrganizer($event, $this->getConfigs());
        $organizer->organize();

        if (!$containers = $organizer->getContainers()) {
            $this->log[] = $this->translator->trans('mautic.email.fetch.no_mailboxes_configured');

            return;
        }

        foreach ($containers as $container) {
            $path       = $container->getPath();
            $markAsSeen = $container->shouldMarkAsSeen();

            foreach ($container->getCriteria() as $criteria => $mailboxes) {
                try {
                    // Get mail and parse into Message objects
                    $this->imapHelper->switchMailbox($mailboxes[0]);

                    $mailIds   = $this->imapHelper->searchMailBox($criteria);
                    $messages  = $this->getMessages($mailIds, $limit, $markAsSeen);
                    $processed = count($messages);

                    if ($messages) {
                        $event->setMessages($messages)
                            ->setKeys($mailboxes);
                        $this->dispatcher->dispatch(EmailEvents::EMAIL_PARSE, $event);
                    }

                    $this->log[] = $this->translator->trans(
                        'mautic.email.fetch.processed',
                        ['%count%' => $processed, '%imapPath%' => $path, '%criteria%' => $criteria]
                    );

                    if ($limit && $this->processedMessageCounter >= $limit) {
                        break;
                    }
                } catch (\Exception $e) {
                    $this->log[] = $e->getMessage();
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param int  $limit
     * @param bool $markAsSeen
     *
     * @return array
     */
    private function getMessages(array $mailIds, $limit, $markAsSeen)
    {
        if (!count($mailIds)) {
            return [];
        }

        $messages = [];
        foreach ($mailIds as $id) {
            $messages[] = $this->imapHelper->getMail($id, $markAsSeen);
            ++$this->processedMessageCounter;

            if ($limit && $this->processedMessageCounter >= $limit) {
                break;
            }
        }

        return $messages;
    }

    /**
     * @return array
     */
    private function getConfigs()
    {
        $mailboxes = [];
        foreach ($this->mailboxes as $mailbox) {
            $mailboxes[$mailbox] = new ConfigAccessor($this->imapHelper->getMailboxSettings($mailbox));
        }

        return $mailboxes;
    }
}
