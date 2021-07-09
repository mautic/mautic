<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\DTO\AddressDTO;
use Mautic\EmailBundle\Helper\Exception\OwnerNotFoundException;
use Mautic\EmailBundle\Helper\Exception\TokenNotFoundOrEmptyException;
use Mautic\LeadBundle\Entity\LeadRepository;

class FromEmailHelper
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var array
     */
    private $owners = [];

    /**
     * @var AddressDTO|null
     */
    private $defaultFrom;

    /**
     * @var array|null
     */
    private $lastOwner;

    /**
     * FromEmailHelper constructor.
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, LeadRepository $leadRepository)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->leadRepository       = $leadRepository;
    }

    public function setDefaultFromArray(array $from)
    {
        $this->defaultFrom = new AddressDTO($from);
    }

    /**
     * @return array
     */
    public function getFromAddressArrayConsideringOwner(array $from, array $contact = null, Email $email = null)
    {
        $address = new AddressDTO($from);

        // Reset last owner
        $this->lastOwner = null;

        // Check for token
        if ($address->isEmailTokenized() || $address->isNameTokenized()) {
            return $this->getEmailArrayFromToken($address, $contact, true, $email);
        }

        if (!$contact) {
            return $from;
        }

        try {
            return $this->getFromEmailArrayAsOwner($contact, $email);
        } catch (OwnerNotFoundException $exception) {
            return $from;
        }
    }

    /**
     * @return array
     */
    public function getFromAddressArray(array $from, array $contact = null)
    {
        $address = new AddressDTO($from);

        // Reset last owner
        $this->lastOwner = null;

        // Check for token
        if ($address->isEmailTokenized() || $address->isNameTokenized()) {
            return $this->getEmailArrayFromToken($address, $contact, false);
        }

        return $from;
    }

    /**
     * @param int $userId
     *
     * @return array
     *
     * @throws OwnerNotFoundException
     */
    public function getContactOwner($userId, Email $email = null)
    {
        // Reset last owner
        $this->lastOwner = null;

        if ($email) {
            if (!$email->getUseOwnerAsMailer()) {
                throw new OwnerNotFoundException("mailer_is_owner is not enabled for this email ({$email->getId()})");
            }
        } elseif (!$this->coreParametersHelper->get('mailer_is_owner')) {
            throw new OwnerNotFoundException('mailer_is_owner is not enabled in global configuration');
        }

        if (isset($this->owners[$userId])) {
            return $this->owners[$userId];
        }

        if ($owner = $this->leadRepository->getLeadOwner($userId)) {
            $this->owners[$userId] = $this->lastOwner = $owner;

            return $owner;
        }

        throw new OwnerNotFoundException();
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        if (!$this->lastOwner) {
            return '';
        }

        $owner = $this->lastOwner;

        return $this->replaceSignatureTokens($owner['signature'], $owner);
    }

    /**
     * @param string $signature
     *
     * @return string
     */
    private function replaceSignatureTokens($signature, array $owner)
    {
        $signature = nl2br($signature);
        $signature = str_replace('|FROM_NAME|', $owner['first_name'].' '.$owner['last_name'], $signature);

        foreach ($owner as $key => $value) {
            $token     = sprintf('|USER_%s|', strtoupper($key));
            $signature = str_replace($token, $value, $signature);
        }

        return EmojiHelper::toHtml($signature);
    }

    /**
     * @return array
     */
    private function getDefaultFromArray()
    {
        if ($this->defaultFrom) {
            return $this->defaultFrom->getAddressArray();
        }

        return $this->getSystemDefaultFrom()->getAddressArray();
    }

    /**
     * @return AddressDTO
     */
    private function getSystemDefaultFrom()
    {
        $email = $this->coreParametersHelper->get('mailer_from_email');
        $name  = $this->coreParametersHelper->get('mailer_from_name');
        $name  = $name ? $name : null;

        return new AddressDTO([$email => $name]);
    }

    /**
     * @param array $contact
     * @param bool  $asOwner
     *
     * @return array
     */
    private function getEmailArrayFromToken(AddressDTO $address, array $contact = null, $asOwner = true, Email $email = null)
    {
        try {
            if (!$contact) {
                throw new TokenNotFoundOrEmptyException();
            }

            $name = $address->isNameTokenized() ? $address->getNameTokenValue($contact) : $address->getName();
        } catch (TokenNotFoundOrEmptyException $exception) {
            $name = $this->defaultFrom ? $this->defaultFrom->getName() : $this->getSystemDefaultFrom()->getName();
        }

        try {
            if (!$contact) {
                throw new TokenNotFoundOrEmptyException();
            }

            $email = $address->isEmailTokenized() ? $address->getEmailTokenValue($contact) : $address->getEmail();

            return [$email => $name];
        } catch (TokenNotFoundOrEmptyException $exception) {
            if ($contact && $asOwner) {
                try {
                    return $this->getFromEmailArrayAsOwner($contact, $email);
                } catch (OwnerNotFoundException $exception) {
                }
            }

            return $this->getDefaultFromArray();
        }
    }

    /**
     * @return array
     *
     * @throws OwnerNotFoundException
     */
    private function getFromEmailArrayAsOwner(array $contact, Email $email = null)
    {
        if (empty($contact['owner_id'])) {
            throw new OwnerNotFoundException();
        }

        $owner      = $this->getContactOwner($contact['owner_id'], $email);
        $ownerEmail = $owner['email'];
        $ownerName  = sprintf('%s %s', $owner['first_name'], $owner['last_name']);

        // Decode apostrophes and other special characters
        $ownerName = trim(html_entity_decode($ownerName, ENT_QUOTES));

        return [$ownerEmail => $ownerName];
    }
}
