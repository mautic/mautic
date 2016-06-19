<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;

/**
 * Class PageSubscriber
 */
class PageSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            PageEvents::PAGE_POST_SAVE       => array('onPagePostSave', 0),
            PageEvents::PAGE_POST_DELETE     => array('onPageDelete', 0),
            PageEvents::PAGE_ON_DISPLAY      => array('onPageDisplay', -255) // We want this to run last
        );
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\PageEvent $event
     */
    public function onPagePostSave(Events\PageEvent $event)
    {
        $page = $event->getPage();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "page",
                "object"    => "page",
                "objectId"  => $page->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->factory->getIpAddressFromRequest()
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\PageEvent $event
     */
    public function onPageDelete(Events\PageEvent $event)
    {
        $page = $event->getPage();
        $log = array(
            "bundle"     => "page",
            "object"     => "page",
            "objectId"   => $page->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $page->getTitle()),
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }

    /**
     * Allow event listeners to add scripts to
     * - </head> : onPageDisplay_headClose
     * - <body>  : onPageDisplay_bodyOpen
     * - </body> : onPageDisplay_bodyClose
     *
     * @param Events\PageDisplayEvent $event
     */
    public function onPageDisplay(Events\PageDisplayEvent $event)
    {
        $content = $event->getContent();

        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->factory->getHelper('template.assets');

        // Get scripts to insert before </head>
        ob_start();
        $assetsHelper->outputScripts('onPageDisplay_headClose');
        $headCloseScripts = ob_get_clean();

        if ($headCloseScripts) {
            $content = str_ireplace('</head>', $headCloseScripts . "\n</head>", $content);
        }

        // Get scripts to insert after <body>
        ob_start();
        $assetsHelper->outputScripts('onPageDisplay_bodyOpen');
        $bodyOpenScripts = ob_get_clean();

        if ($bodyOpenScripts) {
            preg_match('/(<body[a-z=\s\-_:"\']*>)/i', $content, $matches);

            $content = str_ireplace($matches[0], $matches[0] . "\n" . $bodyOpenScripts, $content);
        }

        // Get scripts to insert before </body>
        ob_start();
        $assetsHelper->outputScripts('onPageDisplay_bodyClose');
        $bodyCloseScripts = ob_get_clean();

        if ($bodyCloseScripts) {
            $content = str_ireplace('</body>', $bodyCloseScripts . "\n</body>", $content);
        }

        $event->setContent($content);
    }
}
