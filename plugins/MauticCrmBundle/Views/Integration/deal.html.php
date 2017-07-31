<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($deal instanceof \MauticPlugin\MauticCrmBundle\Entity\PipedriveDeal) {
  $id     = $deal->getId();
  $lead   = $deal->getLead();
  $title  = $deal->getTitle();

} else {
  $id     = $deal['id'];
  $lead   = $deal['lead'];
  $title  = $deal['title'];
}

$icon = 'fa-forward';

?>
<li id="Deal<?php echo $id; ?>">
  <div class="panel ">
    <div class="panel-body np box-layout">
      <div class="height-auto icon bdr-r bg-dark-xs col-xs-1 text-center">
        <h3><i class="fa fa-lg fa-fw <?php echo $icon; ?>"></i></h3>
      </div>
      <div class="media-body col-xs-11 pa-10">
        <?php echo $title; ?>
      </div>
    </div>
  </div>
</li>
