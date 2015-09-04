<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\PageBundle\EventListener;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event\CategoryBundlesEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class CategorySubscriber
 *
 * @package Mautic\PageBundle\EventListener
 */
class CategorySubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD => array('onCategoryBundleListBuild', 0)
        );
    }

    /**
     * Add bundle to the category
     *
     * @param CategoryBundlesEvent $event
     *
     * @return void
     */
    public function onCategoryBundleListBuild(CategoryBundlesEvent $event)
    {
        $event->addBundle('page');
    }
}
