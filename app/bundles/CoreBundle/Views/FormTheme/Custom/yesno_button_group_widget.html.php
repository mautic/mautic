<?php
//apply attributes to radios
$attr     = $form->vars['attr'];
$onchange = "Mautic.toggleYesNoButtonClass(mQuery(this).attr('id'));";
if (isset($attr['onchange'])) {
    if (substr($attr['onchange'], 0, -1) !== ';') {
        $attr['onchange'] .= ';';
    }
    $attr['onchange'] .= " $onchange";
} else {
    $attr['onchange'] = $onchange;
}
$attr['style'] = 'width: 1px; height: 1px; top: 0; left: 0; margin-top: 0;';

?>
<div class="btn-group btn-block" data-toggle="buttons">
    <?php foreach ($form as $child): ?>
        <?php $class =
            (!empty($child->vars['checked']) ? ' active' : '').
            (!empty($child->vars['disabled']) || !empty($child->vars['read_only']) ? ' disabled' : '').
            ($child->vars['name'] === '0' ? ' btn-no' : ($child->vars['name'] === '1' ? ' btn-yes' : ' btn-extra')).
            ($child->vars['name'] === '0' && !empty($child->vars['checked']) ? ' btn-danger' : '').
            ($child->vars['name'] === '1' && !empty($child->vars['checked']) ? ' btn-success' : ''); ?>
        <label class="btn btn-default <?php echo $class; ?>">
            <?php echo $view['form']->widget($child, ['attr' => $attr]); ?>
            <span><?php echo $view['translator']->trans($child->vars['label']); ?></span>
        </label>
    <?php endforeach; ?>
</div>