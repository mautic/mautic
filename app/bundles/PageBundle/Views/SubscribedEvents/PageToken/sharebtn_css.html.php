<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$css = <<<CSS
.share-buttons { display: block; }
.share-button { float: left; margin-right: 5px; }
.share-button.facebook-share-button.layout-box_count.action-like  iframe { width: 50px !important; }
.share-button.facebook-share-button.layout-box_count { margin-right: 10px !important; }
.share-button.twitter-share-button.layout-horizontal { width: 75px !important; }
CSS;

$view['assets']->addStyleDeclaration($css);
