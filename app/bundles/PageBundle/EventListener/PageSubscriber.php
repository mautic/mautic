<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\Event\PageEditSubmitEvent;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\Model\PageDraftModel;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\PageEvents;
use Mautic\QueueBundle\Event\QueueConsumerEvent;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\QueueEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageSubscriber implements EventSubscriberInterface
{
    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var PageModel
     */
    private $pageModel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HitRepository
     */
    private $hitRepository;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var RedirectRepository
     */
    private $redirectRepository;

    /**
     * @var LeadRepository
     */
    private $contactRepository;

    /**
     * @var PageDraftModel
     */
    private $pageDraftModel;

    public function __construct(
        AssetsHelper $assetsHelper,
        IpLookupHelper $ipLookupHelper,
        AuditLogModel $auditLogModel,
        PageModel $pageModel,
        LoggerInterface $logger,
        HitRepository $hitRepository,
        PageRepository $pageRepository,
        RedirectRepository $redirectRepository,
        LeadRepository $contactRepository,
        PageDraftModel $pageDraftModel
    ) {
        $this->assetsHelper       = $assetsHelper;
        $this->ipLookupHelper     = $ipLookupHelper;
        $this->auditLogModel      = $auditLogModel;
        $this->pageModel          = $pageModel;
        $this->logger             = $logger;
        $this->hitRepository      = $hitRepository;
        $this->pageRepository     = $pageRepository;
        $this->redirectRepository = $redirectRepository;
        $this->contactRepository  = $contactRepository;
        $this->pageDraftModel     = $pageDraftModel;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::PAGE_POST_SAVE      => ['onPagePostSave', 0],
            PageEvents::PAGE_POST_DELETE    => ['onPageDelete', 0],
            PageEvents::PAGE_ON_DISPLAY     => ['onPageDisplay', -255], // We want this to run last
            QueueEvents::PAGE_HIT           => ['onPageHit', 0],
            PageEvents::ON_PAGE_EDIT_SUBMIT => ['managePageDraft'],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onPagePostSave(PageEvent $event)
    {
        $page = $event->getPage();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'page',
                'object'    => 'page',
                'objectId'  => $page->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onPageDelete(PageEvent $event)
    {
        $page = $event->getPage();
        $log  = [
            'bundle'    => 'page',
            'object'    => 'page',
            'objectId'  => $page->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $page->getTitle()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Allow event listeners to add scripts to
     * - </head> : onPageDisplay_headClose
     * - <body>  : onPageDisplay_bodyOpen
     * - </body> : onPageDisplay_bodyClose.
     */
    public function onPageDisplay(Events\PageDisplayEvent $event)
    {
        $content = $event->getContent();

        // Get scripts to insert before </head>
        ob_start();
        $this->assetsHelper->outputScripts('onPageDisplay_headClose');
        $headCloseScripts = ob_get_clean();

        if ($headCloseScripts) {
            $content = str_ireplace('</head>', $headCloseScripts."\n</head>", $content);
        }

        // Get scripts to insert after <body>
        ob_start();
        $this->assetsHelper->outputScripts('onPageDisplay_bodyOpen');
        $bodyOpenScripts = ob_get_clean();

        if ($bodyOpenScripts) {
            preg_match('/(<body[a-z=\s\-_:"\']*>)/i', $content, $matches);

            $content = str_ireplace($matches[0], $matches[0]."\n".$bodyOpenScripts, $content);
        }

        // Get scripts to insert before </body>
        ob_start();
        $this->assetsHelper->outputScripts('onPageDisplay_bodyClose');
        $bodyCloseScripts = ob_get_clean();

        if ($bodyCloseScripts) {
            $content = str_ireplace('</body>', $bodyCloseScripts."\n</body>", $content);
        }

        // Get scripts to insert before a custom tag
        $params = $event->getParams();
        if (count($params) > 0) {
            if (isset($params['custom_tag']) && $customTag = $params['custom_tag']) {
                ob_start();
                $this->assetsHelper->outputScripts('customTag');
                $bodyCustomTag = ob_get_clean();

                if ($bodyCustomTag) {
                    $content = str_ireplace($customTag, $bodyCustomTag."\n".$customTag, $content);
                }
            }
        }

        $event->setContent($content);
    }

    public function onPageHit(QueueConsumerEvent $event)
    {
        $payload                = $event->getPayload();
        $request                = $payload['request'];
        $trackingNewlyGenerated = $payload['isNew'];
        $hitId                  = $payload['hitId'];
        $pageId                 = $payload['pageId'];
        $leadId                 = $payload['leadId'];
        $isRedirect             = !empty($payload['isRedirect']);
        $hit                    = $hitId ? $this->hitRepository->find((int) $hitId) : null;
        $lead                   = $leadId ? $this->contactRepository->find((int) $leadId) : null;

        // On the off chance that the queue contains a message which does not
        // reference a valid Hit or Lead, discard it to avoid clogging the queue.
        if (null === $hit || null === $lead) {
            $event->setResult(QueueConsumerResults::REJECT);

            // Log the rejection with event payload as context.
            if ($this->logger) {
                $this->logger->notice(
                    'QUEUE MESSAGE REJECTED: Lead or Hit not found',
                    $payload
                );
            }

            return;
        }

        if ($isRedirect) {
            $page = $pageId ? $this->redirectRepository->find((int) $pageId) : null;
        } else {
            $page = $pageId ? $this->pageRepository->find((int) $pageId) : null;
        }

        // Also reject messages when processing causes any other exception.
        try {
            $this->pageModel->processPageHit($hit, $page, $request, $lead, $trackingNewlyGenerated, false);
            $event->setResult(QueueConsumerResults::ACKNOWLEDGE);
        } catch (\Exception $e) {
            $event->setResult(QueueConsumerResults::REJECT);

            // Log the exception with event payload as context.
            if ($this->logger) {
                $this->logger->error(
                    'QUEUE CONSUMER ERROR ('.QueueEvents::PAGE_HIT.'): '.$e->getMessage(),
                    $payload
                );
            }
        }
    }

    public function managePageDraft(PageEditSubmitEvent $event): void
    {
        $livePage   = $event->getPreviousPage();
        $editedPage = $event->getCurrentPage();

        if (
            ($event->isSaveAndClose() || $event->isApply())
            && $editedPage->hasDraft()
        ) {
            $pageDraft = $editedPage->getDraft();
            $pageDraft->setHtml($editedPage->getCustomHtml());
            $pageDraft->setTemplate($editedPage->getTemplate());
            $editedPage->setCustomHtml($livePage->getCustomHtml());
            $editedPage->setTemplate($livePage->getTemplate());
            $this->pageDraftModel->saveDraft($pageDraft);
            $this->pageModel->saveEntity($editedPage);
        }

        if ($event->isSaveAsDraft()) {
            $pageDraft = $this
                ->pageDraftModel
                ->createDraft($editedPage, $editedPage->getCustomHtml(), $editedPage->getTemplate());

            $editedPage->setCustomHtml($livePage->getCustomHtml());
            $editedPage->setTemplate($livePage->getTemplate());
            $editedPage->setDraft($pageDraft);
            $this->pageModel->saveEntity($editedPage);
        }

        if ($event->isDiscardDraft()) {
            $this->revertPageModifications($livePage, $editedPage);
            $this->pageDraftModel->deleteDraft($editedPage);
            $editedPage->setDraft(null);
            $this->pageModel->saveEntity($editedPage);
        }

        if ($event->isApplyDraft()) {
            $this->pageDraftModel->deleteDraft($editedPage);
            $editedPage->setDraft(null);
        }
    }

    public function deletePageDraft(PageEvent $event): void
    {
        try {
            $this->pageDraftModel->deleteDraft($event->getPage());
        } catch (NotFoundHttpException $exception) {
            // No associated draft found for deletion. We have nothing to do here. Return.
            return;
        }
    }

    private function revertPageModifications(Page $livePage, Page $editedPage): void
    {
        $livePageReflection   = new \ReflectionObject($livePage);
        $editedPageReflection = new \ReflectionObject($editedPage);
        foreach ($livePageReflection->getProperties() as $property) {
            if ('id' == $property->getName()) {
                continue;
            }

            $property->setAccessible(true);
            $name                = $property->getName();
            $value               = $property->getValue($livePage);
            $editedPageProperty  = $editedPageReflection->getProperty($name);
            $editedPageProperty->setAccessible(true);
            $editedPageProperty->setValue($editedPage, $value);
        }
    }
}
