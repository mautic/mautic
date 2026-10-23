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

    private const string OWN_REFERRER    = 'https://own-referrer.mautic-test.com/page';
    private const string OTHER_REFERRER  = 'https://other-referrer.mautic-test.com/page';

    private User $withoutViewOther;

    private User $withViewOther;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutViewOther = $this->createUserWithPermission('form-viewown', 'form', 'forms', 2);
        $this->withViewOther    = $this->createUserWithPermission('form-viewother', 'form', 'forms', 6);

        // Form::setCreatedBy() stores the user's id, so the users must exist first.
        $this->em->flush();

        $this->createSubmission('own', $this->withoutViewOther, self::OWN_SUBMITTER, self::OWN_REFERRER);
        $this->createSubmission('other', $this->withViewOther, self::OTHER_SUBMITTER, self::OTHER_REFERRER);

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

    public function testSubmissionsInTimeCountsOnlyOwnFormsWithoutViewOther(): void
    {
        $html = $this->renderWidgetAsUser($this->withoutViewOther, 'submissions.in.time');

        $this->assertSame(1, $this->sumChartData($html), 'Without form:forms:viewother the chart must count only submissions to the user\'s own form.');
    }

    public function testSubmissionsInTimeCountsEveryFormWithViewOther(): void
    {
        $html = $this->renderWidgetAsUser($this->withViewOther, 'submissions.in.time');

        $this->assertSame(2, $this->sumChartData($html));
    }

    public function testTopReferrersHidesOtherPeoplesFormsWithoutViewOther(): void
    {
        $html = $this->renderWidgetAsUser($this->withoutViewOther, 'top.submission.referrers');

        $this->assertStringContainsString(self::OWN_REFERRER, $html, 'Referrers of the user\'s own form should still be listed.');
        $this->assertStringNotContainsString(self::OTHER_REFERRER, $html, 'Without form:forms:viewother the widget must not list referrers of another user\'s form.');
    }

    public function testTopReferrersListsEveryFormWithViewOther(): void
    {
        $html = $this->renderWidgetAsUser($this->withViewOther, 'top.submission.referrers');

        $this->assertStringContainsString(self::OWN_REFERRER, $html);
        $this->assertStringContainsString(self::OTHER_REFERRER, $html);
    }

    private function createSubmission(string $suffix, User $formOwner, string $submitterEmail, string $referrer): void
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
        $submission->setReferer($referrer);
        $this->em->persist($submission);
    }
}
