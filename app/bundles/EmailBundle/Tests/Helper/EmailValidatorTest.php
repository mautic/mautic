<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\Tests\Helper\EventListener\EmailValidationSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\Translator;

class EmailValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testValidGmailEmail()
    {
        $this->getEmailValidator()->validate('john@gmail.com');
    }

    public function testValidGmailEmailWithPeriod()
    {
        $this->getEmailValidator()->validate('john.doe@gmail.com');
    }

    public function testValidGmailEmailWithPlus()
    {
        $this->getEmailValidator()->validate('john+doe@gmail.com');
    }

    public function testValidGmailEmailWithNonStandardTld()
    {
        // hopefully this domain remains intact
        $this->getEmailValidator()->validate('john@mail.email');
    }

    public function testValidateEmailWithoutTld()
    {
        $this->expectException(InvalidEmailException::class);
        $this->getEmailValidator()->validate('john@doe');
    }

    public function testValidateEmailWithSpaceInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->getEmailValidator()->validate('jo hn@gmail.com');
    }

    public function testValidateEmailWithCaretInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->getEmailValidator()->validate('jo^hn@gmail.com');
    }

    public function testValidateEmailWithApostropheInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->getEmailValidator()->validate('jo\'hn@gmail.com');
    }

    public function testValidateEmailWithSemicolonInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->getEmailValidator()->validate('jo;hn@gmail.com');
    }

    public function testValidateEmailWithAmpersandInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->getEmailValidator()->validate('jo&hn@gmail.com');
    }

    public function testValidateEmailWithStarInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->getEmailValidator()->validate('jo*hn@gmail.com');
    }

    public function testValidateEmailWithPercentInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->getEmailValidator()->validate('jo%hn@gmail.com');
    }

    public function testValidateEmailWithDoublePeriodInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->getEmailValidator()->validate('jo..hn@gmail.com');
    }

    public function testValidateEmailWithBadDNS()
    {
        $this->expectException(InvalidEmailException::class);
        $this->getEmailValidator()->validate('john@doe.shouldneverexist');
    }

    public function testIntegrationInvalidatesEmail()
    {
        try {
            $this->getEmailValidator()->doPluginValidation('bad@gmail.com');

            return;
        } catch (InvalidEmailException $exception) {
            if ('bad email' === $exception->getMessage()) {
                return;
            }
        }

        $this->fail('Event listener did not invalidate the email');
    }

    /**
     * @return EmailValidator
     */
    protected function getEmailValidator()
    {
        $mockTranslator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new EmailValidationSubscriber());

        return new EmailValidator($mockTranslator, $dispatcher);
    }
}
