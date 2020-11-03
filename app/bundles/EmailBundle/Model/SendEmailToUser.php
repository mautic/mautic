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

use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Exception\InvalidValueException;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\OptionsAccessor\EmailToUserAccessor;
use Mautic\LeadBundle\DataObject\ContactFieldToken;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Hash\UserHash;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SendEmailToUser
{
    /**
     * @var EmailModel
     */
    private $emailModel;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EmailModel $emailModel, EventDispatcherInterface $dispatcher)
    {
        $this->emailModel = $emailModel;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @throws EmailCouldNotBeSentException
     * @throws ORMException
     */
    public function sendEmailToUsers(array $config, Lead $lead)
    {
        $emailToUserAccessor = new EmailToUserAccessor($config);

        $email = $this->emailModel->getEntity($emailToUserAccessor->getEmailID());

        if (!$email || !$email->isPublished()) {
            throw new EmailCouldNotBeSentException('Email not found or published');
        }

        $leadCredentials = $lead->getProfileFields();

        $to  = $emailToUserAccessor->getToFormatted();
        $cc  = $emailToUserAccessor->getCcFormatted();
        $bcc = $emailToUserAccessor->getBccFormatted();

        $to  = $this->replaceTokens($to, $lead);
        $cc  = $this->replaceTokens($cc, $lead);
        $bcc = $this->replaceTokens($bcc, $lead);

        $users  = $emailToUserAccessor->getUserIdsToSend($lead->getOwner());
        $idHash = UserHash::getFakeUserHash();
        $tokens = $this->emailModel->dispatchEmailSendEvent($email, $leadCredentials, $idHash)->getTokens();
        $errors = $this->emailModel->sendEmailToUser($email, $users, $leadCredentials, $tokens, [], false, $to, $cc, $bcc);

        if ($errors) {
            throw new EmailCouldNotBeSentException(implode(', ', $errors));
        }
    }

    private function replaceTokens(array $emailAddressesOrTokens, Lead $lead): array
    {
        return array_map($this->makeTokenReplacerCallback($lead), $emailAddressesOrTokens);
    }

    private function replaceToken(string $token, Lead $lead): string
    {
        $tokenEvent = new TokenReplacementEvent($token, $lead);
        $this->dispatcher->dispatch(EmailEvents::ON_EMAIL_ADDRESS_TOKEN_REPLACEMENT, $tokenEvent);

        return $tokenEvent->getContent();
    }

    private function makeTokenReplacerCallback(Lead $lead): callable
    {
        return function (string $emailAddressOrToken) use ($lead) {
            try {
                $contactToken = new ContactFieldToken($emailAddressOrToken);

                return $this->replaceToken($contactToken->getFullToken(), $lead);
            } catch (InvalidValueException $e) {
                // Not a token, do nothing to the value. It should be a valid email address already due to validation rules on forms.
                return $emailAddressOrToken;
            }
        };
    }
}
