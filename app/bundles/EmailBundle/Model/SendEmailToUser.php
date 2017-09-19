<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Model;

use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\OptionsAccessor\EmailToUserAccessor;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Hash\UserHash;

class SendEmailToUser
{
    /** @var EmailModel */
    private $emailModel;

    public function __construct(EmailModel $emailModel)
    {
        $this->emailModel = $emailModel;
    }

    /**
     * @param array     $config
     * @param Lead      $lead
     *
     * @throws EmailCouldNotBeSentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendEmailToUsers(array $config, Lead $lead)
    {
        $emailToUserAccessor = new EmailToUserAccessor($config);

        $email = $this->emailModel->getEntity($emailToUserAccessor->getEmailID());

        if (!$email || !$email->isPublished()) {
            throw new EmailCouldNotBeSentException('Email not found or published');
        }

        $leadCredentials = $lead->getProfileFields();

        $to = $emailToUserAccessor->getToFormatted();
        $cc = $emailToUserAccessor->getCcFormatted();
        $bcc = $emailToUserAccessor->getBccFormatted();

        $owner = $lead->getOwner();
        $users = $emailToUserAccessor->getUserIdsToSend($owner);

        $idHash = UserHash::getFakeUserHash();
        $tokens = $this->emailModel->dispatchEmailSendEvent($email, $leadCredentials, $idHash)->getTokens();
        $errors = $this->emailModel->sendEmailToUser($email, $users, $leadCredentials, $tokens, [], false, $to, $cc, $bcc);

        if ($errors) {
            throw new EmailCouldNotBeSentException(implode(', ', $errors));
        }
    }
}
