<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use PHPUnit\Framework\Attributes\DataProvider;

final class FocusControllerTest extends MauticMysqlTestCase
{
    public function testFocusWithProject(): void
    {
        $focus = new Focus();
        $focus->setName('Test Focus');
        $focus->setType('notice');
        $focus->setStyle('bar');
        $this->em->persist($focus);

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/focus/edit/'.$focus->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['focus[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedFocus = $this->em->find(Focus::class, $focus->getId());
        $this->assertInstanceOf(Focus::class, $savedFocus);
        $this->assertSame($project->getId(), $savedFocus->getProjects()->first()->getId());
    }

    public function testFrequencyInDaysAndMinimumPageViewsAreSaved(): void
    {
        $focus = $this->createNoticeFocus();

        $crawler = $this->client->request('GET', '/s/focus/edit/'.$focus->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['focus[properties][frequency]']->setValue('days');
        $form['focus[properties][frequency_days]']->setValue('7');
        $form['focus[properties][min_page_views]']->setValue('2');

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $properties = $this->em->find(Focus::class, $focus->getId())->getProperties();
        $this->assertSame('days', $properties['frequency']);
        $this->assertSame(7, $properties['frequency_days']);
        $this->assertSame(2, $properties['min_page_views']);
    }

    #[DataProvider('provideInvalidNumbers')]
    public function testInvalidNumbersAreRejected(string $field, string $value, string $expectedError): void
    {
        $focus = $this->createNoticeFocus();

        $crawler = $this->client->request('GET', '/s/focus/edit/'.$focus->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['focus[properties][frequency]']->setValue('days');
        $form['focus[properties][frequency_days]']->setValue('7');
        $form['focus[properties][min_page_views]']->setValue('2');
        $form['focus[properties]['.$field.']']->setValue($value);

        $crawler = $this->client->submit($form);

        $this->assertStringContainsString(
            $expectedError,
            $crawler->filter('#focus_properties_'.$field)->ancestors()->first()->text()
        );
        $this->em->clear();
        $this->assertArrayNotHasKey('frequency', $this->em->find(Focus::class, $focus->getId())->getProperties());
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideInvalidNumbers(): iterable
    {
        yield 'every X days without a number' => ['frequency_days', '', 'Enter after how many days the focus should engage the visitor again.'];
        yield 'every 0 days' => ['frequency_days', '0', 'This value should be 1 or more.'];
        yield 'from page view 0' => ['min_page_views', '0', 'This value should be 1 or more.'];
        yield 'from a negative page view' => ['min_page_views', '-1', 'This value should be 1 or more.'];
    }

    private function createNoticeFocus(): Focus
    {
        $focus = new Focus();
        $focus->setName('Test Focus');
        $focus->setType('notice');
        $focus->setStyle('bar');
        $this->em->persist($focus);
        $this->em->flush();
        $this->em->clear();

        return $focus;
    }
}
