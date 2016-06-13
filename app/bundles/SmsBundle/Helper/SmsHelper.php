<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Helper;

use Doctrine\ORM\EntityManager;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;

class SmsHelper
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var PhoneNumberHelper
     */
    protected $phoneNumberHelper;

    /**
     * SmsHelper constructor.
     * 
     * @param EntityManager $em
     * @param LeadModel $leadModel
     */
    public function __construct(EntityManager $em, LeadModel $leadModel, PhoneNumberHelper $phoneNumberHelper)
    {
        $this->em = $em;
        $this->leadModel = $leadModel;
        $this->phoneNumberHelper = $phoneNumberHelper;
    }

    public function unsubscribe($number)
    {
        $number = $this->phoneNumberHelper->format($number, PhoneNumberFormat::E164);

        /** @var \Mautic\LeadBundle\Entity\LeadRepository $repo */
        $repo = $this->em->getRepository('MauticLeadBundle:Lead');

        $args = array(
            'filter' => array(
                'force' => array(
                    array(
                        'column' => 'mobile',
                        'expr' => 'eq',
                        'value' => $number
                    )
                )
            )
        );

        $leads = $repo->getEntities($args);

        if (! empty($leads)) {
            $lead = array_shift($leads);
        } else {
            // Try to find the lead based on the given phone number
            $args['filter']['force'][0]['column'] = 'phone';

            $leads = $repo->getEntities($args);

            if (! empty($leads)) {
                $lead = array_shift($leads);
            } else {
                return false;
            }
        }

        return $this->leadModel->addDncForLead($lead, 'sms', null, DoNotContact::UNSUBSCRIBED);
    }
}