<!-- form -->
<form action="" class="panel">
    <div class="form-control-icon pa-xs">
        <input type="text" class="form-control bdr-w-0" placeholder="Search...">
        <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
    </div>
</form>
<!--/ form -->

<ul class="timeline">
    <li class="header ellipsis bg-white">Recent Events</li>
    <li class="wrapper">
        <ul class="events">
            <?php foreach ($notes as $note): ?>
                <li class="wrapper">
                    <div class="figure"><!--<span class="fa fa-check"></span>--></div>
                    <div class="panel ">
                        <div class="panel-body">
                            <p class="mb-0"><?php echo $view['translator']->trans('mautic.lead.note.details', array('%dateAdded%' => $view['date']->toFullConcat($note['dateAdded']), '%author%' => $note['author']['firstName'] . ' ' . $note['author']['lastName'])); ?></p>
                        </div>
                        <?php if (isset($event['extra'])) : ?>
                            <div class="panel-footer">
                                <?php print_r($event['extra']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </li>
</ul>