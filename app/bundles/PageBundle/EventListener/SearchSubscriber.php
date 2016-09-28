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
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\PageBundle\Model\PageModel;

/**
 * Class SearchSubscriber
 *
 * @package Mautic\PageBundle\EventListener
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var PageModel
     */
    protected $pageModel;

    /**
     * SearchSubscriber constructor.
     *
     * @param UserHelper $userHelper
     * @param PageModel  $pageModel
     */
    public function __construct(UserHelper $userHelper, PageModel $pageModel)
    {
        $this->userHelper = $userHelper;
        $this->pageModel  = $pageModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            CoreEvents::GLOBAL_SEARCH        => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST   => array('onBuildCommandList', 0)
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
                    'value'  => $this->userHelper->getUser()->getId()
                );
            }

            $pages = $this->pageModel->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $filter
                ));

            if (count($pages) > 0) {
                $pageResults = array();

                foreach ($pages as $page) {
                    $pageResults[] = $this->templating->renderResponse(
                        'MauticPageBundle:SubscribedEvents\Search:global.html.php',
                        array('page' => $page)
                    )->getContent();
                }
                if (count($pages) > 5) {
                    $pageResults[] = $this->templating->renderResponse(
                        'MauticPageBundle:SubscribedEvents\Search:global.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($pages) - 5)
                        )
                    )->getContent();
                }
                $pageResults['count'] = count($pages);
                $event->addResults('mautic.page.pages', $pageResults);
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
                'mautic.page.pages',
                $this->pageModel->getCommandList()
            );
        }
    }
}