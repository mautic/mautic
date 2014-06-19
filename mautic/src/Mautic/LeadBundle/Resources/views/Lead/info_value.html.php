<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$name  = $field->getField()->getLabel();
$value = $field->getValue();
$type  = $field->getField()->getType();
?>

<?php if (stripos($name, "email") !== false): ?>
<a target="_new" href="mailto:<?php echo $value; ?>"><?php echo $value; ?></a>

<?php elseif (stripos($name, "skype") !== false): ?>
<a href="skype:<?php echo $value; ?>?call"><?php echo $value; ?></a>

<?php elseif (stripos($name, "facebook") !== false): ?>
<?php if (strpos($value, 'http') === false): ?>
<a target="_new" href="https://www.facebook.com/<?php echo $value; ?>"><?php echo $value; ?></a>
<?php else: ?>
<a target="_new" href="<?php echo $value; ?>"><?php echo $value; ?></a>
<?php endif; ?>

<?php elseif (stripos($name, "twitter") !== false): ?>
<?php if (strpos($value, 'http') === false): ?>
<a target="_new" href="https://www.twitter.com/<?php echo $value; ?>"><?php echo $value; ?></a>
<?php else: ?>
<a target="_new" href="<?php echo $value; ?>"><?php echo $value; ?></a>
<?php endif; ?>

<?php elseif (stripos($name, "linkedin") !== false): ?>
<?php if (strpos($value, 'http') === false): ?>
<a target="_new" href="https://www.linkedin.com/in/<?php echo $value; ?>"><?php echo $value; ?></a>
<?php else: ?>
<a target="_new" href="<?php echo $value; ?>"><?php echo $value; ?></a>
<?php endif; ?>

<?php elseif (stripos($name, "linkedin") !== false): ?>
<?php if (strpos($value, 'http') === false): ?>
<a target="_new" href="https://www.linkedin.com/in/<?php echo $value; ?>"><?php echo $value; ?></a>
<?php else: ?>
<a target="_new" href="<?php echo $value; ?>"><?php echo $value; ?></a>
<?php endif; ?>

<?php elseif (stripos($name, "google plus") !== false): ?>
<?php if (strpos($value, 'http') === false): ?>
<a target="_new" href="https://plus.google.com/+<?php echo $value; ?>"><?php echo $value; ?></a>
<?php else: ?>
<a target="_new" href="<?php echo $value; ?>"><?php echo $value; ?></a>
<?php endif; ?>

<?php elseif (stripos($name, "website") !== false): ?>
<?php if (strpos($value, 'http') === false): ?>
<a target="_new" href="http://<?php echo $value; ?>"><?php echo $value; ?></a>
<?php else: ?>
<a target="_new" href="<?php echo $value; ?>"><?php echo $value; ?></a>
<?php endif; ?>

<?php
elseif ($type == 'datetime'):
    $dateHelper = new \Mautic\CoreBundle\Helper\DateTimeHelper($value);
    echo $dateHelper->getLocalString($dateFormats[$type]);
?>

<?php else: ?>
<?php echo $value; ?>

<?php endif; ?>