<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="right-panel-inner-wrapper">
    <div class="right-panel-header">
        <input class="input-global-search form-control" name="global_search" id="global_search"
            onkeypress="Mautic.loadGlobalSearchResults(event, this.value);"
            placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>"
            value="<?php echo $app->getSession()->get('mautic.global_search'); ?>" />
    </div>
    <div class="side-panel-nav-wrapper global-search-wrapper">
        <?php
        echo $view['actions']->render(
            new \Symfony\Component\HttpKernel\Controller\ControllerReference('MauticCoreBundle:Default:globalSearch')
        );
        ?>
    </div>
</div>