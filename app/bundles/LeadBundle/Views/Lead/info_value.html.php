<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (empty($type)) {
    $type = 'string';
}

?>
<?php //email ?>
<?php if (stripos($name, "email") !== false): ?>
<a target="_blank" href="mailto:<?php echo $value; ?>"><?php echo $value; ?></a>
<?php //website ?>
<?php elseif (stripos($name, "website") !== false): ?>
<?php //website - with http already?>
<?php if (strpos($value, 'http') === 0): ?>
<a target="_blank" href="<?php echo $value; ?>"><?php echo $value; ?></a>
<?php //website - without http at all ?>
<?php elseif (strpos($value, 'http') === false): ?>
<a target="_blank" href="http://<?php echo $value; ?>"><?php echo $value; ?></a>
<?php else: ?>
<?php //website - can't determine or multiple urls ?>
<?php echo $value; ?>
<?php endif; ?>
<?php //not marked as website but starts with http ?>
<?php elseif (strpos($value, 'http') === 0): ?>
<a target="_blank" href="<?php echo $value; ?>"><?php echo $value; ?></a>
<?php
elseif ($type == 'datetime'):
    echo $view['date']->toFull($value);
?>
<?php else: ?>
<?php
    if (isset($socialProfileUrls)) {
        //test for social networking profiles
        foreach ($socialProfileUrls as $network => $url) {
            if (stripos($name, $network) !== false && (strpos($value, 'http') === false)) {
                $url   = str_replace('%handle%', $value, $url);
                $value = '<a href="'.$url.'" target="_blank">'.$value.'</a>';
                break;
            }
        }
    }

    echo $value;
?>
<?php endif; ?>