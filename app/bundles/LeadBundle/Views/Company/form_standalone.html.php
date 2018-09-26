<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'company');

$header = ($entity->getId())
    ?
    $view['translator']->trans(
        'mautic.company.menu.edit',
        ['%name%' => $view->escape($entity->getName())]
    )
    :
    $view['translator']->trans('mautic.company.menu.new');
$view['slots']->set('headerTitle', $header);

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'      => $entity,
            'routeBase' => 'company',
            'langVar'   => 'lead.company',
        ]
    )
);
echo $view['form']->start($form);
?>
    <div class="box-layout">
        <div class="col-md-3 bg-white height-auto">
            <div class="pr-lg pl-lg pt-md pb-md">
                <ul class="list-group list-group-tabs">
                    <?php $step = 1; ?>
                    <?php foreach ($groups as $g): ?>
                        <?php if (!empty($fields[$g])): ?>
                            <li class="list-group-item<?php if ($step === 1) {
    echo ' active';
} ?>">
                                <a href="#company-<?php echo $g; ?>" class="steps" data-toggle="tab">
                                    <?php echo $view['translator']->trans('mautic.lead.field.group.'.$g); ?>
                                </a>
                            </li>
                            <?php ++$step; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <hr/>
                <div>
                    <?php echo $view['form']->row($form['score']); ?>
                </div>
                <hr/>
                <div>
                    <?php echo $view['form']->row($form['owner']); ?>
                </div>
            </div>
        </div>
        <div class="col-md-9 bg-auto height-auto bdr-l">
            <div class="tab-content">
                <?php echo $view->render(
                    'MauticLeadBundle:Company:form_fields.html.php',
                    ['form' => $form, 'groups' => $groups, 'fields' => $fields]
                ); ?>
            </div>
        </div>
    </div>

<?php echo $view['form']->end($form); ?>