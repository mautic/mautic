<!-- tabs controls -->
<ul class="nav nav-tabs pr-md pl-md mt-10">
    <?php foreach ($leadForms as $key=>$leadForm): ?>
        <li<?php if ($key == 0): ?> class="active"<?php endif; ?>>
            <a href="#form-<?php echo $leadForm['entity']->getAlias()?>" role="tab" data-toggle="tab">
                        <span class="label label-primary mr-sm" id="form-label-<?php echo $leadForm['entity']->getAlias()?>">
                             <?php echo $leadForm['results']['count']; ?>
                        </span>
                <?php echo $leadForm['entity']->getName(); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<div class="tab-content pa-md">

    <?php foreach ($leadForms as $key=>$leadForm):
        ?>
        <div class="tab-pane fade bdr-w-0  <?php if ($key == 0): ?> active in<?php endif; ?>" id="form-<?php echo $leadForm['entity']->getAlias()?>">
            <?php echo $leadForm['content']; ?>
            <p>
                <a class="btn btn-primary btn-sm" href="<?php echo $view['router']->generate('mautic_form_results', ['objectAction' => 'index', 'objectId' => $leadForm['entity']->getId()]); ?>" data-toggle="ajax"><?php echo $view['translator']->trans('mautic.form.form.contacttab.show.results'); ?></a>&nbsp;
                &nbsp;<a class="btn btn-primary btn-sm" href="<?php echo $view['router']->generate('mautic_form_action', ['objectAction' => 'view', 'objectId' => $leadForm['entity']->getId()]); ?>" data-toggle="ajax"><?php echo $view['translator']->trans('mautic.form.form.contacttab.show.form'); ?></a>
            </p>

        </div>
    <?php endforeach; ?>

</div>