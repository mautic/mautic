<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testSendTestEmailAction(): void
    {
        /** @var CoreParametersHelper $parameters */
        $parameters = self::getContainer()->get('mautic.helper.core_parameters');

        $this->client->request(Request::METHOD_POST, '/s/ajax?action=email:sendTestEmail');
        Assert::assertTrue($this->client->getResponse()->isOk());

        $this->assertQueuedEmailCount(1);
        $email = $this->getMailerMessage();

        /** @var UserHelper $userHelper */
        $userHelper = static::getContainer()->get(UserHelper::class);

        $user         = $userHelper->getUser();
        $expectedBody = 'Hi! This is a test email from Mautic. Testing...testing...1...2...3!';

        Assert::assertSame('Mautic test email', $email->getSubject());
        Assert::assertStringContainsString($expectedBody, $email->getHtmlBody());
        Assert::assertSame($expectedBody, $email->getTextBody());
        Assert::assertCount(1, $email->getFrom());
        Assert::assertSame($parameters->get('mailer_from_name'), $email->getFrom()[0]->getName());
        Assert::assertSame($parameters->get('mailer_from_email'), $email->getFrom()[0]->getAddress());
        Assert::assertCount(1, $email->getTo());
        Assert::assertSame($user->getFirstName().' '.$user->getLastName(), $email->getTo()[0]->getName());
        Assert::assertSame($user->getEmail(), $email->getTo()[0]->getAddress());
        Assert::assertCount(1, $email->getReplyTo());
        Assert::assertSame('', $email->getReplyTo()[0]->getName());
        Assert::assertSame($parameters->get('mailer_from_email'), $email->getReplyTo()[0]->getAddress());
    }
}
