<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Webmecanik
 * @link        http://webmecanik.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'services' => array(
        'other' => array(
            'mautic.helper.feed' => array(
                'class' => 'Mautic\FeedBundle\Helper\FeedHelper',
                'methodCalls' => array(
                    'setFactory' => array('debril.parser.factory'),
                    'setXmlParser' => array('debril.parser.xml'),
                    'setReader' => array('debril.reader')
                )
            )
        )
    )
);
