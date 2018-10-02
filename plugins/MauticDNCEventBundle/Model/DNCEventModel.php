<?php

namespace MauticPlugin\MauticDNCEventBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\DoNotContact;

class DNCEventModel extends FormModel
{
    public function addDoNotContact($lead, $options)
    {
        $leadModel = $this->factory->getModel('lead');

        $res = $leadModel->addDncForLead($lead, $options['channel'], $options['comments'], DoNotContact::UNSUBSCRIBED, true);

        return true;
    }

    public function removeDoNotContact($lead, $options)
    {
        $leadModel = $this->factory->getModel('lead');

        $res = $leadModel->removeDncForLead($lead, $options['channel'], $options['comments'], DoNotContact::UNSUBSCRIBED, true);

        return true;
    }
}
