<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="row">
    <div class="col-xs-4">
        <img class="img img-responsive" style="margin: auto;" src="<?php echo $view['assets']->getUrl($icon); ?>" />
    </div>

    <div class="col-xs-8">
        <h3>
            <?php echo $bundle->getPrimaryDescription(); ?>
        </h3>
    </div>
</div>

<?php if ($bundle->hasSecondaryDescription()) : ?>
<div class="row mt-lg">
    <div class="col-xs-12">
        <?php echo $bundle->getSecondaryDescription(); ?>
    </div>
</div>
<?php endif; ?>
