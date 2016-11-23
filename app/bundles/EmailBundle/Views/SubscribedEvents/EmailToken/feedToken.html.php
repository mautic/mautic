<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\CoreBundle\Helper\BuilderTokenHelper;

use Mautic\FeedBundle\Helper\FeedHelper;

$feedItems=array_merge(FeedHelper::$feedLoopAction,FeedHelper::$feedItems  );
?>

<div id="feedEmailTokens">

    <div class="list-group">
        <?php
        foreach ($feedItems as $k => $v):
        $token = $view->escape(\Mautic\CoreBundle\Helper\BuilderTokenHelper::getVisualTokenHtml($k, $v));
        ?>
            <a href="#" class="list-group-item" data-token="<?php echo $token; ?>">
                <span><?php echo $view['translator']->trans($v);; ?></span>
            </a>
        <?php endforeach; ?>
    </div>


</div>