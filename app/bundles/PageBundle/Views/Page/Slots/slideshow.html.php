<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// Enable / Disable the slideshow
if (isset($slideshow_enabled) && !$slideshow_enabled && $public) {
    return;
}

// define default values
if (!isset($height) || !$height) {
    $height = '300px';
}

if (!isset($width) || !$width) {
    $width = '100%';
}

if (!isset($background_color) || !$background_color) {
    $background_color = 'transparent';
}

if ($background_color != 'transparent') {
    $background_color = '#'.$background_color;
}

// css declaration for whole slideshow
$css = <<<CSS
.slideshow-{$slot} .item {
	height: {$height};
	width: {$width};
    background-color: {$background_color};
}
.slideshow-{$slot} {
    height: {$height};
    width: {$width};
    background-color: {$background_color};
}
CSS;

$view['assets']->addStyleDeclaration($css);
?>

<!-- Header Carousel -->
<div id="carousel-generic-<?php echo $slot ?>" class="carousel slide slideshow-<?php echo $slot ?>" data-ride="carousel">

<?php if (isset($dot_navigation) && $dot_navigation) : ?>
    <!-- Indicators -->
    <ol class="carousel-indicators">
    <?php foreach ($slides as $key => $slide) : ?>
        <li data-target="#carousel-generic-<?php echo $slot ?>" data-slide-to="<?php echo $key; ?>" <?php echo $key == 0 ? 'class="active"' : '' ?>></li>
	<?php endforeach; ?>
    </ol>
<?php endif; ?>

    <!-- Wrapper for slides -->
    <div class="carousel-inner" role="listbox">
	<?php foreach ($slides as $key => $slide) : ?>
		<?php
// css declaration for each slide
$css = <<<CSS
.slide-{$slot}-{$key} {
	background: url("{$slide['background-image']}") no-repeat;
	background-position: center center;
}
CSS;

$view['assets']->addStyleDeclaration($css);
        ?>
        <div class="item text-center <?php echo $key == 0 ? 'active' : '' ?> slide-<?php echo $slot ?>-<?php echo $key ?>">
            <?php if (!empty($slide['captionheader']) || !empty($slide['captionheader'])) : ?>
            <div class="carousel-caption">
	            <?php if (!empty($slide['captionheader'])) : ?>
                <h2><?php echo $slide['captionheader']; ?></h2>
                <?php endif; ?>
                <?php if (!empty($slide['captionbody'])) : ?>
                <p><?php echo $slide['captionbody']; ?></p>
                <?php endif; ?>
            </div>
	        <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>

<?php if (isset($arrow_navigation) && $arrow_navigation) : ?>
    <!-- Controls -->
    <a class="left carousel-control" href="#carousel-generic-<?php echo $slot ?>" role="button" data-slide="prev">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="right carousel-control" href="#carousel-generic-<?php echo $slot ?>" role="button" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
    </a>
<?php endif; ?>

    <?php if (!$public) : ?>
    <div class="dropdown slideshow-options">
		<button class="btn btn-default dropdown-toggle" type="button" id="slideshow-options" data-toggle="dropdown" aria-expanded="true">
			<i class="fa fa-cog"></i>
			<span class="caret"></span>
		</button>
		<ul class="dropdown-menu" role="menu" aria-labelledby="slideshow-options">
			<li role="presentation">
				<a role="menuitem" tabindex="-1" href="#" data-toggle="modal" data-target=".slideshow-global-config<?php echo $slot ?>">
					<i class="fa fa-pencil-square-o"></i> Edit Slideshow
				</a>
			</li>
			<li role="presentation">
				<a role="menuitem" tabindex="-1" href="#" data-toggle="modal" data-target=".slideshow-slides-config<?php echo $slot ?>">
					<i class="fa fa-bars"></i> Edit Slides
				</a>
			</li>
		</ul>
	</div>
	<?php endif; ?>
</div>

<?php if (!$public) : ?>
<!-- Slideshow global config modal edit form -->
<div class="modal fade slideshow-global-config<?php echo $slot ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title" id="exampleModalLabel">Configure slideshow</h4>
			</div>
			<div class="modal-body">
				<?php echo $view['form']->start($configForm); ?>

				<?php echo $view['form']->end($configForm); ?>
			</div>
			<div class="modal-footer">
                <button type="button" class="btn btn-default" onclick="SlideshowManager.saveConfigObject('<?php echo $slot ?>');">
	                <i class="fa fa-check"></i> Apply
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
	                <i class="fa fa-cross"></i> Close
                </button>
            </div>
		</div>
	</div>
</div>

<!-- Slideshow slide config modal edit form -->
<div class="modal fade slideshow-slides-config<?php echo $slot ?> slides-config" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title" id="exampleModalLabel">Configure slides</h4>
			</div>
			<div class="modal-body">
				<div class="col-md-3 bg-white height-auto list-of-slides">
					<ul class="list-group list-group-tabs" data-toggle="sortablelist">
					<?php foreach ($slides as $key => $slide) : ?>
		                 <li class="list-group-item <?php echo $key == 0 ? 'active' : '' ?>">
							<a href="#slide-tab-<?php echo $key; ?>" class="steps" data-toggle="tab">
								Slide <span class="slide-id"><?php echo $key; ?></span>
							</a>
                            <span class="btn btn-default btn-xs pull-right ui-sort-handle" data-toggle="tooltip" data-placement="right" data-original-title="Drag&drop this item by the button to change order of the slides.">
                                <i class="fa fa-arrows-v"></i>
                            </span>
						</li>
					<?php endforeach; ?>
		            </ul>
		            <button type="button" onclick="SlideshowManager.newSlide();" class="btn button-default new-slide">
						<i class="fa fa-plus-circle"></i> New Slide
					</button>
	            </div>
	            <div class="tab-content col-md-9 bg-auto height-auto bdr-l config-fields">
				<?php foreach ($slides as $key => $slide) : ?>
					<div class="tab-pane fade bdr-rds-0 bdr-w-0 <?php echo $key == 0 ? 'in active' : '' ?>" id="slide-tab-<?php echo $key; ?>">
						<?php echo $view['form']->start($slide['form']); ?>
						<div class="row text-right">
							<?php echo $view['form']->row($slide['form']['slides:'.$key.':remove']); ?>
						</div>
						<?php echo $view['form']->row($slide['form']['slides:'.$key.':captionheader']); ?>
						<?php echo $view['form']->row($slide['form']['slides:'.$key.':captionbody']); ?>
						<?php echo $view['form']->row($slide['form']['slides:'.$key.':order']); ?>
						<div class="row">
							<div class="col-md-9">
								<?php echo $view['form']->row($slide['form']['slides:'.$key.':background-image']); ?>
							</div>
							<div class="col-md-3">
								<button type="button" onclick="SlideshowManager.BrowseServer('slides:<?php echo $key; ?>:background-image');" class="btn button-default file-manager-toggle">
									<i class="fa fa-folder-open-o"></i> File Manager
								</button>
							</div>
						</div>
						<?php echo $view['form']->end($slide['form']); ?>
					</div>
				<?php endforeach; ?>
				</div>
				<div class="clearfix"></div>
				<div id="fileManager"></div>
			</div>
			<div class="modal-footer">
                <button type="button" class="btn btn-default" onclick="SlideshowManager.saveConfigObject('<?php echo $slot ?>');">
	                <i class="fa fa-check"></i> Apply
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
	                <i class="fa fa-cross"></i> Close
                </button>
            </div>
		</div>
	</div>
</div>
<?php endif; ?>
