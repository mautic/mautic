<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

echo $view['form']->start($form);

echo $view['form']->row($form['from']);
echo $view['form']->row($form['subject']);
echo $view['form']->row($form['body']);
echo $view['form']->row($form['templates']);

echo $view['form']->end($form);