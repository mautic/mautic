<?php

if (empty($function)) {
    $function = 'form';
}

if (empty($variables)) {
    $variables = [];
}

echo $view['form']->$function($form, $variables);
