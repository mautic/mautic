<?php


//do not include the surrounding form since this will be embedded into the current form
foreach ($form as $f) {
    echo $view['form']->row($f);
}
