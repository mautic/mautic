<?php

namespace MauticPlugin\MauticFocusBundle\Tests\Helper;

use MauticPlugin\MauticFocusBundle\Helper\IframeAvailabilityChecker;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class IframeAvailabilityCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    private IframeAvailabilityChecker $helper;

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
