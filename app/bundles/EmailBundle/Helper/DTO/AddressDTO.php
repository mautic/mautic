<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper\DTO;

use Mautic\EmailBundle\Helper\Exception\TokenNotFoundOrEmptyException;

class AddressDTO
{
    /**
     * @var string|null
     */
    private $email;

    /**
     * @var string|null
     */
    private $name;

    /**
     * AddressDTO constructor.
     *
     * @param array $address
     */
    public function __construct(array $address)
    {
        $this->email = key($address);
        $this->name  = $address[$this->email];
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $contact
     *
     * @return string
     *
     * @throws TokenNotFoundOrEmptyException
     */
    public function getEmailTokenValue(array $contact)
    {
        if (!preg_match('/{contactfield=(.*?)}/', $this->email, $matches)) {
            throw new TokenNotFoundOrEmptyException();
        }

        $emailToken = $matches[1];

        if (empty($contact[$emailToken])) {
            throw new TokenNotFoundOrEmptyException("$emailToken was not found or empty in the contact array");
        }

        return $contact[$emailToken];
    }

    /**
     * @param array $contact
     *
     * @return string
     *
     * @throws TokenNotFoundOrEmptyException
     */
    public function getNameTokenValue(array $contact)
    {
        if (!preg_match('/{contactfield=(.*?)}/', $this->name, $matches)) {
            throw new TokenNotFoundOrEmptyException();
        }

        $nameToken = $matches[1];

        if (empty($contact[$nameToken])) {
            throw new TokenNotFoundOrEmptyException("$nameToken was not found or empty in the contact array");
        }

        return $contact[$nameToken];
    }

    /**
     * @return bool
     */
    public function isEmailTokenized()
    {
        return (bool) preg_match('/{contactfield=(.*?)}/', $this->email);
    }

    /**
     * @return bool
     */
    public function isNameTokenized()
    {
        return (bool) preg_match('/{contactfield=(.*?)}/', $this->name);
    }

    /**
     * @return array
     */
    public function getAddressArray()
    {
        return [$this->email => $this->name];
    }
}
