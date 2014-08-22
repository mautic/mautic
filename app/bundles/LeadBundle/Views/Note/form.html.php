<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view['slots']->set('mauticContent', 'leadnote');
$userId = $form->vars['data']->getId();
if (!empty($userId)) {
    $header = $view['translator']->trans('mautic.lead.note.header.edit');
} else {
    $header = $view['translator']->trans('mautic.lead.note.header.new');
}
?>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">
		<span aria-hidden="true">&times;</span>
		<span class="sr-only"><?php echo $view['translator']->trans('mautic.core.close'); ?></span>
	</button>
	<h4 class="modal-title">
		<?php echo $header; ?>
	</h4>
</div>
<div class="modal-body">
	<?php echo $view['form']->start($form); ?>
	<?php echo $view['form']->row($form['text']); ?>
	<?php echo $view['form']->end($form); ?>
	<div class="footer-margin"></div>
</div>
