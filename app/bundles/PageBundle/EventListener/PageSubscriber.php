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
use Mautic\CoreBundle\CoreEvents;
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
            CoreEvents::GLOBAL_SEARCH        => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST   => array('onBuildCommandList', 0),
            PageEvents::PAGE_POST_SAVE       => array('onPagePostSave', 0),
            PageEvents::PAGE_POST_DELETE     => array('onPageDelete', 0)
        );
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $filter     = array("string" => $str, "force" => array());

        $permissions = $this->security->isGranted(
            array('page:pages:viewown', 'page:pages:viewother'),
            'RETURN_ARRAY'
        );
        if ($permissions['page:pages:viewown'] || $permissions['page:pages:viewother']) {
            if (!$permissions['page:pages:viewother']) {
                $filter['force'][] = array(
                    'column' => 'IDENTITY(p.createdBy)',
                    'expr'   => 'eq',
                    'value'  => $this->factory->getUser()->getId()
                );
            }

            $pages = $this->factory->getModel('page.page')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $filter
                ));

            if (count($pages) > 0) {
                $pageResults = array();

                foreach ($pages as $page) {
                    $pageResults[] = $this->templating->renderResponse(
                        'MauticPageBundle:Search:page.html.php',
                        array('page' => $page)
                    )->getContent();
                }
                if (count($pages) > 5) {
                    $pageResults[] = $this->templating->renderResponse(
                        'MauticPageBundle:Search:page.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($pages) - 5)
                        )
                    )->getContent();
                }
                $pageResults['count'] = count($pages);
                $event->addResults('mautic.page.header.index', $pageResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(array('page:pages:viewown', 'page:pages:viewother'), "MATCH_ONE")) {
            $event->addCommands(
                'mautic.page.header.index',
                $this->factory->getModel('page.page')->getCommandList()
            );
        }
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
}
