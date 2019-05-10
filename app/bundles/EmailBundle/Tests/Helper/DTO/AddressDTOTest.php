<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Helper\DTO;

use Mautic\EmailBundle\Helper\DTO\AddressDTO;
use Mautic\EmailBundle\Helper\Exception\TokenNotFoundOrEmptyException;

class AddressDTOTest extends \PHPUnit_Framework_TestCase
{
    public function testNameTokenReturnsTrue()
    {
        $this->assertTrue((new AddressDTO(['someone@somewhere.com' => '{contactfield=other_name}']))->isNameTokenized());
    }

    public function testNameTokenReturnsFalse()
    {
        $this->assertFalse((new AddressDTO(['someone@somewhere.com' => 'Someone Somewhere']))->isNameTokenized());
    }

    public function testNameTokenEmptyThrowsException()
    {
        $this->expectException(TokenNotFoundOrEmptyException::class);

        (new AddressDTO(['someone@somewhere.com' => '{contactfield=other_name}']))->getNameTokenValue([]);
    }

    public function testNameTokenIsReturned()
    {
        $contact = [
            'other_name' => 'Thing Two',
        ];

        $this->assertEquals(
            'Thing Two',
            (new AddressDTO(['someone@somewhere.com' => '{contactfield=other_name}']))->getNameTokenValue($contact)
        );
    }

    public function testEmailTokenReturnsTrue()
    {
        $this->assertTrue((new AddressDTO(['{contactfield=other_email}' => 'Someone Somewhere']))->isEmailTokenized());
    }

    public function testEmailTokenReturnsFalse()
    {
        $this->assertFalse((new AddressDTO(['someone@somewhere.com' => 'Someone Somewhere']))->isEmailTokenized());
    }

    public function testEmailTokenEmptyThrowsException()
    {
        $this->expectException(TokenNotFoundOrEmptyException::class);

        (new AddressDTO(['{contactfield=other_email}' => 'Thing One']))->getEmailTokenValue([]);
    }

    public function testEmailTokenIsReturned()
    {
        $contact = [
            'other_email' => 'other@somewhere.com',
        ];

        $this->assertEquals(
            'other@somewhere.com',
            (new AddressDTO(['{contactfield=other_email}' => '']))->getEmailTokenValue($contact)
        );
    }

    public function testTokenValuesReturned()
    {
        $contact = [
            'other_email' => 'thingtwo@somewhere.com',
            'other_name'  => 'Thing Two',
        ];

        $addressDTO = new AddressDTO(['{contactfield=other_email}' => '{contactfield=other_name}']);

        $this->assertEquals('thingtwo@somewhere.com', $addressDTO->getEmailTokenValue($contact));
        $this->assertEquals('Thing Two', $addressDTO->getNameTokenValue($contact));
    }

    public function testDefaultsAreReturned()
    {
        $addressDTO = new AddressDTO(['someone@somewhere.com' => 'Someone Somewhere']);

        $this->assertEquals('someone@somewhere.com', $addressDTO->getEmail());
        $this->assertEquals('Someone Somewhere', $addressDTO->getName());
    }
}
