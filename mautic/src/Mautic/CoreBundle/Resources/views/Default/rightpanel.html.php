<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$value = $app->getSession()->get('mautic.global_search');
?>

<div class="right-panel-inner-wrapper">
    <div class="right-panel-header">
        <div class="input-group">
            <input type="search"
                   autocomplete="off"
                   class="input-global-search form-control" name="global_search" id="global_search"
                   data-target=".global-search-wrapper"
                   data-action="<?php echo $view['router']->generate('mautic_core_ajax', array('action' => 'globalSearch')); ?>"
                   data-overlay-text="<?php echo $view['translator']->trans('mautic.core.search.livesearch'); ?>"
                   data-overlay-background="#513B49"
                   data-overlay-color="#ffffff"
                   placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>"
                   value="<?php echo $value ?>" />
            <span class="input-group-btn">
                <button class="btn btn-default btn-global-search-submit"
                        type="button"
                        data-livesearch-parent="global_search"
                    >
                    <i class="fa fa-search"></i>
                </button>
            </span>
        </div>
    </div>
    <div class="side-panel-nav-wrapper global-search-wrapper">
        <?php
        echo $view['actions']->render(
            new \Symfony\Component\HttpKernel\Controller\ControllerReference('MauticCoreBundle:Default:globalSearch')
        );
        ?>
    </div>
</div>