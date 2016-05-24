<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Webmecanik
 * @link        http://webmecanik.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes' => array(
        'main' => array(
            'mautic_feed_index' => array(
                'path' => '/feeds',
                'controller' => 'MauticFeedBundle:Feed:index'
            )
        )
    ),
    'menu' => array(
        'main' => array(
            'priority' => 35,
            'items' => array(
                'mautic.feed.menu.index' => array(
                    'route' => 'mautic_feed_index',
                    'iconClass' => 'fa-bullhorn'
                )
            )
        )
    ),
    'services' => array(
        'other' => array(
            'mautic.helper.feed' => array(
                'class' => 'Mautic\FeedBundle\Helper\FeedHelper',
//                 'argument' => array(
//                     'debril.http.curl',
//                     'debril.parser.factory',
//                     'debril.parser.xml'
//                 )
                'methodCalls' => array(
                    'setFactory' => array('debril.parser.factory'),
                    'setXmlParser' => array('debril.parser.xml'),
                    'setReader' => array('debril.reader')
                )
            )
        )
    )
);
