<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!isset($class)) {
    $class = 'table';
}
if (!isset($shortenLinkText)) {
    $shortenLinkText = 30;
}
?>

<table class="<?php echo $class; ?>">
    <?php if (!empty($headItems)) : ?>
        <thead>
            <tr>
            <?php foreach ($headItems as $headItem) : ?>
                <th><?php echo $view['translator']->trans($headItem); ?></th>
            <?php endforeach; ?>
            </tr>
        </thead>
    <?php endif; ?>
    <?php if (!empty($bodyItems)) : ?>
        <tbody>
            <?php foreach ($bodyItems as $row) : ?>
                <tr>
                    <?php if (is_array($row)) : ?>
                        <?php foreach ($row as $key => $item) : ?>
                            <td>
                                <?php if (is_string($key)) : ?>
                                    <a href="<?php echo $key; ?>" title="<?php echo $item; ?>">
                                        <?php $item = str_replace(array('http://', 'https://'), '', $item); ?>
                                        <?php echo $view['assets']->shortenText($item, $shortenLinkText); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo $item; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    <?php endif; ?>
</table>
