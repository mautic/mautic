<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;

/**
 * Class SearchSubscriber
 *
 * @package MauticAddon\MauticChatBundle\EventListener
 */
class SearchSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            CoreEvents::GLOBAL_SEARCH        => array('onGlobalSearch', 0)
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

        /** @var \MauticAddon\MauticChatBundle\Model\ChatModel $model */
        $model = $this->factory->getModel('addon.mauticChat.chat');

        $searchMessages = $model->searchMessages($str);

        if ($searchMessages['count'] > 0) {
            $results = array();
            foreach ($searchMessages['messages'] as $message) {
                $results[] = $this->templating->renderResponse(
                    'MauticChatBundle:SubscribedEvents\Search:global.html.php',
                    array('chat'  => $message)
                )->getContent();
            }
            if ($searchMessages['total'] > 30) {
                $results[] = $this->templating->renderResponse(
                    'MauticChatBundle:SubscribedEvents\Search:global.html.php',
                    array(
                        'showMore'     => true,
                        'searchString' => $str,
                        'remaining'    => $searchMessages['total'] - $searchMessages['count']
                    )
                )->getContent();
            }
            $results['count'] = $searchMessages['total'];
            $event->addResults('mautic.chat.header.index', $results);
        }
    }
}