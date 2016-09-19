<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\Model\ClientModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;

/**
 * Class SearchSubscriber
 *
 * @package Mautic\ApiBundle\EventListener
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var ClientModel
     */
    protected $apiClientModel;

    /**
     * SearchSubscriber constructor.
     *
     * @param ClientModel $apiClientModel
     */
    public function __construct(ClientModel $apiClientModel)
    {
        $this->apiClientModel = $apiClientModel;
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
        if ($this->security->isGranted('api:clients:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $clients = $this->apiClientModel->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));

            if (count($clients) > 0) {
                $clientResults = array();
                $canEdit     = $this->security->isGranted('api:clients:edit');
                foreach ($clients as $client) {
                    $clientResults[] = $this->templating->renderResponse(
                        'MauticApiBundle:SubscribedEvents\Search:global.html.php',
                        array(
                            'client'  => $client,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if (count($clients) > 5) {
                    $clientResults[] = $this->templating->renderResponse(
                        'MauticApiBundle:SubscribedEvents\Search:global.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($clients) - 5)
                        )
                    )->getContent();
                }
                $clientResults['count'] = count($clients);
                $event->addResults('mautic.api.client.menu.index', $clientResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        $security   = $this->security;
        if ($security->isGranted('api:clients:view')) {
            $event->addCommands(
                'mautic.api.client.header.index',
                $this->apiClientModel->getCommandList()
            );
        }
    }
}