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
     *
     * @param CoreParametersHelper $coreParametersHelper
     * @param LeadRepository       $leadRepository
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, LeadRepository $leadRepository)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->leadRepository       = $leadRepository;
    }

    /**
     * @param array $from
     */
    public function setDefaultFromArray(array $from)
    {
        $this->defaultFrom = new AddressDTO($from);
    }

    /**
     * @param array      $from
     * @param array|null $contact
     *
     * @return array
     */
    public function getFromAddressArrayConsideringOwner(array $from, array $contact = null)
    {
        $address = new AddressDTO($from);

        // Reset last owner
        $this->lastOwner = null;

        // Check for token
        if ($address->isEmailTokenized() || $address->isNameTokenized()) {
            return $this->getEmailArrayFromToken($address, $contact);
        }

        if (!$contact) {
            return $from;
        }

        try {
            return $this->getFromEmailArrayAsOwner($contact);
        } catch (OwnerNotFoundException $exception) {
            return $from;
        }
    }

    /**
     * @param array      $from
     * @param array|null $contact
     *
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
    public function getContactOwner($userId)
    {
        // Reset last owner
        $this->lastOwner = null;

        if (!$this->coreParametersHelper->getParameter('mailer_is_owner')) {
            throw new OwnerNotFoundException('mailer_is_owner is not enabled');
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
     * @param array  $owner
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
        $email = $this->coreParametersHelper->getParameter('mailer_from_email');
        $name  = $this->coreParametersHelper->getParameter('mailer_from_name');
        $name  = $name ? $name : null;

        return new AddressDTO([$email => $name]);
    }

    /**
     * @param AddressDTO $address
     * @param array      $contact
     * @param bool       $asOwner
     *
     * @return array
     */
    private function getEmailArrayFromToken(AddressDTO $address, array $contact = null, $asOwner = true)
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
                    return $this->getFromEmailArrayAsOwner($contact);
                } catch (OwnerNotFoundException $exception) {
                }
            }

            return $this->getDefaultFromArray();
        }
    }

    /**
     * @param array $contact
     *
     * @return array
     *
     * @throws OwnerNotFoundException
     */
    private function getFromEmailArrayAsOwner(array $contact)
    {
        if (empty($contact['owner_id'])) {
            throw new OwnerNotFoundException();
        }

        $owner      = $this->getContactOwner($contact['owner_id']);
        $ownerEmail = $owner['email'];
        $ownerName  = sprintf('%s %s', $owner['first_name'], $owner['last_name']);

        // Decode apostrophes and other special characters
        $ownerName = trim(html_entity_decode($ownerName, ENT_QUOTES));

        return [$ownerEmail => $ownerName];
    }
}
