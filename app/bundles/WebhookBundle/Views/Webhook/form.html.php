<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'webhook');
?>

<?php echo $view['form']->start($form); ?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto">
        <div class="pa-md">
            <div class="row">
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['title']); ?>
                    <?php echo $view['form']->row($form['description']); ?>
                    <?php echo $view['form']->row($form['webhook_url']); ?>
                    <?php echo $view['form']->row($form['description']); ?>
                    <?php echo $view['form']->row($form['events']); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <?php echo $view['form']->row($form['category']); ?>
        <?php echo $view['form']->row($form['isPublished']); ?>
    </div>
</div>
<?php echo $view['form']->end($form); ?>