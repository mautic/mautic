<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php if (!empty($stats)): ?>
    <table class="table table-bordered table-striped mb-0">
        <tbody>
        <tr>
            <th colspan="2" class="bg-primary segment-title">
                <?php echo $title; ?>
            </th>
        </tr>
        <?php foreach ($stats as $stat) : ?>
            <?php if (!$countIds = count($stat['ids'])): ?>
                <?php continue; ?>
            <?php endif; ?>
            <tr>
                <td>
                    <?php echo $view['translator']->trans($stat['label']); ?>
                </td>
                <td width="5%">
                                    <span class="mt-xs label label-primary has-click-event clickable-stat"><a
                                            href="<?php echo $view['router']->path(
                                                $stat['route'],
                                                [
                                                    'search' => $view['translator']->trans(
                                                            'mautic.core.searchcommand.ids'
                                                        ).':'.implode(',', $stat['ids']),
                                                ]
                                            ); ?>">
                                              <?php echo $countIds; ?>
                                            </a></span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
