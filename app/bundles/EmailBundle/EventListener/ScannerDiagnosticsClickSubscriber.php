<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\EmailBundle\Diagnostics\ScannerDiagnosticsRecorder;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\PageBundle\Event\RedirectClickEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Records a click on a link of an email for email scanner diagnostics. It only looks at the click: the click is
 * handled as it always was.
 */
final readonly class ScannerDiagnosticsClickSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ScannerDiagnosticsRecorder $recorder,
        private StatRepository $statRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RedirectClickEvent::class => 'onRedirectClick',
        ];
    }

    public function onRedirectClick(RedirectClickEvent $event): void
    {
        if (!$this->recorder->isEnabled()) {
            return;
        }

        try {
            $stat = $this->findStat($event->getRequest());

            if ($stat instanceof Stat) {
                $this->recorder->record(ScannerDiagnostic::EVENT_CLICK, $event->getRequest(), $stat, $event->getRedirect());
            }
        } catch (\Throwable $exception) {
            // The visitor is on the way to the link, and that mustn't depend on diagnostics
            $this->logger->warning('Email scanner diagnostics could not record a click: '.$exception->getMessage(), ['exception' => $exception]);
        }
    }

    /**
     * The send a click belongs to. A click from an email carries both the email and the send, the same as the click hit
     * expects. A click on a link of a text message or without a clickthrough doesn't belong to an email send.
     */
    private function findStat(Request $request): ?Stat
    {
        $clickthrough = $request->query->all()['ct'] ?? null;

        if (!is_string($clickthrough) || '' === $clickthrough) {
            return null;
        }

        try {
            $data = ClickthroughHelper::decodeArrayFromUrl($clickthrough);
        } catch (InvalidDecodedStringException) {
            return null;
        }

        if (!array_key_exists('email', $data) || !is_string($data['stat'] ?? null) || '' === $data['stat']) {
            return null;
        }

        $stat = $this->statRepository->getEmailStatus($data['stat']);

        return $stat instanceof Stat ? $stat : null;
    }
}
