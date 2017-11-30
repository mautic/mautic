<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$halfSize = (empty($embedded)) ? 4 : 6;
$fullSize = (empty($embedded)) ? 8 : 12;
?>

    <!-- pane -->
<?php
foreach ($groups as $key => $group):
    if (isset($fields[$group])):
        $groupFields = $fields[$group];
        if (!empty($groupFields)): ?>
            <div class="tab-pane fade<?php if ($key === 0) {
            echo ' in active';
        } ?> bdr-rds-0 bdr-w-0" id="company-<?php echo $group; ?>">
                <?php if (empty($embedded)): ?>
                    <div class="pa-md bg-auto bg-light-xs bdr-b">
                        <h4 class="fw-sb">
                            <?php echo $view['translator']->trans('mautic.lead.field.group.'.$group); ?>
                        </h4>
                    </div>
                <?php endif; ?>
                <div class="pa-md">
                    <?php if ($group == 'core'): ?>
                        <div class="form-group mb-0">
                            <div class="row">
                                <?php if (isset($form['companyname'])): ?>
                                    <div class="col-sm-<?php echo $halfSize; ?>">
                                        <?php echo $view['form']->row($form['companyname']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($form['companyemail'])): ?>
                                    <div class="col-sm-<?php echo $halfSize; ?>">
                                        <?php echo $view['form']->row($form['companyemail']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <hr class="mnr-md mnl-md">
                        <?php if (isset($form['companyaddress1']) || isset($form['companyaddress2']) || isset($form['companycity'])
                            || isset($form['companystate'])
                            || isset($form['companyzipcode'])
                            || isset($form['companycountry'])
                        ): ?>
                            <div class="form-group mb-0">
                                <label
                                    class="control-label mb-xs"><?php echo $view['translator']->trans('mautic.company.field.address'); ?></label>
                                <?php if (isset($form['companyaddress1'])): ?>
                                    <div class="row mb-xs">
                                        <div class="col-sm-<?php echo $fullSize; ?>">
                                            <?php echo $view['form']->widget(
                                                $form['companyaddress1'],
                                                ['attr' => ['placeholder' => $form['companyaddress1']->vars['label']]]
                                            ); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($form['companyaddress2'])): ?>
                                    <div class="row mb-xs">
                                        <div class="col-sm-<?php echo $fullSize; ?>">
                                            <?php echo $view['form']->widget(
                                                $form['companyaddress2'],
                                                ['attr' => ['placeholder' => $form['companyaddress2']->vars['label']]]
                                            ); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="row mb-xs">
                                    <?php if (isset($form['companycity'])): ?>
                                        <div class="col-sm-<?php echo $halfSize; ?>">
                                            <?php echo $view['form']->widget(
                                                $form['companycity'],
                                                ['attr' => ['placeholder' => $form['companycity']->vars['label']]]
                                            ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($form['companystate'])): ?>
                                        <div class="col-sm-<?php echo $halfSize; ?>">
                                            <?php echo $view['form']->widget(
                                                $form['companystate'],
                                                ['attr' => ['placeholder' => $form['companystate']->vars['label']]]
                                            ); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="row">
                                    <?php if (isset($form['companyzipcode'])): ?>
                                        <div class="col-sm-<?php echo $halfSize; ?>">
                                            <?php echo $view['form']->widget(
                                                $form['companyzipcode'],
                                                ['attr' => ['placeholder' => $form['companyzipcode']->vars['label']]]
                                            ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($form['companycountry'])): ?>
                                        <div class="col-sm-<?php echo $halfSize; ?>">
                                            <?php echo $view['form']->widget(
                                                $form['companycountry'],
                                                ['attr' => ['placeholder' => $form['companycountry']->vars['label']]]
                                            ); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php endif; ?>
                        <hr class="mnr-md mnl-md">
                    <?php endif; ?>
                    <div class="form-group mb-0">
                        <div class="row">
                            <?php foreach ($groupFields as $alias => $field): ?>
                                <?php
                                if ($form[$alias]->isRendered()) {
                                    continue;
                                } ?>
                                <div class="col-sm-<?php echo $fullSize; ?>">
                                    <?php echo $view['form']->row($form[$alias]); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if (!empty($embedded)): ?>
                        <hr class="mnr-md mnl-md">
                        <div>
                            <?php echo $view['form']->row($form['owner']); ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <?php
        endif;
    endif;
endforeach;
