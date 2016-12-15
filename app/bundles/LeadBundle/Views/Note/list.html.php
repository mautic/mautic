<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticLeadBundle:Note:index.html.php');
}
?>

<ul class="notes" id="LeadNotes">
    <?php foreach ($notes as $note): ?>
        <?php
        //Use a separate layout for AJAX generated content
        echo $view->render('MauticLeadBundle:Note:note.html.php', [
            'note'        => $note,
            'lead'        => $lead,
            'permissions' => $permissions,
        ]); ?>
    <?php endforeach; ?>
</ul>
<?php if ($totalNotes = count($notes)): ?>
<div class="notes-pagination">
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', [
        'totalItems' => $totalNotes,
        'target'     => '#notes-container',
        'page'       => $page,
        'limit'      => $limit,
        'sessionVar' => 'lead.'.$lead->getId().'.note',
        'baseUrl'    => $view['router']->path('mautic_contactnote_index', ['leadId' => $lead->getId()]),
    ]); ?>
</div>
<?php endif; ?>