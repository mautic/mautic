<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'campaign');

$header = ($entity->getId()) ?
    $view['translator']->trans('mautic.campaign.header.edit',
        array('%name%' => $view['translator']->trans($entity->getName()))) :
    $view['translator']->trans('mautic.campaign.header.new');
$view['slots']->set("headerTitle", $header);
?>

<ul class="nav nav-tabs" role="tablist">
    <li class="active"><a href="#details" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.campaign.form.panel.details'); ?></a></li>
    <li><a href="#events" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.campaign.form.panel.events'); ?></a></li>
</ul>

<?php echo $view['form']->start($form); ?>
<div class="tab-content">
    <div class="tab-pane active pa-md" id="details">

        <?php
        echo $view['form']->row($form['name']);
        echo $view['form']->row($form['description']);
        echo $view['form']->row($form['category_lookup']);
        echo $view['form']->row($form['category']);
        echo $view['form']->row($form['isPublished']);
        echo $view['form']->row($form['publishUp']);
        echo $view['form']->row($form['publishDown']);
        ?>
    </div>
    <div class="tab-pane pa-md" id="events">
        <div class="row">
            <div class="col-md-8">
                <?php if (!$hasEvents = count($campaignEvents)): ?>
                <h3 id='campaign-event-placeholder'><?php echo $view['translator']->trans('mautic.campaign.form.addevent'); ?></h3>
                <?php endif; ?>
                <ol id="campaignEvents">
                <?php
                if ($hasEvents):
                    echo $view->render('MauticCampaignBundle:CampaignBuilder:events.html.php', array(
                        'events'        => $campaignEvents,
                        'inForm'        => true,
                        'deletedEvents' => $deletedEvents,
                        'eventSettings' => $eventSettings
                    ));
                endif;
                ?>
                </ol>
            </div>
            <div class="col-md-4">
                <?php echo $view->render('MauticCampaignBundle:CampaignBuilder:components.html.php', array(
                    'eventSettings' => $eventSettings,
                    'tmpl'          => $tmpl
                )); ?>
            </div>
        </div>
    </div>

    <?php echo $view['form']->end($form); ?>
    <?php
    $view['slots']->append('modal', $this->render('MauticCoreBundle:Helper:modal.html.php', array(
        'id'     => 'campaignEventModal',
        'header' => $view['translator']->trans('mautic.campaign.form.modalheader'),
    )));
    ?>
</div>