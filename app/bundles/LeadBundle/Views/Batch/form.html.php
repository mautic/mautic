<?php

$attr                         = $form->vars['attr'];
$attr['data-submit-callback'] = 'leadBatchSubmit';
echo $view['form']->form($form, ['attr' => $attr]);
