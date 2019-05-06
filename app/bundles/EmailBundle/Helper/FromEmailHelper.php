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
use Mautic\EmailBundle\Helper\Exception\OwnerNotFoundException;
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
     * @var array|null
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
        $this->defaultFrom = $from;
    }

    /**
     * @param array      $from
     * @param array|null $contact
     *
     * @return array
     */
    public function getFromAddressArrayConsideringOwner(array $from, array $contact = null)
    {
        // Reset last owner
        $this->lastOwner = null;

        $email = key($from);

        // Check for token
        if ($this->isToken($email)) {
            return $this->getEmailArrayFromToken($email, $contact, true);
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
        // Reset last owner
        $this->lastOwner = null;

        $email = key($from);

        // Check for token
        if ($this->isToken($email)) {
            return $this->getEmailArrayFromToken($email, $contact, false);
        }

        return $from;
    }

    /**
     * @param int $userId
     *
     * @return array|null
     */
    public function getContactOwner(int $userId)
    {
        // Reset last owner
        $this->lastOwner = null;

        if (!$this->coreParametersHelper->getParameter('mailer_is_owner')) {
            return null;
        }

        if (isset($this->owners[$userId])) {
            return $this->owners[$userId];
        }

        if ($owner = $this->leadRepository->getLeadOwner($userId)) {
            $this->owners[$userId] = $this->lastOwner = $owner;

            return $owner;
        }

        return null;
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
            return $this->defaultFrom;
        }

        $email = $this->coreParametersHelper->getParameter('mailer_from_email');
        $name  = $this->coreParametersHelper->getParameter('mailer_from_name');
        $name  = $name ? $name : null;

        return [$email => $name];
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    private function isToken($email)
    {
        return (bool) preg_match('/{contactfield=(.*?)}/', $email);
    }

    /**
     * @param string $token
     * @param array  $contact
     * @param bool   $asOwner
     *
     * @return array
     */
    private function getEmailArrayFromToken($token, array $contact, $asOwner)
    {
        preg_match('/{contactfield=(.*?)}/', $token, $matches);

        $field = $matches[1];

        if (!empty($contact[$field])) {
            return [$contact[$field] => null];
        }

        if ($asOwner) {
            try {
                return $this->getFromEmailArrayAsOwner($contact);
            } catch (OwnerNotFoundException $exception) {
            }
        }

        return $this->getDefaultFromArray();
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

        if (!$owner = $this->getContactOwner($contact['owner_id'])) {
            throw new OwnerNotFoundException();
        }

        $ownerEmail = $owner['email'];
        $ownerName  = sprintf('%s %s', $owner['first_name'], $owner['last_name']);

        return [$ownerEmail => $ownerName];
    }
}
