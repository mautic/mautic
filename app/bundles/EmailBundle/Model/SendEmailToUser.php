<?php

namespace Mautic\EmailBundle\Model;

use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Exception\InvalidValueException;
use Mautic\CoreBundle\Exception\RecordException;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\OptionsAccessor\EmailToUserAccessor;
use Mautic\LeadBundle\DataObject\ContactFieldToken;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Exception\InvalidContactFieldTokenException;
use Mautic\LeadBundle\Validator\CustomFieldValidator;
use Mautic\UserBundle\Hash\UserHash;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SendEmailToUser
{
    public function __construct(
        private EmailModel $emailModel,
        private EventDispatcherInterface $dispatcher,
        private CustomFieldValidator $customFieldValidator,
        private EmailValidator $emailValidator,
    ) {
    }

    /**
     * @throws EmailCouldNotBeSentException
     * @throws ORMException
     */
    public function sendEmailToUsers(array $config, Lead $lead): void
    {
        $emailToUserAccessor = new EmailToUserAccessor($config);

        $email = $this->emailModel->getEntity($emailToUserAccessor->getEmailID());

        if (!$email || !$email->isPublished()) {
            throw new EmailCouldNotBeSentException('Email not found or published');
        }

        $leadCredentials = $lead->getProfileFields();

        $to  = ArrayHelper::removeEmptyValues($this->replaceTokens($emailToUserAccessor->getToFormatted(), $lead));
        $cc  = ArrayHelper::removeEmptyValues($this->replaceTokens($emailToUserAccessor->getCcFormatted(), $lead));
        $bcc = ArrayHelper::removeEmptyValues($this->replaceTokens($emailToUserAccessor->getBccFormatted(), $lead));

        $users  = $emailToUserAccessor->getUserIdsToSend($lead->getOwner());
        $idHash = UserHash::getFakeUserHash();
        $tokens = $this->emailModel->dispatchEmailSendEvent($email, $leadCredentials, $idHash)->getTokens();
        $errors = $this->emailModel->sendEmailToUser($email, $users, $leadCredentials, $tokens, [], false, $to, $cc, $bcc);

        if ($errors) {
            throw new EmailCouldNotBeSentException(implode(', ', $errors));
        }
    }

    /**
     * @param string[] $emailAddressesOrTokens
     *
     * @return string[]
     */
    private function replaceTokens(array $emailAddressesOrTokens, Lead $lead): array
    {
        return array_map($this->makeTokenReplacerCallback($lead), $emailAddressesOrTokens);
    }

    private function makeTokenReplacerCallback(Lead $lead): callable
    {
        return function (string $emailAddressOrToken) use ($lead): string {
            try {
                $contactFieldToken = new ContactFieldToken($emailAddressOrToken);
            } catch (InvalidContactFieldTokenException) {
                try {
                    $this->emailValidator->validate($emailAddressOrToken);

                    return $emailAddressOrToken;
                } catch (InvalidEmailException) {
                    return '';
                }
            }

            // The values are validated on form save.
            // But ensure the custom field is still valid on email send before asking for the replacement value.
            try {
                // Validate that the contact field exists and is type of email.
                $this->customFieldValidator->validateFieldType($contactFieldToken->getFieldAlias(), 'email');

                return $this->replaceToken($contactFieldToken->getFullToken(), $lead);
            } catch (InvalidValueException|RecordException) {
                // If the field does not exist or is not type of email then use the default value.
                return (string) $contactFieldToken->getDefaultValue();
            }
        };
    }

    private function replaceToken(string $token, Lead $lead): string
    {
        $tokenEvent = new TokenReplacementEvent($token, $lead);
        $this->dispatcher->dispatch($tokenEvent, EmailEvents::ON_EMAIL_ADDRESS_TOKEN_REPLACEMENT);

        return $tokenEvent->getContent();
    }
}
