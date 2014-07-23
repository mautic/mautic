<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php foreach ($profile as $l => $v): ?>
    <div class="row">
        <div class="col-xs-3">
            <?php echo $view['translator']->trans('mautic.social.'.$network.'.'.$l); ?>
        </div>
        <div class="col-xs-9 field-value">
            <?php echo $view->render('MauticLeadBundle:Lead:info_value.html.php', array(
                'name'              => $l,
                'value'             => $v,
                'socialProfileUrls' => $socialProfileUrls
            )); ?>
        </div>
    </div>
<?php endforeach; ?>