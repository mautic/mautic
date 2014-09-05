<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'point');

$header = ($entity->getId()) ?
    $view['translator']->trans('mautic.point.header.edit',
        array('%name%' => $view['translator']->trans($entity->getName()))) :
    $view['translator']->trans('mautic.point.header.new');
$view['slots']->set("headerTitle", $header);

echo $view['form']->start($form);
echo $view['form']->row($form['name']);
echo $view['form']->row($form['description']);
echo $view['form']->row($form['category_lookup']);
echo $view['form']->row($form['category']);
echo $view['form']->row($form['isPublished']);
echo $view['form']->row($form['publishUp']);
echo $view['form']->row($form['publishDown']);
echo $view['form']->row($form['type']);
?>
<div id="pointActionProperties">
    <?php
    if (isset($form['properties'])):
    echo $view['form']->row($form['properties']);
    endif;
    ?>
</div>
<?php
echo $view['form']->end($form);
?>