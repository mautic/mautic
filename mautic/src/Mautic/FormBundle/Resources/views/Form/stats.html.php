<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo generate stats for results
?>
<div class="panel panel-success">
    <div class="panel-heading">
        <span><?php echo $view['translator']->trans('mautic.form.form.header.stats'); ?></span>
        <span> - </span>
        <span>
            <a href="<?php echo $view['router']->generate('mautic_form_action',
                    array('objectAction' => 'results', 'objectId' => $form->getId())); ?>"
               data-toggle="ajax"
               data-menu-link="mautic_form_index"
               ><?php echo $view['translator']->trans('mautic.form.form.results'); ?></a>
        </span>
    </div>
    <div class="panel-body">
        <h3>@todo - add stats, graphs, etc</h3>
    </div>
</div>