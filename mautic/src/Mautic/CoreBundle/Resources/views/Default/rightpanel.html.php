<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$value    = $app->getSession()->get('mautic.global_search');
$btnClass = ($value) ? "fa-eraser" : "fa-search";
$function = ($value) ? "clearGlobalSearchResults" : "onClickGlobalSearchResults";
?>

<div class="right-panel-inner-wrapper">
    <div class="right-panel-header">
        <div class="input-group">
            <input class="input-global-search form-control" name="global_search" id="global_search"
                onkeypress="Mautic.onKeyPressGlobalSearchResults(event);"
                placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>"
                value="<?php echo $value ?>" />
            <span class="input-group-btn">
                <button class="btn btn-default btn-global-search-submit" type="button" onclick="Mautic.<?php echo $function; ?>();">
                    <i class="fa <?php echo $btnClass; ?>"></i>
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