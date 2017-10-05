<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$form       = $event['extra']['form'];
$page       = $event['extra']['page'];
$submission = $event['extra']['submission'];
$results    = $submission->getResults();

?>

<?php if (isset($event['extra'])) : ?>
<dl class="dl-horizontal">
<?php if (isset($link)) : ?>
	<dt><?php echo $view['translator']->trans('mautic.core.source'); ?></dt>
	<dd><?php echo $link; ?></dd>
<?php endif; ?>
<?php if ($form->getDescription()) : ?>
	<dt><?php echo $view['translator']->trans('mautic.core.description'); ?></dt>
	<dd><?php echo $form->getDescription(); ?></dd>
    <?php if (isset($event['extra'])) : ?>
        <?php if ($descr = $form->getDescription()): ?>
        <p><?php echo $descr; ?></p>
    <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
	<dt><?php echo $view['translator']->trans('mautic.form.result.thead.referrer'); ?></dt>
	<dd><?php echo $view['assets']->makeLinks($submission->getReferer()); ?></dd>
<?php if (is_array($results)) : ?>
	<?php foreach ($form->getFields() as $field) : ?>
		<?php if (array_key_exists($field->getAlias(), $results) && $results[$field->getAlias()] != ''
&& $results[$field->getAlias()] != null
&& $results[$field->getAlias()] != []
) : ?>
			<dt><?php echo $field->getLabel(); ?></dt>
			<dd>
                <?php if ($field->isFileType()) : ?>
                <a href="<?php echo $view['router']->path('mautic_form_file_download', ['submissionId' => $submission->getId(), 'field' => $field->getAlias()]); ?>">
                    <?php echo $results[$field->getAlias()]; ?>
                </a>
                <?php else : ?>
                    <?php echo $results[$field->getAlias()]; ?>
                <?php endif; ?>
            </dd>
		<?php endif; ?>
	<?php endforeach; ?>
<?php endif; ?>
</dl>
<?php endif; ?>
