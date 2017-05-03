<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!isset($form->children['channel'])) {
    return;
}

$channel        = $form->children['channel']->vars['data'];
$hide           = empty($form->children['isEnabled']->vars['data']);
$channelContent = $view['content']->getCustomContent('channel.right', $mauticTemplateVars);
$leftCol        = ($channelContent) ? 6 : 12;
$enableCol      = ($channelContent) ? '' : 'col-md-2';
$propsCol       = ($channelContent) ? '' : 'col-md-10';
?>

<?php echo $view['form']->row($form->children['channel']); ?>
<?php echo $view['form']->errors($form); ?>
<div class="row">
    <div class="col-md-<?php echo $leftCol; ?>">
        <div class="<?php echo $enableCol; ?>">
            <?php echo $view['form']->row($form->children['isEnabled']); ?>
        </div>
        <div class="<?php echo $propsCol; ?>">
            <div class="message_channel_properties_<?php echo $channel; ?><?php if ($hide): echo ' hide'; endif; ?>">
                <?php if (isset($form->children['channelId'])): ?>
                    <?php echo $view['form']->row($form->children['channelId']); ?>
                <?php endif; ?>

                <?php if (!empty($form->children['properties'])): ?>
                    <?php echo $view['form']->row($form->children['properties']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($channelContent): ?>
    <div class="col-md-6">
        <?php echo $channelContent; ?>
    </div>
    <?php endif ?>
</div>