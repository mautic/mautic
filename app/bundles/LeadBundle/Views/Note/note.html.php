<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($note instanceof \Mautic\LeadBundle\Entity\LeadNote) {
    $id        = $note->getId();
    $text      = $note->getText();
    $date      = $note->getDateAdded();
    $createdBy = $note->getCreatedBy();
    $author    = $createdBy->getFirstName() . ' ' . $createdBy->getLastName();
} else {
    $id     = $note['id'];
    $text   = $note['text'];
    $date   = $note['dateAdded'];
    $author = $note['createdBy']['firstName'] . ' ' . $note['createdBy']['lastName'];
}
?>
<li class="featured" id="LeadNote<?php echo $id; ?>">
    <div class="figure"><!--<span class="fa fa-check"></span>--></div>
    <div class="panel ">
        <div class="panel-body">
            <p class="mb-sm">
                <?php echo $text; ?>
            </p>
            <div><i class="fa fa-clock-o fa-fw"></i><span class="small"><?php echo $view['date']->toFullConcat($date); ?></span></div>
            <div><i class="fa fa-user fa-fw"></i><span class="small"><?php echo $author; ?></span></div>
        </div>
    </div>
</li>