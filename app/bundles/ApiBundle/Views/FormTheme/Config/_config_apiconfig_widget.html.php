<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php foreach ($form->children as $f): ?>
<div class="row">
    <div class="col-md-6">
        <?php echo $view['form']->row($f); ?>
    </div>
</div>
<?php endforeach; ?>