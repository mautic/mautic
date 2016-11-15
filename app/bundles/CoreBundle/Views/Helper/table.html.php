<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
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
                        <?php foreach ($row as $item) : ?>
                            <td>
                                <?php if (isset($item['type']) && $item['type'] == 'link') : ?>
                                    <a href="<?php echo $item['link']; ?>"
                                        title="<?php echo $item['value']; ?>"
                                        <?php if (!empty($item['external'])) : ?>
                                        target="_blank"
                                        <?php else : ?>
                                        data-toggle="ajax"
                                        <?php endif; ?>
                                        >
                                        <?php $item = str_replace(['http://', 'https://'], '', $item); ?>
                                        <?php echo $view['assets']->shortenText($item['value'], $shortenLinkText); ?>
                                    </a>
                                <?php elseif (isset($item['value'])): ?>
                                    <?php echo $item['value']; ?>
                                <?php elseif (is_string($item)): ?>
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
