<?php $method      = strtoupper($method); ?>
<?php $form_method = 'GET' === $method || 'POST' === $method ? $method : 'POST'; ?>
<form novalidate autocomplete="false" data-toggle="ajax" role="form" name="<?php echo $form->vars['name']; ?>" method="<?php echo strtolower($form_method); ?>" action="<?php echo $action; ?>"<?php foreach ($attr as $k => $v) {
    printf(' %s="%s"', $view->escape($k), $view->escape($v));
} ?><?php if ($multipart): ?> enctype="multipart/form-data"<?php endif; ?>>
<?php if ($form_method !== $method): ?>
    <input type="hidden" name="_method" value="<?php echo $view->escape($method); ?>" />
<?php endif; ?>
<?php if (count($form->vars['errors'])): ?>
<div class="has-error pa-10">
    <?php echo $view['form']->errors($form); ?>
</div>
<?php endif; ?>
