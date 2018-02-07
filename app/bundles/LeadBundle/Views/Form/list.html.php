<!-- tabs controls -->
<ul class="nav nav-tabs pr-md pl-md mt-10">
    <?php foreach ($leadForms as $leadForm): ?>
        <li class="active">
            <a href="#test-container" role="tab" data-toggle="tab">
                        <span class="label label-primary mr-sm" id="TestCount">
                             <?php echo count($leadForm['results']); ?>
                        </span>
                <?php echo $leadForm['entity'][0]->getName(); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<div class="tab-content pa-md">

    <?php foreach ($leadForms as $leadForm):
        print_r($leadForm['results'][0]);
        ?>
        <div class="tab-pane fade bdr-w-0 in active" id="test-container">
            a
        </div>
    <?php endforeach; ?>

</div>