<?php declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<?php echo $view['form']->row($form['filter-totalFieldCount']); ?>
<?php foreach ($form as $fieldName => $fieldForm): ?>
<?php if (isset($fieldForm['mappedField'])): ?>
    <div class="row">
        <div class="col-sm-12"><?php echo $view['form']->label($fieldForm); ?></div>
    </div>
    <div class="row">
        <div class="col-sm-6<?php if ($view['form']->containsErrors($fieldForm['mappedField'])) {
    echo ' has-error';
} ?>">
            <?php echo $view['form']->widget($fieldForm['mappedField']); ?>
        </div>
        <div class="col-sm-6"><?php echo $view['form']->widget($fieldForm['syncDirection']); ?></div>
    </div>
    <hr />
<?php endif; ?>
<?php endforeach; ?>
<?php echo $view->render(
    'MauticCoreBundle:Helper:pagination.html.php',
    [
        'totalItems'  => $form['filter-totalFieldCount']->vars['data'],
        'page'        => $page,
        'limit'       => 15,
        'fixedLimit'  => true,
        'sessionVar'  => $integration.'-'.$object,
        'target'      => '#IntegrationEditModal',
        'jsCallback'  => 'Mautic.getPaginatedIntegrationFields',
        'jsArguments' => [
            [
                'object'      => $object,
                'integration' => $integration,
                'keyword'     => $form['filter-keyword']->vars['data'],
            ],
        ],
    ]
); ?>
