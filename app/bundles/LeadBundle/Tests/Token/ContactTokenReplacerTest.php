<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Token;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Token\ContactTokenReplacer;

class ContactTokenReplacerTest extends \PHPUnit_Framework_TestCase
{
    private $lead = [
        'firstname' => 'Bob',
        'lastname'  => 'Smith',
        'country'   => '',
        'web'       => 'https://mautic.org',
        'date'      => '2000-05-05 12:45:50',
        'companies' => [
            [
                'companyzip' => '77008',
            ],
        ],
    ];

    private $content = 'custom content with {contactfield=firstname} {leadfield=firstname}';

    private $regex = ['/({|%7B)leadfield=(.*?)(}|%7D)/', '/({|%7B)contactfield=(.*?)(}|%7D)/'];

    private $contactTokenReplacer;

    public function setUp()
    {
        $coreParametersHelperMock   = $this->createMock(CoreParametersHelper::class);
        $this->contactTokenReplacer = new ContactTokenReplacer($coreParametersHelperMock);
        parent::setUp();
    }

    public function testSearchTokens()
    {
        $tokens = $this->contactTokenReplacer->searchTokens($this->content, $this->regex);
        $this->assertCount(2, $tokens);
    }

    public function testGetTokens()
    {
        $tokens = $this->contactTokenReplacer->getTokens($this->content, $this->lead);
        $this->assertCount(2, $tokens);
    }

    public function testReplaceTokens()
    {
        $content = $this->contactTokenReplacer->replaceTokens($this->content, $this->lead);
        $this->assertEquals('custom content with Bob Bob', $content);
    }

    public function testReplaceEmptyValueTokens()
    {
        $content = 'custom content with {contactfield=country}';
        $content = $this->contactTokenReplacer->replaceTokens($content, $this->lead);
        $this->assertEquals('custom content with ', $content);
    }

    public function testReplaceDefaultValueTokens()
    {
        $content = 'custom content with {contactfield=country|somethingdefault}';
        $content = $this->contactTokenReplacer->replaceTokens($content, $this->lead);
        $this->assertEquals('custom content with somethingdefault', $content);
    }

    public function testReplaceUrlEncodeValueTokens()
    {
        $content = 'custom content with {contactfield=web|true}';
        $content = $this->contactTokenReplacer->replaceTokens($content, $this->lead);
        $this->assertEquals('custom content with https%3A%2F%2Fmautic.org', $content);
    }

    public function testReplaceDateTimeFormatValue()
    {
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);

        $coreParametersHelperMock->expects($this->at(0))
            ->method('getParameter')
            ->with('date_format_dateonly')
            ->willReturn('d. m. Y');

        $coreParametersHelperMock->expects($this->at(1))
            ->method('getParameter')
            ->with('date_format_timeonly')
            ->willReturn('g:i a');

        $tokenReplacer      = new ContactTokenReplacer($coreParametersHelperMock);
        $token              = '{contactfield=date|datetime}';
        $tokenList          = $tokenReplacer->getTokens($token, $this->lead);
        $this->assertNotEmpty($tokenList[$token]);
        $this->assertNotSame($this->lead['date'], $tokenList[$token]);
    }

    public function testReplaceDateFormatValue()
    {
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);

        $coreParametersHelperMock->expects($this->at(0))
            ->method('getParameter')
            ->with('date_format_dateonly')
            ->willReturn('d. m. Y');

        $coreParametersHelperMock->expects($this->at(1))
            ->method('getParameter')
            ->with('date_format_timeonly')
            ->willReturn('g:i a');

        $tokenReplacer      = new ContactTokenReplacer($coreParametersHelperMock);
        $token              = '{contactfield=date|time}';
        $tokenList          = $tokenReplacer->getTokens($token, $this->lead);
        $this->assertNotEmpty($tokenList[$token]);
        $this->assertNotSame($this->lead['date'], $tokenList[$token]);
    }

    public function testReplaceTimeFormatValue()
    {
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);

        $coreParametersHelperMock->expects($this->at(0))
            ->method('getParameter')
            ->with('date_format_dateonly')
            ->willReturn('d. m. Y');

        $coreParametersHelperMock->expects($this->at(1))
            ->method('getParameter')
            ->with('date_format_timeonly')
            ->willReturn('g:i a');

        $tokenReplacer      = new ContactTokenReplacer($coreParametersHelperMock);
        $token              = '{contactfield=date|time}';
        $tokenList          = $tokenReplacer->getTokens($token, $this->lead);
        $this->assertNotEmpty($tokenList[$token]);
        $this->assertNotSame($this->lead['date'], $tokenList[$token]);
    }
}
