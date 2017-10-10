<?php

/*
* @copyright   2014 Mautic Contributors. All rights reserved
* @author      Mautic
*
* @link        http://mautic.org
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

?>

<?php if (!empty($graphOrder) && !empty($graphs)): ?>
    <div class="mt-lg pa-md" style="clear: both">
        <div class="row equal">
            <?php
            $rowCount = 0;
            foreach ($graphOrder as $key):
                $details = $graphs[$key];
                if (!isset($details['data'])) {
                    continue;
                }
                if ($rowCount >= 12):
                    echo '</div><div class="row equal">';
                    $rowCount = 0;
                endif;
                echo $view->render('MauticReportBundle:Graph:'.ucfirst($details['type']).'.html.php', ['graph' => $details['data'], 'options' => $details['options'], 'report' => $report]);
                $rowCount += ($details['type'] == 'line') ? 12 : 4;
            endforeach;
            ?>
        </div>
    </div>
<?php endif; ?>