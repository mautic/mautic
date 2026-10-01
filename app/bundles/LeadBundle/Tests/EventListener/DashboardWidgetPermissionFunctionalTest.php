<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * The contacts widgets must honour lead:leads:viewother.
 *
 * These go through the dashboard widget endpoint rather than calling the model, because the
 * defects were in how the subscriber called the model: the permission flag was passed to the
 * $filters parameter in front of it, the owner restriction was assigned to $filter while the
 * query used $filters, and getLeadList() applied that restriction to the users who *could*
 * see everyone. Calling LeadModel directly would have passed throughout.
 */
final class DashboardWidgetPermissionFunctionalTest extends MauticMysqlTestCase
{
    private const string OWN_CONTACT   = 'widget-own@mautic-test.com';
    private const string OTHER_CONTACT = 'widget-other@mautic-test.com';

    private User $withoutViewOther;

    private User $withViewOther;

    protected function setUp(): void
    {
        parent::setUp();

        // viewown alone is 2; viewown plus viewother is 6.
        $this->withoutViewOther = $this->createUser('widget-viewown', 2);
        $this->withViewOther    = $this->createUser('widget-viewother', 6);

        $this->createContact(self::OWN_CONTACT, $this->withoutViewOther);
        $this->createContact(self::OTHER_CONTACT, $this->withViewOther);

        $this->em->flush();
    }

    public function testCreatedContactsWidgetHidesOtherPeoplesContactsWithoutViewOther(): void
    {
        $html = $this->renderCreatedContactsWidgetFor($this->withoutViewOther);

        $this->assertStringContainsString(self::OWN_CONTACT, $html, 'The user should still see the contacts they own.');
        $this->assertStringNotContainsString(self::OTHER_CONTACT, $html, 'Without lead:leads:viewother the widget must not show another user\'s contacts.');
    }

    public function testCreatedContactsWidgetShowsEveryContactWithViewOther(): void
    {
        $html = $this->renderCreatedContactsWidgetFor($this->withViewOther);

        $this->assertStringContainsString(self::OWN_CONTACT, $html);
        $this->assertStringContainsString(self::OTHER_CONTACT, $html);
    }

    private function renderCreatedContactsWidgetFor(User $user): string
    {
        // Widget::get() refuses a widget the current user did not create, so each user needs one.
        $widget = new Widget();
        $widget->setName('Created contacts');
        $widget->setType('created.leads');
        $widget->setParams(['limit' => 50]);
        $widget->setWidth(100);
        $widget->setHeight(330);
        $widget->setCreatedBy($user);
        $this->em->persist($widget);
        $this->em->flush();

        $this->loginUser($user);
        $this->client->xmlHttpRequest(Request::METHOD_GET, sprintf('/s/dashboard/widget/%s', $widget->getId()));
        $this->assertResponseIsSuccessful();

        $content = (string) $this->client->getResponse()->getContent();
        $this->assertJson($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('widgetHtml', $data);

        return (string) $data['widgetHtml'];
    }

    private function createContact(string $email, User $owner): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->setOwner($owner);
        // getLeadList() only returns identified contacts.
        $lead->setDateIdentified(new \DateTime());
        $this->em->persist($lead);

        return $lead;
    }

    private function createUser(string $name, int $leadsBitwise): User
    {
        $role = new Role();
        $role->setName('role_'.$name);
        $role->setIsAdmin(false);
        $this->em->persist($role);

        $permission = new Permission();
        $permission->setBundle('lead');
        $permission->setName('leads');
        $permission->setRole($role);
        $permission->setBitwise($leadsBitwise);
        $this->em->persist($permission);

        $user = new User();
        $user->setEmail($name.'@mautic-test.com');
        $user->setUsername($name);
        $user->setFirstName($name);
        $user->setLastName('Test');
        $user->setRole($role);

        $hasher = self::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher($user);
        $this->assertInstanceOf(PasswordHasherInterface::class, $hasher);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));

        $this->em->persist($user);

        return $user;
    }
}
