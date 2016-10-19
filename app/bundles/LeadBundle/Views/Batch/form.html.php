<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$attr                         = $form->vars['attr'];
$attr['data-submit-callback'] = 'leadBatchSubmit';
echo $view['form']->form($form, ['attr' => $attr]);
