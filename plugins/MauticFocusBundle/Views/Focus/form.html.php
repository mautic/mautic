<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'focus');

$header = ($entity->getId())
    ?
    $view['translator']->trans(
        'mautic.focus.edit',
        ['%name%' => $view['translator']->trans($entity->getName())]
    )
    :
    $view['translator']->trans('mautic.focus.new');
$view['slots']->set('headerTitle', $header);

echo $view['assets']->includeScript('plugins/MauticFocusBundle/Assets/js/focus.js');
echo $view['assets']->includeStylesheet('plugins/MauticFocusBundle/Assets/css/focus.css');

echo $view['form']->start($form);
?>
    <!-- start: box layout -->
    <div class="box-layout">
        <!-- container -->
        <div class="col-md-9 bg-auto height-auto bdr-r pa-md">
            <div class="row">
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['name']); ?>
                </div>
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['website']); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?php echo $view['form']->row($form['description']); ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 bg-white height-auto">
            <div class="pr-lg pl-lg pt-md pb-md">
                <?php
                echo $view['form']->row($form['category']);
                echo $view['form']->row($form['isPublished']);
                echo $view['form']->row($form['publishUp']);
                echo $view['form']->row($form['publishDown']);
                ?>
                <hr />
                <h5><?php echo $view['translator']->trans('mautic.email.utm_tags'); ?></h5>
                <br />
                <?php
                foreach ($form['utmTags'] as $i => $utmTag):
                    echo $view['form']->row($utmTag);
                endforeach;
                ?>
            </div>
        </div>
    </div>

    <div class="hide builder focus-builder">
        <div class="builder-content">
            <div class="website-preview">
                <div class="website-placeholder hide well well-lg col-md-6 col-md-offset-3 mt-lg">
                    <div class="row">
                        <div class="mautibot-image col-xs-3 text-center">
                            <img class="img-responsive" style="max-height: 125px; margin-left: auto; margin-right: auto;" src="<?php echo $view['mautibot']->getImage(
                                'wave'
                            ); ?>"/>
                        </div>
                        <div class="col-xs-9">
                            <h4><i class="fa fa-quote-left"></i> <?php echo $view['translator']->trans('mautic.core.noresults.tip'); ?>
                                <i class="fa fa-quote-right"></i></h4>
                            <p class="mt-md">
                                <?php echo $view['translator']->trans('mautic.focus.website_placeholder'); ?>
                            </p>
                            <div class="input-group">
                                <input id="websiteUrlPlaceholderInput" disabled type="text" class="form-control" placeholder="http..."/>
                                <span class="input-group-btn">
                                <button class="btn btn-default btn-fetch" type="button"><?php echo $view['translator']->trans(
                                        'mautic.focus.fetch_snapshot'
                                    ); ?></button>
                            </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="viewport-switcher text-center bdr-t-sm bdr-b-sm bdr-r-sm">
                    <div class="btn btn-sm btn-success btn-nospin btn-viewport" data-viewport="desktop">
                        <i class="fa fa-mobile-phone fa-3x"></i>
                    </div>
                </div>
                <figure id="websiteScreenshot">
                    <div class="screenshot-container text-center">
                        <div class="preview-body text-left"></div>
                        <canvas id="websiteCanvas">
                            Your browser does not support the canvas element.
                        </canvas>
                    </div>
                </figure>
            </div>
        </div>
        <div class="builder-panel builder-panel-focus">
            <div class="builder-panel-top">
                <p>
                    <button type="button" class="btn btn-primary btn-close-builder btn-block" onclick="Mautic.closeFocusBuilder(this);"><?php echo $view['translator']->trans(
                            'mautic.core.close.builder'
                        ); ?></button>
                </p>
            </div>
            <?php
            $class = ($form['type']->vars['data']) ? 'focus-type-'.$form['type']->vars['data'] : 'focus-type-all';
            $class .= ($form['style']->vars['data']) ? ' focus-style-'.$form['style']->vars['data'] : ' focus-style-all';
            ?>
            <div class="<?php echo $class; ?>" style="margin-top: 40px;" id="focusFormContent">
                <!-- start focus type  -->
                <div class="panel panel-default" id="focusType">
                    <div class="panel-heading">
                        <h4 class="focus-type-header panel-title">
                            <a role="button" data-toggle="collapse" href="#focusTypePanel" aria-expanded="true" aria-controls="focusTypePanel">
                                <i class="fa fa-bullseye"></i> <?php echo $view['translator']->trans('mautic.focus.form.type'); ?>
                            </a>
                        </h4>
                    </div>
                    <div id="focusTypePanel" class="panel-collapse collapse in" role="tabpanel">
                        <?php echo $view['form']->widget($form['type']); ?>
                        <ul class="list-group mb-0">
                            <li data-focus-type="form" class="focus-type list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="fa fa-2x fa-pencil-square-o text-primary"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.focus.form.type.form'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.focus.form.type.form_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>

                            <li class="focus-properties focus-form-properties list-group-item pl-sm pr-sm" style="display: none;"></li>

                            <li data-focus-type="notice" class="focus-type list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="fa fa-2x fa-bullhorn text-warning"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.focus.form.type.notice'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.focus.form.type.notice_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>

                            <li class="focus-properties focus-notice-properties list-group-item pl-sm pr-sm" style="display: none;"></li>

                            <li data-focus-type="link" class="focus-type list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="fa fa-2x fa-hand-o-right text-info"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.focus.form.type.link'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.focus.form.type.link_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>

                            <li class="focus-properties focus-link-properties list-group-item pl-sm pr-sm" style="display: none;"></li>
                        </ul>
                    </div>

                    <div class="hide" id="focusTypeProperties">
                        <?php echo $view['form']->row($form['properties']['animate']); ?>
                        <?php echo $view['form']->row($form['properties']['when']); ?>
                        <?php echo $view['form']->row($form['properties']['timeout']); ?>
                        <?php echo $view['form']->row($form['properties']['link_activation']); ?>
                        <?php echo $view['form']->row($form['properties']['frequency']); ?>
                        <div class="hidden-focus-type-notice">
                            <?php echo $view['form']->row($form['properties']['stop_after_conversion']); ?>
                        </div>
                    </div>
                </div>
                <!-- end focus type -->

                <!-- start focus type tab -->
                <div class="panel panel-default" id="focusStyle">
                    <div class="panel-heading">
                        <h4 class="panel-title focus-style-header">
                            <a role="button" data-toggle="collapse" href="#focusStylePanel" aria-expanded="true" aria-controls="focusStylePanel">
                                <i class="fa fa-desktop"></i> <?php echo $view['translator']->trans('mautic.focus.form.style'); ?>
                            </a>
                        </h4>
                    </div>
                    <div id="focusStylePanel" class="panel-collapse collapse" role="tabpanel">
                        <ul class="list-group mb-0">
                            <li data-focus-style="bar" class="focus-style visible-focus-style-bar list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="pl-2 fa fa-2x fa-minus text-primary"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.focus.style.bar'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.focus.style.bar_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>
                            <li class="focus-properties focus-bar-properties list-group-item pl-sm pr-sm" style="display: none;"></li>

                            <li data-focus-style="modal" class="focus-style visible-focus-style-modal list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="fa fa-2x fa-list-alt text-warning"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.focus.style.modal'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.focus.style.modal_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>
                            <li class="focus-properties focus-modal-properties list-group-item pl-sm pr-sm" style="display: none;"></li>

                            <li data-focus-style="notification" class="focus-style visible-focus-style-notification list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="pl-2 fa fa-2x fa-info-circle text-info"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans(
                                                'mautic.focus.style.notification'
                                            ); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.focus.style.notification_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>
                            <li class="focus-properties focus-notification-properties list-group-item pl-sm pr-sm" style="display: none;"></li>

                            <li data-focus-style="page" class="focus-style visible-focus-style-page list-group-item pl-sm pr-sm">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <i class="pl-2 fa fa-2x fa-square text-danger"></i>
                                    </div>
                                    <div class="col-xs-10">
                                        <h4 class="list-group-heading"><?php echo $view['translator']->trans('mautic.focus.style.page'); ?></h4>
                                        <p class="list-group-item-text small"><?php echo $view['translator']->trans(
                                                'mautic.focus.style.page_description'
                                            ); ?></p>
                                    </div>
                                </div>
                            </li>
                            <!-- <li class="focus-properties focus-page-properties list-group-item pl-sm pr-sm" style="display: none;"></li> -->
                        </ul>
                    </div>

                    <div class="hide" id="focusStyleProperties">
                        <!-- bar type properties -->
                        <div class="focus-hide visible-focus-style-bar">
                            <?php echo $view['form']->row($form['properties']['bar']['allow_hide']); ?>
                            <?php echo $view['form']->row($form['properties']['bar']['push_page']); ?>
                            <?php echo $view['form']->row($form['properties']['bar']['sticky']); ?>
                            <?php echo $view['form']->row($form['properties']['bar']['placement']); ?>
                            <?php echo $view['form']->row($form['properties']['bar']['size']); ?>
                        </div>

                        <!-- modal type properties -->
                        <div class="focus-hide visible-focus-style-modal">
                            <?php echo $view['form']->row($form['properties']['modal']['placement']); ?>
                        </div>

                        <!-- notifications type properties -->
                        <div class="focus-hide visible-focus-style-notification">
                            <?php echo $view['form']->row($form['properties']['notification']['placement']); ?>
                        </div>

                        <!-- page type properties -->
                        <!-- <div class="focus-hide visible-focus-style-page"></div> -->
                    </div>
                </div>
                <!-- end focus style -->

                <!-- start focus colors -->
                <div class="panel panel-default" id="focusColors">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" href="#focusColorsPanel" aria-expanded="true" aria-controls="focusColorsPanel">
                                <i class="fa fa-paint-brush"></i> <?php echo $view['translator']->trans('mautic.focus.tab.focus_colors'); ?>
                            </a>
                        </h4>
                    </div>
                    <div id="focusColorsPanel" class="panel-collapse collapse" role="tabpanel">
                        <div class="panel-body pa-xs">
                            <div class="row">
                                <div class="form-group col-xs-12 ">
                                    <?php echo $view['form']->label($form['properties']['colors']['primary']); ?>
                                    <div class="input-group">
                                        <?php echo $view['form']->widget($form['properties']['colors']['primary']); ?>
                                        <span class="input-group-btn">
                                        <button data-dropper="focus_properties_colors_primary" class="btn btn-default btn-nospin btn-dropper" type="button"><i class="fa fa-eyedropper"></i></button>
                                    </span>
                                    </div>
                                    <div class="mt-xs site-color-list hide" id="primary_site_colors"></div>
                                    <?php echo $view['form']->errors($form['properties']['colors']['primary']); ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-xs-12 ">
                                    <?php echo $view['form']->label($form['properties']['colors']['text']); ?>
                                    <div class="input-group">
                                        <?php echo $view['form']->widget($form['properties']['colors']['text']); ?>
                                        <span class="input-group-btn">
                                        <button data-dropper="focus_properties_colors_text" class="btn btn-default btn-nospin btn-dropper" type="button"><i class="fa fa-eyedropper"></i></button>
                                    </span>
                                    </div>
                                    <div class="mt-xs site-color-list hide" id="text_site_colors"></div>
                                    <?php echo $view['form']->errors($form['properties']['colors']['text']); ?>
                                </div>
                            </div>

                            <div class="hidden-focus-type-notice">

                                <div class="row">

                                    <div class="form-group col-xs-12 ">
                                        <?php echo $view['form']->label($form['properties']['colors']['button']); ?>
                                        <div class="input-group">
                                            <?php echo $view['form']->widget($form['properties']['colors']['button']); ?>
                                            <span class="input-group-btn">
                                        <button data-dropper="focus_properties_colors_button" class="btn btn-default btn-nospin btn-dropper" type="button"><i class="fa fa-eyedropper"></i></button>
                                    </span>
                                        </div>
                                        <div class="mt-xs site-color-list hide" id="button_site_colors"></div>
                                        <?php echo $view['form']->errors($form['properties']['colors']['button']); ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-xs-12 ">
                                        <?php echo $view['form']->label($form['properties']['colors']['button_text']); ?>
                                        <div class="input-group">
                                            <?php echo $view['form']->widget($form['properties']['colors']['button_text']); ?>
                                            <span class="input-group-btn">
                                        <button data-dropper="focus_properties_colors_button_text" class="btn btn-default btn-nospin btn-dropper" type="button"><i class="fa fa-eyedropper"></i></button>
                                    </span>
                                        </div>
                                        <div class="mt-xs site-color-list hide" id="button_text_site_colors"></div>
                                        <?php echo $view['form']->errors($form['properties']['colors']['button_text']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end focus colors -->

                <!-- start focus content -->
                <div class="panel panel-default" id="focusContent">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" href="#focusContentPanel" aria-expanded="true" aria-controls="focusContentPanel">
                                <i class="fa fa-newspaper-o"></i> <?php echo $view['translator']->trans('mautic.focus.tab.focus_content'); ?>
                            </a>
                        </h4>
                    </div>
                    <div id="focusContentPanel" class="panel-collapse collapse" role="tabpanel">
                        <div class="panel-body pa-xs">
                            <?php echo $view['form']->row($form['html_mode']); ?>
                            <?php echo $view['form']->row($form['editor']); ?>
                            <?php echo $view['form']->row($form['html']); ?>
                            <?php echo $view['form']->row($form['properties']['content']['headline']); ?>
                            <div class="hidden-focus-style-bar">
                                <?php echo $view['form']->row($form['properties']['content']['tagline']); ?>
                            </div>
                            <?php echo $view['form']->row($form['properties']['content']['font']); ?>

                            <!-- form type properties -->
                            <div class="focus-hide visible-focus-type-form">
                                <div class="col-sm-12" id="focusFormAlert" data-hide-on='{"focus_html_mode_0":"checked"}'>
                                    <div class="alert alert-info">
                                        <?php echo $view['translator']->trans('mautic.focus.form_token.instructions'); ?>
                                    </div>
                                </div>
                                <?php echo $view['form']->row($form['form']); ?>
                                <div style="margin-bottom: 50px;"></div>
                            </div>

                            <!-- link type properties -->
                            <div class="focus-hide visible-focus-type-link">
                                <?php echo $view['form']->row($form['properties']['content']['link_text']); ?>
                                <?php echo $view['form']->row($form['properties']['content']['link_url']); ?>
                                <?php echo $view['form']->row($form['properties']['content']['link_new_window']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end focus content -->

            </div>
        </div>
    </div>

<?php echo $view['form']->end($form); ?>