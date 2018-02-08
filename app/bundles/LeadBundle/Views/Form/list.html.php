<!-- tabs controls -->
<ul class="nav nav-tabs pr-md pl-md mt-10">
    <?php foreach ($leadForms as $leadForm): ?>
        <li class="active">
            <a href="#test-container" role="tab" data-toggle="tab">
                        <span class="label label-primary mr-sm" id="form-<?php echo $leadForm['entity']->getAlias()?>">
                             <?php echo count($leadForm['results']); ?>
                        </span>
                <?php echo $leadForm['entity']->getName(); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<div class="tab-content pa-md">

    <?php foreach ($leadForms as $leadForm):
        ?>
        <div class="tab-pane fade bdr-w-0 in active" id="form-<?php echo $leadForm['entity']->getAlias()?>">
            <?php
                               echo $leadForm['content'];
            ?>
        </div>
    <?php endforeach; ?>

</div>