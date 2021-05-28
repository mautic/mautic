<?php
/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Tests\Helper;

use MauticPlugin\MauticFocusBundle\Helper\IframeAvailabilityChecker;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;

class IframeAvailabilityCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject
     */
    private $translator;

    /**
     * @var IframeAvailabilityChecker|MockObject
     */
    private $helper;

    public function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->helper     = new IframeAvailabilityChecker($this->translator);
    }

    public function testCheckProtocolMismatch(): void
    {
        $currentScheme           = 'https';
        $url                     = 'http://google.com';
        $translatedErrorMessage  = 'error';
        $expectedResponseContent = [
            'status'       => 0,
            'errorMessage' => $translatedErrorMessage,
        ];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                    'mautic.focus.protocol.mismatch',
                    [
                        '%url%' => str_replace('http://', 'https://', $url),
                    ]
                )
            ->willReturn($translatedErrorMessage);

        /** @var JsonResponse $response */
        $response = $this->helper->check($url, $currentScheme);

        $responseBody = json_decode($response->getContent(), true);
        $this->assertEquals($expectedResponseContent, $responseBody);
    }
}
