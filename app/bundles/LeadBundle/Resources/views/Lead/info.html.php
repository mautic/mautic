<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php /*
 <img class="img img-responsive" src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($fields['email']))); ?>?&s=250" />
 <hr />
<strong>About</strong><br />
<p><em>3 kids and counting, 1 wife and holding.</em></p>

<br />

<address>
    <strong>Twitter, Inc.</strong><br>
    795 Folsom Ave, Suite 600<br>
    San Francisco, CA 94107<br>
    <abbr title="Phone">P:</abbr><span> <span id="gc-number-0" class="gc-cs-link" title="Call with Google Voice">(123) 456-7890</span>
</span>
</address>

<span class="label label-teal"><span class="fa fa-twitter"></span></span> <a href="#">@dbhurley</a><br />
<span class="label label-teal"><span class="fa fa-facebook"></span></span> <a href="#">@dbhurley</a><br />
<span class="label label-teal"><span class="fa fa-linkedin"></span></span> <a href="#">@dbhurley</a><br />
<span class="label label-teal"><span class="fa fa-google"></span></span> <a href="#">@dbhurley</a><br />

 */
?>

<div class="panel panel-success">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.lead.lead.header.leadinfo'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="col-sm-2">
            <img class="img img-responsive"
                 src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($fields['email']['value']))); ?>?&s=250" />
        </div>
        <div class="col-sm-10">
            <?php if ($lead->getOwner()): ?>
                <div class="row">
                    <div class="col-xs-3 field-label">
                        <?php echo $view['translator']->trans('mautic.lead.lead.field.owner'); ?>
                    </div>
                    <div class="col-xs-9 field-value">
                        <a href="<?php echo $view['router']->generate('mautic_user_action', array(
                            'objectAction' => 'contact',
                            'objectId'     => $lead->getOwner()->getId(),
                            'entity'       => 'lead',
                            'id'           => $lead->getId(),
                            'returnUrl'    => $view['router']->generate('mautic_lead_action', array(
                                'objectAction' => 'view',
                                'objectId'     => $lead->getId()
                            ))
                            )); ?>">
                        <?php echo $lead->getOwner()->getName(); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            <?php foreach ($fields as $field): ?>
            <?php if (empty($field['value'])) continue; ?>
                <div class="row">
                    <div class="col-xs-3 field-label">
                        <?php echo $field['label']; ?>
                    </div>
                    <div class="col-xs-9 field-value">
                        <?php echo $view->render('MauticLeadBundle:Lead:info_value.html.php', array(
                            'value'       => $field['value'],
                            'name'        => $field['alias'],
                            'type'        => $field['type'],
                            'dateFormats' => $dateFormats
                        )); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        </div>
    </div>
</div>