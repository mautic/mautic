<?php

use Mautic\CoreBundle\Form\Type\TelType;

echo $view['form']->block($form, 'form_widget_simple', ['type' => isset($type) ? $type : TelType::class]);
