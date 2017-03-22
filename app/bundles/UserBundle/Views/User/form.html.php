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
$view['slots']->set('mauticContent', 'user');
$userId = $form->vars['data']->getId();
if (!empty($userId)) {
    $user   = $form->vars['data']->getName();
    $header = $view['translator']->trans('mautic.user.user.header.edit', ['%name%' => $user]);
} else {
    $header = $view['translator']->trans('mautic.user.user.header.new');
}
$view['slots']->set('headerTitle', $header);
?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <?php echo $view['form']->start($form); ?>
    <div class="col-md-9 bg-auto height-auto bdr-r">
		<div class="pa-md">
			<div class="form-group mb-0">
			    <div class="row">
                    <div class="col-sm-6<?php echo (count($form['firstName']->vars['errors'])) ? ' has-error' : ''; ?>">
                    	<label class="control-label mb-xs"><?php echo $view['form']->label($form['firstName']); ?></label>
			            <?php echo $view['form']->widget($form['firstName'], ['attr' => ['placeholder' => $form['firstName']->vars['label']]]); ?>
                        <?php echo $view['form']->errors($form['firstName']); ?>
			        </div>
                    <div class="col-sm-6<?php echo (count($form['lastName']->vars['errors'])) ? ' has-error' : ''; ?>">
                        <label class="control-label mb-xs"><?php echo $view['form']->label($form['lastName']); ?></label>
			            <?php echo $view['form']->widget($form['lastName'], ['attr' => ['placeholder' => $form['lastName']->vars['label']]]); ?>
                        <?php echo $view['form']->errors($form['lastName']); ?>
			        </div>
			    </div>
			</div>
			<hr class="mnr-md mnl-md">

			<div class="form-group mb-0">
			    <div class="row">
			        <div class="col-sm-6<?php echo (count($form['role']->vars['errors'])) ? ' has-error' : ''; ?>">
			        	<label class="control-label mb-xs"><?php echo $view['form']->label($form['role']); ?></label>
			            <?php echo $view['form']->widget($form['role'], ['attr' => ['placeholder' => $form['role']->vars['label']]]); ?>
                        <?php echo $view['form']->errors($form['role']); ?>
			        </div>
			        <div class="col-sm-6<?php echo (count($form['position']->vars['errors'])) ? ' has-error' : ''; ?>">
				    	<label class="control-label mb-xs"><?php echo $view['form']->label($form['position']); ?></label>
			            <?php echo $view['form']->widget($form['position'], ['attr' => ['placeholder' => $form['position']->vars['label']]]); ?>
                        <?php echo $view['form']->errors($form['position']); ?>
			        </div>
			    </div>
			</div>
			<hr class="mnr-md mnl-md">

            <div class="form-group mb-0">
                <div class="row">
                    <div class="col-sm-6<?php echo (count($form['signature']->vars['errors'])) ? ' has-error' : ''; ?>">
                        <label class="control-label mb-xs"><?php echo $view['form']->label($form['signature']); ?></label>
                        <?php echo $view['form']->widget($form['signature'], ['attr' => ['placeholder' => $form['signature']->vars['label']]]); ?>
                        <?php echo $view['form']->errors($form['signature']); ?>
                    </div>
                </div>
            </div>
            <hr class="mnr-md mnl-md">

			<div class="panel panel-default form-group mb-0">
				<div class="panel-body">
				    <div class="row">
				        <div class="col-sm-6">
				        	<div class="form-group col-xs-12<?php echo (count($form['username']->vars['errors'])) ? ' has-error' : ''; ?>">
				        		<label class="control-label mb-xs"><?php echo $view['form']->label($form['username']); ?></label>
				            	<?php echo $view['form']->widget($form['username'], ['attr' => ['placeholder' => $form['username']->vars['label']]]); ?>
                                <?php echo $view['form']->errors($form['username']); ?>
				            </div>
							<div class="form-group col-xs-12<?php echo (count($form['email']->vars['errors'])) ? ' has-error' : ''; ?>">
					    		<label class="control-label mb-xs"><?php echo $view['form']->label($form['email']); ?></label>
				            	<?php echo $view['form']->widget($form['email'], ['attr' => ['placeholder' => $form['email']->vars['label']]]); ?>
                                <?php echo $view['form']->errors($form['email']); ?>
				            </div>
				        </div>
				        <div class="col-sm-6">
				            <?php echo $view['form']->widget($form['plainPassword'], ['attr' => ['placeholder' => $form['plainPassword']->vars['label']]]); ?>
				        </div>
				    </div>
				</div>
			</div>
			<hr class="mnr-md mnl-md">

		</div>
	</div>
 	<div class="col-md-3 bg-white height-auto">
		<div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->rest($form); ?>
		</div>
	</div>
    <?php echo $view['form']->end($form); ?>
</div>