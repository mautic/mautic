<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * The form widgets must honour form:forms:viewother.
 *
 * These restrict by joining the form's created_by, rather than by an owner_id filter as the
 * contacts widgets do, so they exercise the other half of the same defect: the subscriber
 * passed $canViewOthers to the $filters parameter in front of it, leaving the flag at its
 * default of true for every user.
 */
final class DashboardWidgetPermissionFunctionalTest extends MauticMysqlTestCase
{
    private const string OWN_SUBMITTER   = 'submitter-own@mautic-test.com';
    private const string OTHER_SUBMITTER = 'submitter-other@mautic-test.com';

    private User $withoutViewOther;

    private User $withViewOther;

    protected function setUp(): void
    {
        parent::setUp();

        // viewown alone is 2; viewown plus viewother is 6.
        $this->withoutViewOther = $this->createUser('form-viewown', 2);
        $this->withViewOther    = $this->createUser('form-viewother', 6);

        // Form::setCreatedBy() stores the user's id, so the users must exist first.
        $this->em->flush();

        $this->createSubmission('own', $this->withoutViewOther, self::OWN_SUBMITTER);
        $this->createSubmission('other', $this->withViewOther, self::OTHER_SUBMITTER);

        $this->em->flush();
    }

    public function testTopSubmittersHidesOtherPeoplesFormsWithoutViewOther(): void
    {
        $html = $this->renderTopSubmittersWidgetFor($this->withoutViewOther);

        $this->assertStringContainsString(self::OWN_SUBMITTER, $html, 'Submissions to the user\'s own form should still be counted.');
        $this->assertStringNotContainsString(self::OTHER_SUBMITTER, $html, 'Without form:forms:viewother the widget must not count submissions to another user\'s form.');
    }

    public function testTopSubmittersCountsEveryFormWithViewOther(): void
    {
        $html = $this->renderTopSubmittersWidgetFor($this->withViewOther);

        $this->assertStringContainsString(self::OWN_SUBMITTER, $html);
        $this->assertStringContainsString(self::OTHER_SUBMITTER, $html);
    }

    private function renderTopSubmittersWidgetFor(User $user): string
    {
        // Widget::get() refuses a widget the current user did not create, so each user needs one.
        $widget = new Widget();
        $widget->setName('Top submitters');
        $widget->setType('top.submitters');
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

    private function createSubmission(string $suffix, User $formOwner, string $submitterEmail): void
    {
        $form = new Form();
        $form->setName('Widget form '.$suffix);
        $form->setAlias('widget_form_'.$suffix);
        $form->setCreatedBy($formOwner);
        $this->em->persist($form);

        $contact = new Lead();
        $contact->setEmail($submitterEmail);
        $contact->setDateIdentified(new \DateTime());
        $this->em->persist($contact);

        $submission = new Submission();
        $submission->setForm($form);
        $submission->setLead($contact);
        $submission->setDateSubmitted(new \DateTime());
        $submission->setReferer('');
        $this->em->persist($submission);
    }

    private function createUser(string $name, int $formsBitwise): User
    {
        $role = new Role();
        $role->setName('role_'.$name);
        $role->setIsAdmin(false);
        $this->em->persist($role);

        $permission = new Permission();
        $permission->setBundle('form');
        $permission->setName('forms');
        $permission->setRole($role);
        $permission->setBitwise($formsBitwise);
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
