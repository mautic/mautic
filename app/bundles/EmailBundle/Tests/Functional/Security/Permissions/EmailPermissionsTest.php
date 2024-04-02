<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\Security\Permissions;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class EmailPermissionsTest extends MauticMysqlTestCase
{
    public function testEmailSendToDncPermissionIsAvailable(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/roles/new');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        Assert::assertStringContainsString('Send to unsubscribed contacts', $this->client->getResponse()->getContent());

        $emailPermissionTab = $crawler->filter('#emailPermissionTab');
        Assert::assertCount(1, $emailPermissionTab);

        $sendToDncRole = $crawler->filter('input[name="role[permissions][email:emails][]"]');
        Assert::assertCount(11, $sendToDncRole);
    }

    public function testUserCanSaveSendToDncPermission(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/roles/new');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $submit = $crawler->selectButton('Save & Close');
        $form   = $submit->form();
        $form['role[name]']->setValue('Send To DNC Permission');
        $form['role[isAdmin]']->setValue('0');
        $form['role[description]']->setValue('This is to send emails with "Send to DNC" permission');
        $form['role[permissions][email:emails][8]']->setValue('sendtodnc');
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $role               = $this->em->getRepository(Role::class)->findOneBy(['name' => 'Send To DNC Permission']);
        $readablePermission = $role->getRawPermissions();
        Assert::assertSame(['email:emails' => [8 => 'sendtodnc']], $readablePermission);
    }
}
