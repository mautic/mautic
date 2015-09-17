<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\EventListener;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class CategorySubscriber
 *
 * @package Mautic\EmailBundle\EventListener
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
     * @param CategoryTypesEvent $event
     *
     * @return void
     */
    public function onCategoryBundleListBuild(CategoryTypesEvent $event)
    {
        $event->addCategoryType('email');
    }
}
