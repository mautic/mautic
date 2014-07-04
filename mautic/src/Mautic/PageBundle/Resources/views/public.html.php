<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//extend the template chosen
$view->extend(':Templates/'.$template.':page.html.php');


$head = $view['slots']->get('head', '');
$view['slots']->start('head');
echo $head;
?>

<script>

</script>
<?php
$view['slots']->end('head');

//Set the slots
foreach ($slots as $slot) {
    $value = isset($content[$slot]) ? $content[$slot] : "";
    $view['slots']->set($slot, $value);
}