<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'dwc');
$fields = $form->vars['fields'];
$id     = $form->vars['data']->getId();
$index  = '';

if (!empty($id)) {
    $name   = $form->vars['data']->getName();
    $header = $view['translator']->trans('mautic.lead.list.header.edit', ["%name%" => $name]);
} else {
    $header = $view['translator']->trans('mautic.lead.list.header.new');
}
$view['slots']->set("headerTitle", $header);

$templates = [
    'countries' => 'country-template',
    'regions'   => 'region-template',
    'timezones' => 'timezone-template',
    'select'    => 'select-template',
    'lists'     => 'leadlist-template',
    'emails'    => 'lead_email_received-template',
    'tags'      => 'tags-template'
];

$mainErrors   = ($view['form']->containsErrors($form, ['filters'])) ? 'class="text-danger"' : '';
?>

<?php echo $view['form']->start($form); ?>
<div class="box-layout">
    <div class="col-md-9 bg-white height-auto">
        <div class="row">
            <div class="col-xs-12">
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active">
                        <a href="#details" role="tab" data-toggle="tab"<?php echo $mainErrors; ?>>
                            <?php echo $view['translator']->trans('mautic.core.details'); ?>
                            <?php if ($mainErrors): ?>
                                <i class="fa fa-warning"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>

                <!-- start: tab-content -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="details">
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['name']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <?php echo $view['form']->row($form['description']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['isPublished']); ?>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>

<div class="hide" id="templates">
    <?php foreach ($templates as $dataKey => $template): ?>
        <?php $attr = ($dataKey == 'tags') ? ' data-placeholder="' . $view['translator']->trans('mautic.lead.tags.select_or_create') . '" data-no-results-text="' . $view['translator']->trans('mautic.lead.tags.enter_to_create') . '" data-allow-add="true" onchange="Mautic.createLeadTag(this)"' : ''; ?>
        <select class="form-control not-chosen <?php echo $template; ?>" name="leadlist[filters][__name__][filter]" id="leadlist_filters___name___filter"<?php echo $attr; ?>>
            <?php
            if (isset($form->vars[$dataKey])):
                foreach ($form->vars[$dataKey] as $value => $label):
                    if (is_array($label)):
                        echo "<optgroup label=\"$value\">\n";
                        foreach ($label as $optionValue => $optionLabel):
                            echo "<option value=\"$optionValue\">$optionLabel</option>\n";
                        endforeach;
                        echo "</optgroup>\n";
                    else:
                        if ($dataKey == 'lists' && (isset($currentListId) && (int) $value === (int) $currentListId))
                            continue;
                        echo "<option value=\"$value\">$label</option>\n";
                    endif;
                endforeach;
            endif;
            ?>
        </select>
    <?php endforeach; ?>
</div>