<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle;

/**
 * Class CategoryBundle
 * Events available for CategoryBundle
 *
 * @package Mautic\CategoryBundle
 */
final class CategoryEvents
{
    /**
     * The mautic.category_pre_save event is thrown right before a category is persisted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_PRE_SAVE   = 'mautic.category_pre_save';

    /**
     * The mautic.category_post_save event is thrown right after a category is persisted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_POST_SAVE   = 'mautic.category_post_save';

    /**
     * The mautic.category_pre_delete event is thrown prior to when a category is deleted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_PRE_DELETE   = 'mautic.category_pre_delete';


    /**
     * The mautic.category_post_delete event is thrown after a category is deleted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_POST_DELETE   = 'mautic.category_post_delete';
}