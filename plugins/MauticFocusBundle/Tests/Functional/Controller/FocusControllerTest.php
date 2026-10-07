<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use MauticPlugin\MauticFocusBundle\Entity\Focus;

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

    public function testFrequencyInDaysRequiresTheNumberOfDays(): void
    {
        $focus = $this->createNoticeFocus();

        $crawler = $this->client->request('GET', '/s/focus/edit/'.$focus->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['focus[properties][frequency]']->setValue('days');
        $form['focus[properties][frequency_days]']->setValue('');

        $crawler = $this->client->submit($form);

        $this->assertStringContainsString(
            'Enter after how many days the focus should engage the visitor again.',
            $crawler->filter('#focus_properties_frequency_days')->ancestors()->first()->text()
        );
        $this->em->clear();
        $this->assertArrayNotHasKey('frequency_days', $this->em->find(Focus::class, $focus->getId())->getProperties());
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
