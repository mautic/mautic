<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//do not include the surrounding form since this will be embedded into the current form
foreach ($form as $f) {
    echo $view['form']->row($f);
}
