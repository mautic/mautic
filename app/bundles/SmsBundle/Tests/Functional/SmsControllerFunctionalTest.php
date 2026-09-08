<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Functional;

use Mautic\CoreBundle\Entity\TranslationEntityInterface;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class SmsControllerFunctionalTest extends MauticMysqlTestCase
{
    use CreateEntitiesTrait;

    public function testSmsCanBeCreatedWithTranslationParent(): void
    {
        // Arrange
        $parentSms = $this->createAndPersistSms('Parent SMS', 'Parent SMS message');

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/new');
        $this->assertResponseIsSuccessful();

        $form                                   = $crawler->selectButton('Save')->form();
        $form['sms[name]']                      = 'Child SMS';
        $form['sms[message]']                   = 'Child SMS message';
        $form['sms[translationParentSelector]'] = (string) $parentSms->getId();

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert
        $childSms = $this->em->getRepository(Sms::class)->findOneBy(['name' => 'Child SMS']);
        $this->assertInstanceOf(Sms::class, $childSms);
        $this->assertInstanceOf(Sms::class, $childSms->getTranslationParent());
        $this->assertSame($parentSms->getId(), $childSms->getTranslationParent()->getId());
    }

    public function testSmsCannotBeItsOwnTranslationParent(): void
    {
        // Arrange
        $sms = $this->createAndPersistSms('Test SMS', 'Test SMS message');

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$sms->getId());
        $this->assertResponseIsSuccessful();

        // Assert
        $options = $crawler->filter('#sms_translationParentSelector option');
        $this->assertCount(2, $options);
        $this->assertSame('Choose a translated item...', $options->eq(0)->text());
        $this->assertSame('Create new...', $options->eq(1)->text());

        // Ensure the SMS itself is not in the dropdown
        $optionValues = $options->each(fn ($node) => $node->attr('value'));
        $this->assertNotContains((string) $sms->getId(), $optionValues);
    }

    public function testSmsWithTranslationParentCanBeEdited(): void
    {
        // Arrange
        $parentSms    = $this->createAndPersistSms('Parent SMS', 'Parent SMS message');
        $childSms     = $this->createAndPersistSms('Child SMS', 'Child SMS message');
        $childSms->setTranslationParent($parentSms);

        $newParentSms = $this->createAndPersistSms('New Parent SMS', 'New Parent SMS message');
        $this->em->flush();

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$childSms->getId());
        $this->assertResponseIsSuccessful();

        // Assert original parent is selected
        $this->assertSame(
            (string) $parentSms->getId(),
            $crawler->filter('#sms_translationParentSelector option[selected]')->attr('value')
        );

        // Change parent
        $form                                   = $crawler->selectButton('Save')->form();
        $form['sms[translationParentSelector]'] = (string) $newParentSms->getId();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert parent updated
        $this->em->refresh($childSms);
        $this->assertInstanceOf(Sms::class, $childSms->getTranslationParent());
        $this->assertSame($newParentSms->getId(), $childSms->getTranslationParent()->getId());
    }

    public function testTranslationParentCanBeRemovedFromSms(): void
    {
        // Arrange
        $parentSms = $this->createAndPersistSms('Parent SMS', 'Parent SMS message');
        $childSms  = $this->createAndPersistSms('Child SMS', 'Child SMS message');
        $childSms->setTranslationParent($parentSms);
        $this->em->flush();

        // Act
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$childSms->getId());
        $this->assertResponseIsSuccessful();

        $form                                   = $crawler->selectButton('Save')->form();
        $form['sms[translationParentSelector]'] = '';
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Assert
        $this->em->refresh($childSms);
        $this->assertNotInstanceOf(TranslationEntityInterface::class, $childSms->getTranslationParent());
    }

    public function testTranslationsAreDisplayedOnViewPage(): void
    {
        // Arrange
        $parentSms = $this->createAndPersistSms('Parent SMS', 'Parent SMS message', 'en');
        $childSms  = $this->createAndPersistSms('Child SMS', 'Child SMS message', 'fr');
        $childSms->setTranslationParent($parentSms);
        $parentSms->addTranslationChild($childSms);

        $this->em->flush();

        // Act & Assert - Parent view
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$parentSms->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('a[href="#translation-container"]'));
        $this->client->click($crawler->selectLink('Translations')->link());
        $this->assertSelectorTextContains('#translation-container', 'Child SMS');

        // Act & Assert - Child view
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$childSms->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('a[href="#translation-container"]'));
        $this->client->click($crawler->selectLink('Translations')->link());
        $this->assertSelectorTextContains('#translation-container', 'Parent SMS');
    }

    public function testScheduleButtonIsLimitedToNonEmbeddedSegmentSms(): void
    {
        $segmentSms  = $this->createAndPersistSegmentSms('Segment SMS', 'Segment message');
        $templateSms = $this->createAndPersistSms('Template SMS', 'Template message');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$segmentSms->getId());
        $this->assertResponseIsSuccessful();
        $scheduleButton = $crawler->filter(sprintf('a[href="/s/sms/scheduleSend/%d"]', $segmentSms->getId()));
        $this->assertCount(1, $scheduleButton);
        $this->assertSame('ajaxmodal', $scheduleButton->attr('data-toggle'));
        $this->assertSame('#MauticSharedModal', $scheduleButton->attr('data-target'));

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$templateSms->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('a[href*="/scheduleSend/"]'));

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$segmentSms->getId().'?isEmbedded=1');
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('a[href*="/scheduleSend/"]'));
    }

    /**
     * @param string[] $permissions
     */
    #[DataProvider('schedulePublishPermissionProvider')]
    public function testScheduleButtonRequiresEntityPublishPermission(array $permissions, bool $expectButton): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'sales']);
        $this->assertInstanceOf(User::class, $user);

        $sms = $this->createAndPersistSegmentSms('Owned segment SMS', 'Segment message');
        $sms->setCreatedBy($user->getId());
        $this->em->flush();

        $this->setPermission($user->getRole(), ['sms:smses' => $permissions]);
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', 'sales');
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$sms->getId());
        $this->assertResponseIsSuccessful();

        $this->assertCount(
            $expectButton ? 1 : 0,
            $crawler->filter(sprintf('a[href="/s/sms/scheduleSend/%d"]', $sms->getId()))
        );

        if (!$expectButton) {
            $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/sms/scheduleSend/'.$sms->getId());
            $this->assertResponseIsSuccessful();
            $this->assertScheduleModalWasClosed();

            $schedulePostValues = $this->getSchedulePostValues();
            $this->setCsrfHeader();
            $this->client->xmlHttpRequest(
                Request::METHOD_POST,
                '/s/sms/scheduleSend/'.$sms->getId(),
                $schedulePostValues,
            );
            $this->assertResponseIsSuccessful();
            $this->assertScheduleModalWasClosed();
            $this->em->refresh($sms);
            $this->assertNull($sms->getPublishUp());
        }
    }

    /**
     * @return iterable<string, array{string[], bool}>
     */
    public static function schedulePublishPermissionProvider(): iterable
    {
        yield 'view without publish' => [['viewown'], false];
        yield 'publish own' => [['viewown', 'publishown'], true];
        yield 'publish other does not grant publish own' => [['viewown', 'publishother'], false];
    }

    public function testScheduleButtonAndModalAllowPublishOtherForAnotherOwnersSms(): void
    {
        $user  = $this->em->getRepository(User::class)->findOneBy(['username' => 'sales']);
        $owner = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(User::class, $owner);

        $sms = $this->createAndPersistSegmentSms('Other owner segment SMS', 'Segment message');
        $sms->setCreatedBy($owner);
        $this->em->persist($sms);
        $this->em->flush();

        $this->setPermission($user->getRole(), ['sms:smses' => ['viewother', 'publishother']]);
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', 'sales');
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$sms->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter(sprintf('a[href="/s/sms/scheduleSend/%d"]', $sms->getId())));

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/scheduleSend/'.$sms->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('form[name="schedule_send"]'));
    }

    public function testScheduleModalShowsCronNoticeAndExistingOneTimeActions(): void
    {
        $sms = $this->createAndPersistSegmentSms('Scheduled SMS', 'Segment message');
        $sms->setPublishUp(new \DateTime('+1 day'));
        $sms->setContinueSending(false);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/scheduleSend/'.$sms->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-info', 'mautic:broadcasts:send --channel=sms');
        $this->assertCount(1, $crawler->selectButton('Update schedule'));
        $this->assertCount(1, $crawler->selectButton('Cancel schedule'));
        $this->assertCount(1, $crawler->selectButton('Close'));
        $this->assertSame('0', $crawler->filter('input[name="schedule_send[continueSending]"][checked]')->attr('value'));
    }

    public function testScheduleCanBeCreatedUpdatedAndCancelled(): void
    {
        $sms = $this->createAndPersistSegmentSms('Schedule lifecycle SMS', 'Segment message', false);

        $start = (new \DateTime('+1 day'))->format('Y-m-d H:i');
        $stop  = (new \DateTime('+2 days'))->format('Y-m-d H:i');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/scheduleSend/'.$sms->getId());
        $form    = $crawler->selectButton('Schedule')->form();
        $form['schedule_send[publishUp]']->setValue($start);
        $form['schedule_send[continueSending]']->setValue('0');
        $form['schedule_send[publishDown]']->setValue($stop);
        $this->client->submit($form);

        $this->em->refresh($sms);
        $this->assertTrue($sms->getIsPublished());
        $this->assertSame($start, $sms->getPublishUp()?->format('Y-m-d H:i'));
        $this->assertFalse($sms->isContinueSending());
        $this->assertNull($sms->getPublishDown());

        $updatedStart = (new \DateTime('+3 days'))->format('Y-m-d H:i');
        $updatedStop  = (new \DateTime('+4 days'))->format('Y-m-d H:i');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/scheduleSend/'.$sms->getId());
        $form    = $crawler->selectButton('Update schedule')->form();
        $form['schedule_send[publishUp]']->setValue($updatedStart);
        $form['schedule_send[continueSending]']->setValue('1');
        $form['schedule_send[publishDown]']->setValue($updatedStop);
        $this->client->submit($form);

        $this->em->refresh($sms);
        $this->assertSame($updatedStart, $sms->getPublishUp()?->format('Y-m-d H:i'));
        $this->assertTrue($sms->isContinueSending());
        $this->assertSame($updatedStop, $sms->getPublishDown()?->format('Y-m-d H:i'));

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/scheduleSend/'.$sms->getId());
        $form    = $crawler->selectButton('Cancel schedule')->form();
        $this->client->submit($form);

        $this->em->refresh($sms);
        $this->assertNull($sms->getPublishUp());
        $this->assertNull($sms->getPublishDown());
        $this->assertFalse($sms->isContinueSending());
        $this->assertTrue($sms->getIsPublished());
    }

    #[DataProvider('eligibleStartTimeProvider')]
    public function testScheduleAcceptsPastOrCurrentStartTime(string $relativeStart): void
    {
        $sms   = $this->createAndPersistSegmentSms('Eligible start SMS '.$relativeStart, 'Segment message', false);
        $start = (new \DateTime($relativeStart))->format('Y-m-d H:i');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/scheduleSend/'.$sms->getId());
        $form    = $crawler->selectButton('Schedule')->form();
        $form['schedule_send[publishUp]']->setValue($start);
        $form['schedule_send[continueSending]']->setValue('0');
        $this->client->submit($form);

        $this->em->refresh($sms);
        $this->assertTrue($sms->getIsPublished());
        $this->assertSame($start, $sms->getPublishUp()?->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function eligibleStartTimeProvider(): iterable
    {
        yield 'past' => ['-1 minute'];
        yield 'current minute' => ['now'];
    }

    public function testInvalidScheduleRangeDoesNotSave(): void
    {
        $sms = $this->createAndPersistSegmentSms('Invalid range SMS', 'Segment message', false);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/scheduleSend/'.$sms->getId());
        $form    = $crawler->selectButton('Schedule')->form();
        $form['schedule_send[publishUp]']->setValue('2027-01-02 10:00');
        $form['schedule_send[continueSending]']->setValue('1');
        $form['schedule_send[publishDown]']->setValue('2027-01-02 09:00');
        $crawler = $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            'Please set the stop sending date to be after the start sending date.',
            $crawler->filter('body')->text()
        );
        $this->em->refresh($sms);
        $this->assertNull($sms->getPublishUp());
        $this->assertFalse($sms->getIsPublished());
    }

    public function testInvalidScheduleCsrfTokenDoesNotSave(): void
    {
        $sms = $this->createAndPersistSegmentSms('Invalid CSRF SMS', 'Segment message', false);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/scheduleSend/'.$sms->getId());
        $form    = $crawler->selectButton('Schedule')->form();
        $form['schedule_send[publishUp]']->setValue((new \DateTime('+1 day'))->format('Y-m-d H:i'));
        $form['schedule_send[continueSending]']->setValue('0');
        $values                            = $form->getPhpValues();
        $values['schedule_send']['_token'] = 'invalid-token';
        $this->client->request(Request::METHOD_POST, $form->getUri(), $values);

        $this->assertResponseIsSuccessful();
        $this->em->refresh($sms);
        $this->assertNull($sms->getPublishUp());
        $this->assertFalse($sms->getIsPublished());
    }

    public function testTemplateAndMissingSmsCannotBeScheduledByGetOrPost(): void
    {
        $templateSms = $this->createAndPersistSms('Template SMS', 'Template message');
        $postValues  = $this->getSchedulePostValues();
        $this->setCsrfHeader();

        foreach ([$templateSms->getId(), 999999999] as $objectId) {
            $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/sms/scheduleSend/'.$objectId);
            $this->assertResponseIsSuccessful();
            $this->assertScheduleModalWasClosed();

            $this->client->xmlHttpRequest(Request::METHOD_POST, '/s/sms/scheduleSend/'.$objectId, $postValues);
            $this->assertResponseIsSuccessful();
            $this->assertScheduleModalWasClosed();
        }

        $this->em->refresh($templateSms);
        $this->assertNull($templateSms->getPublishUp());
    }

    public function testTranslationChildScheduleRouteRedirectsToParent(): void
    {
        $parentSms = $this->createAndPersistSegmentSms('Parent segment SMS', 'Parent message');
        $childSms  = $this->createAndPersistSegmentSms('Child segment SMS', 'Child message');
        $childSms->setTranslationParent($parentSms);
        $parentSms->addTranslationChild($childSms);
        $this->em->flush();

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/s/sms/scheduleSend/'.$childSms->getId());

        $this->assertResponseRedirects('/s/sms/scheduleSend/'.$parentSms->getId());

        $this->client->request(Request::METHOD_POST, '/s/sms/scheduleSend/'.$childSms->getId(), [
            'schedule_send' => [
                'publishUp'       => '2030-01-01 10:00',
                'continueSending' => '0',
            ],
        ]);
        $this->assertResponseRedirects('/s/sms/scheduleSend/'.$parentSms->getId());
    }

    public function testTranslationScheduleButtonUsesParentSmsType(): void
    {
        $templateParent = $this->createAndPersistSms('Template parent SMS', 'Parent message');
        $segmentChild   = $this->createAndPersistSegmentSms('Segment child SMS', 'Child message');
        $segmentChild->setTranslationParent($templateParent);
        $templateParent->addTranslationChild($segmentChild);

        $segmentParent = $this->createAndPersistSegmentSms('Segment parent SMS', 'Parent message');
        $templateChild = $this->createAndPersistSms('Template child SMS', 'Child message');
        $templateChild->setTranslationParent($segmentParent);
        $segmentParent->addTranslationChild($templateChild);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$segmentChild->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('a[href*="/scheduleSend/"]'));

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/view/'.$templateChild->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(
            1,
            $crawler->filter(sprintf('a[href="/s/sms/scheduleSend/%d"]', $segmentParent->getId()))
        );
    }

    public function testEditorShowsScheduleNoticeForSegmentAndDateInputsForTemplate(): void
    {
        $segmentSms  = $this->createAndPersistSegmentSms('Editor segment SMS', 'Segment message');
        $templateSms = $this->createAndPersistSms('Editor template SMS', 'Template message');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$segmentSms->getId());
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('hide', (string) $crawler->filter('#smsScheduleDates')->attr('class'));
        $this->assertStringNotContainsString('hide', (string) $crawler->filter('#smsScheduleOptionsNotice')->attr('class'));
        $this->assertSelectorTextContains('#smsScheduleOptionsNotice', 'Schedule button');
        $this->assertCount(1, $crawler->filter('input[name="sms[publishUp]"][disabled]'));
        $this->assertCount(1, $crawler->filter('input[name="sms[publishDown]"][disabled]'));

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$templateSms->getId());
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('hide', (string) $crawler->filter('#smsScheduleDates')->attr('class'));
        $this->assertStringContainsString('hide', (string) $crawler->filter('#smsScheduleOptionsNotice')->attr('class'));
        $this->assertCount(1, $crawler->filter('input[name="sms[publishUp]"]:not([disabled])'));
        $this->assertCount(1, $crawler->filter('input[name="sms[publishDown]"]:not([disabled])'));
    }

    public function testMainEditorIgnoresTamperedScheduleDatesForSegmentSms(): void
    {
        $segment = $this->createSegment('schedule-tamper-segment');
        $sms     = $this->createAndPersistSegmentSms('Schedule tamper SMS', 'Segment message');
        $sms->addList($segment);
        $sms->setContinueSending(true);
        $sms->setPublishUp(new \DateTime('2030-01-02 10:15:00'));
        $sms->setPublishDown(new \DateTime('2030-01-03 10:15:00'));
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/edit/'.$sms->getId());
        $this->assertResponseIsSuccessful();

        $form                         = $crawler->selectButton('Save')->form();
        $values                       = $form->getPhpValues();
        $values['sms']['publishUp']   = '2031-02-03 11:30';
        $values['sms']['publishDown'] = '2031-02-04 11:30';
        $this->client->request(Request::METHOD_POST, $form->getUri(), $values);

        $this->assertResponseIsSuccessful();
        $this->em->refresh($sms);
        $this->assertSame('2030-01-02 10:15', $sms->getPublishUp()?->format('Y-m-d H:i'));
        $this->assertSame('2030-01-03 10:15', $sms->getPublishDown()?->format('Y-m-d H:i'));
    }

    private function createAndPersistSms(string $name, string $message, string $locale = 'en'): Sms
    {
        $sms = $this->createAnSms($name, $message, true, $locale);
        $this->em->persist($sms);
        $this->em->flush();

        return $sms;
    }

    private function createAndPersistSegmentSms(string $name, string $message, bool $isPublished = true): Sms
    {
        $sms = $this->createAnSms($name, $message, $isPublished);
        $sms->setSmsType('list');
        $this->em->persist($sms);
        $this->em->flush();

        return $sms;
    }

    private function createSegment(string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setName($alias);
        $segment->setPublicName($alias);
        $segment->setAlias($alias);
        $segment->setIsPublished(true);
        $this->em->persist($segment);

        return $segment;
    }

    /**
     * @return array<string, mixed>
     */
    private function getSchedulePostValues(): array
    {
        return [
            'schedule_send' => [
                '_token'          => $this->getCsrfToken('schedule_send'),
                'publishUp'       => '2030-01-01 10:00',
                'continueSending' => '0',
            ],
        ];
    }

    private function assertScheduleModalWasClosed(): void
    {
        $content = (string) $this->client->getResponse()->getContent();
        $data    = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertSame(1, $data['closeModal'] ?? null, $content);
        $this->assertFalse($data['route'] ?? null, $content);
    }

    /**
     * @param array<string, string[]> $permissions
     */
    private function setPermission(Role $role, array $permissions): void
    {
        /** @var RoleModel $roleModel */
        $roleModel = self::getContainer()->get(RoleModel::class);
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();
    }
}
