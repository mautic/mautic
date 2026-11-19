<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailForward;
use Mautic\EmailBundle\Entity\EmailForwardRepository;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Exception\ForwardLimitReachedException;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;

/**
 * Sends a copy of an already sent email to an address its recipient chose.
 *
 * The copy is an internal send: no contact, no stat, no tracking pixel, no tracked links,
 * and the builder tokens that would point back at the original recipient are left out.
 */
final readonly class ForwardedEmailSender
{
    public const CONFIG_LIMIT = 'email_forward_limit';

    public function __construct(
        private MailHelper $mailHelper,
        private EmailValidator $emailValidator,
        private LeadRepository $leadRepository,
        private DoNotContactModel $doNotContact,
        private EmailForwardRepository $emailForwardRepository,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    /**
     * Whether the sent email behind this stat can still be forwarded at all.
     */
    public function isAvailable(Stat $stat): bool
    {
        $email = $stat->getEmail();

        return $email instanceof Email && $email->isPublished();
    }

    public function isLimitReached(Stat $stat): bool
    {
        $limit = (int) $this->coreParametersHelper->get(self::CONFIG_LIMIT);

        return $limit > 0 && $this->emailForwardRepository->countByStat($stat) >= $limit;
    }

    /**
     * An address that belongs to a contact who must not receive email gets no copy, but that is not told to the
     * sender: saying so would let anybody with a forward link find out who unsubscribed. It counts as a forward.
     *
     * @throws InvalidEmailException        when the address is not a valid email address
     * @throws ForwardLimitReachedException when this sent email has been forwarded as often as the configuration allows
     * @throws EmailCouldNotBeSentException when the mailer refused the copy
     */
    public function send(Stat $stat, string $toAddress): void
    {
        $email = $stat->getEmail();
        if (!$email instanceof Email) {
            throw new EmailCouldNotBeSentException('The sent email no longer exists.');
        }

        $this->emailValidator->validate($toAddress);

        if ($this->isLimitReached($stat)) {
            throw new ForwardLimitReachedException();
        }

        if (!$this->isContactable($toAddress)) {
            $this->emailForwardRepository->saveEntity(new EmailForward($stat));

            return;
        }

        $mailer = $this->mailHelper->getMailer();
        $mailer->setLead(null, true);
        $mailer->setForwardedCopy(true);
        $mailer->setEmail($email, false, [], true);

        if (!$mailer->setTo($toAddress)) {
            throw new InvalidEmailException($toAddress, implode('; ', $mailer->getErrors()));
        }

        if (!$mailer->send(true)) {
            $errors = $mailer->getErrors();
            unset($errors['failures']);

            throw new EmailCouldNotBeSentException(implode('; ', $errors));
        }

        $this->emailForwardRepository->saveEntity(new EmailForward($stat));
    }

    /**
     * A copy must not reach somebody who told Mautic not to email them.
     */
    private function isContactable(string $toAddress): bool
    {
        /** @var Lead[] $contacts */
        $contacts = $this->leadRepository->getContactsByEmail($toAddress);

        foreach ($contacts as $contact) {
            if (DoNotContact::IS_CONTACTABLE !== $this->doNotContact->isContactable($contact, 'email')) {
                return false;
            }
        }

        return true;
    }
}
