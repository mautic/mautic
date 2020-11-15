<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($dnc && \Mautic\LeadBundle\Entity\DoNotContact::BOUNCED === $dnc->getReason()) {
    echo '<div class="alert alert-warning">'.$view['translator']->trans('mautic.lead.do.not.contact_bounced').': '.$dnc->getComments().'</div>';
} else {
    echo $view['form']->start($form);

    echo $view['form']->row($form['fromname']);
    echo $view['form']->row($form['from']);
    echo $view['form']->row($form['subject']);
    echo $view['form']->row($form['body']);
    echo $view['form']->row($form['templates']);

    echo $view['form']->end($form);
}
