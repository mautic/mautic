<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticInstallBundle:Install:content.html.php');
}

$header = $view['translator']->trans('mautic.install.install.heading.check.environment');
$view['slots']->set("headerTitle", $header);
?>

<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">
			<?php echo $header; ?>
		</h3>
	</div>
	<div class="panel-body">
        <?php if (count($majors)) : ?>
        <h4><?php echo $view['translator']->trans('mautic.install.install.heading.major.problems'); ?></h4>
        <p><?php echo $view['translator']->trans('mautic.install.install.sentence.major.problems', array('%majors%' => count($majors))); ?></p>
        <ol>
            <?php foreach ($majors as $message) : ?>
                <?php switch ($message) :
                    case 'mautic.install.minimum.php.version': ?>
                        <li><?php echo $view['translator']->trans($message, array('%minimum%' => '5.3.7', '%installed' => PHP_VERSION)); ?></li>
                        <?php break;
                    case 'mautic.install.cache.unwritable': ?>
                        <li><?php echo $view['translator']->trans('mautic.install.directory.unwritable', array('%path%' => $appRoot . '/cache')); ?></li>
                        <?php break;
                    case 'mautic.install.config.unwritable': ?>
                        <li><?php echo $view['translator']->trans($message, array('%path%' => $appRoot . '/config/local.php')); ?></li>
                        <?php break;
                    case 'mautic.install.logs.unwritable': ?>
                        <li><?php echo $view['translator']->trans('mautic.install.directory.unwritable', array('%path%' => $appRoot . '/logs')); ?></li>
                        <?php break;
                    case 'mautic.install.apc.version': ?>
                        <?php $minAPCverison = version_compare(PHP_VERSION, '5.4.0', '>=') ? '3.1.13' : '3.0.17'; ?>
                        <li><?php echo $view['translator']->trans($message, array('%minapc%' => $minAPCverison, '%currentapc%' => phpversion('apc'))); ?></li>
                        <?php break;
                    default: ?>
                        <li><?php echo $view['translator']->trans($message); ?></li>
                        <?php break; ?>
                <?php endswitch; ?>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>
        <?php if (count($minors)) : ?>
        <h4><?php echo $view['translator']->trans('mautic.install.install.heading.minor.problems'); ?></h4>
        <p><?php echo $view['translator']->trans('mautic.install.install.sentence.minor.problems'); ?></p>
        <ol>
            <?php foreach ($minors as $message) : ?>
                <?php switch ($message) :
                    case 'mautic.install.pcre.version': ?>
                        <li><?php echo $view['translator']->trans($message, array('%pcreversion%' => (float) PCRE_VERSION)); ?></li>
                        <?php break;
                    default: ?>
                        <li><?php echo $view['translator']->trans($message); ?></li>
                        <?php break; ?>
                <?php endswitch; ?>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>
        <?php if (!count($majors)) : ?>
        <h4><?php echo $view['translator']->trans('mautic.install.install.heading.ready'); ?></h4>
        <p><?php echo $view['translator']->trans('mautic.install.install.sentence.ready'); ?></p>
        <?php echo $view['form']->form($form); ?>
        <?php endif; ?>
    </div>
</div>
