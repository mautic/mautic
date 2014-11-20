<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

echo $view->render('MauticFormBundle:Field:text.html.php', array(
    'field'   => $field,
    'inForm'  => (isset($inForm)) ? $inForm : false,
    'type'    => 'url',
    'id'      => $id,
    'deleted' => (!empty($deleted)) ? true : false,
    'formId'  => (isset($formId)) ? $formId : 0
));