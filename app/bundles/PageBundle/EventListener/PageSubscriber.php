<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\Event\PageEditSubmitEvent;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\Model\PageDraftModel;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AssetsHelper $assetsHelper,
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private LanguageHelper $languageHelper,
        private PageModel $pageModel,
        private PageDraftModel $pageDraftModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::PAGE_POST_SAVE      => ['onPagePostSave', 0],
            PageEvents::PAGE_POST_DELETE    => ['onPageDelete', 0],
            PageEvents::PAGE_ON_DISPLAY     => ['onPageDisplay', -255], // We want this to run last
            PageEditSubmitEvent::class      => ['managePageDraft'],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onPagePostSave(PageEvent $event): void
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
        if (!array_key_exists($page->getLanguage(), $this->languageHelper->getSupportedLanguages())) {
            $this->languageHelper->extractLanguagePackage($page->getLanguage());
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onPageDelete(PageEvent $event): void
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
    public function onPageDisplay(Events\PageDisplayEvent $event): void
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
            preg_match('/(<body[^>]*>)/i', $content, $matches);

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
        } catch (NotFoundHttpException) {
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
