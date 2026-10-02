<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\DashboardWidgetPermissionTrait;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;

/**
 * The form widgets must honour form:forms:viewother.
 *
 * These restrict by joining the form's created_by, where the contacts widgets add an
 * owner_id filter, so the two cover the two halves of the same defect.
 */
final class DashboardWidgetPermissionFunctionalTest extends MauticMysqlTestCase
{
    use DashboardWidgetPermissionTrait;

    private const string OWN_SUBMITTER   = 'submitter-own@mautic-test.com';
    private const string OTHER_SUBMITTER = 'submitter-other@mautic-test.com';

    private User $withoutViewOther;

    private User $withViewOther;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutViewOther = $this->createUserWithPermission('form-viewown', 'form', 'forms', 2);
        $this->withViewOther    = $this->createUserWithPermission('form-viewother', 'form', 'forms', 6);

        // Form::setCreatedBy() stores the user's id, so the users must exist first.
        $this->em->flush();

        $this->createSubmission('own', $this->withoutViewOther, self::OWN_SUBMITTER);
        $this->createSubmission('other', $this->withViewOther, self::OTHER_SUBMITTER);

        $this->em->flush();
    }

    public function testTopSubmittersHidesOtherPeoplesFormsWithoutViewOther(): void
    {
        $html = $this->renderWidgetAsUser($this->withoutViewOther, 'top.submitters');

        $this->assertStringContainsString(self::OWN_SUBMITTER, $html, 'Submissions to the user\'s own form should still be counted.');
        $this->assertStringNotContainsString(self::OTHER_SUBMITTER, $html, 'Without form:forms:viewother the widget must not count submissions to another user\'s form.');
    }

    public function testTopSubmittersCountsEveryFormWithViewOther(): void
    {
        $html = $this->renderWidgetAsUser($this->withViewOther, 'top.submitters');

        $this->assertStringContainsString(self::OWN_SUBMITTER, $html);
        $this->assertStringContainsString(self::OTHER_SUBMITTER, $html);
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
}
