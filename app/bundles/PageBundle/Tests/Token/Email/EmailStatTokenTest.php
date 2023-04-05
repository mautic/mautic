<?php

namespace Mautic\PageBundle\Tests\Token\Email;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Token\Email\EmailStatToken;
use PHPUnit\Framework\TestCase;

class EmailStatTokenTest extends TestCase
{
    /**
     * @dataProvider replaceProvider
     */
    public function testReplace(array $clickthrough, string $url, string $expected)
    {
        $clickthrough = ClickthroughHelper::encodeArrayForUrl($clickthrough);
        $emailModel   = $this->createMock(EmailModel::class);
        $emailModel->method('getEmailStatus')
            ->willReturnCallback(function ($id) {
                $stat = new Stat();
                $stat->setTokens(['{contactfield=firstname}' => 'John', '{contactfield=lastname}' => 'Doe']);
                $stat->setEmail(new Email());

                return $stat;
            });

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturn(null);

        $emailStatToken = new EmailStatToken($emailModel, $coreParametersHelper);

        $this->assertEquals($expected, $emailStatToken->replace($clickthrough, $url));
    }

    public function replaceProvider()
    {
        return [
            [
                ['channel' => ['email' => 1], 'stat' => 1],
                'http://example.com?firstname={contactfield=firstname}&lastname={contactfield=lastname}',
                'http://example.com?firstname=John&lastname=Doe',
            ],
            [
                ['channel' => ['social' => 1], 'stat' => 1],
                'http://example.com?firstname={contactfield=firstname}&lastname={contactfield=lastname}',
                'http://example.com?firstname={contactfield=firstname}&lastname={contactfield=lastname}',
            ],
            [
                ['channel' => ['email' => 1], 'stat' => 2],
                'http://example.com?firstname={contactfield=firstname}&lastname={contactfield=lastname}',
                'http://example.com?firstname=John&lastname=Doe',
            ],
            [
                ['channel' => ['email' => 1], 'stat' => 1],
                'http://example.com',
                'http://example.com',
            ],
            [
                ['channel' => ['email' => 1]],
                'http://example.com?firstname={contactfield=firstname}&lastname={contactfield=lastname}',
                'http://example.com?firstname={contactfield=firstname}&lastname={contactfield=lastname}',
            ],
            [
                ['channel' => 'email'],
                'http://example.com?firstname={contactfield=firstname}&lastname={contactfield=lastname}',
                'http://example.com?firstname={contactfield=firstname}&lastname={contactfield=lastname}',
            ],
        ];
    }
}
