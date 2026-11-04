<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListTranslationRepository;

final class SegmentTranslationEditorFunctionalTest extends MauticMysqlTestCase
{
    public function testCreatesAndEditsTranslationsThroughSegmentForm(): void
    {
        $crawler = $this->client->request('GET', '/s/segments/new');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('#translations-tab'));
        self::assertCount(1, $crawler->filter('#leadlist-translation-add'));
        $prototype = $crawler->filter('#leadlist-translation-rows')->attr('data-prototype');
        self::assertNotNull($prototype);
        self::assertStringContainsString('name="leadlist[translations][__name__][locale]"', $prototype);

        $form   = $crawler->selectButton('leadlist_buttons_apply')->form();
        $values = $form->getPhpValues();
        $values['leadlist']['name']               = 'Multilingual news';
        $values['leadlist']['alias']              = 'multilingual-news';
        $values['leadlist']['publicName']         = 'Mautic News';
        $values['leadlist']['isPreferenceCenter'] = '1';
        $values['leadlist']['translations']       = [
            ['locale' => 'fr', 'publicName' => 'Actualités Mautic'],
        ];

        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseIsSuccessful();

        $segment = $this->em->getRepository(LeadList::class)->findOneBy(['alias' => 'multilingual-news']);
        self::assertInstanceOf(LeadList::class, $segment);
        self::assertSame(['fr' => 'Actualités Mautic'], $this->getTranslations($segment));

        $crawler = $this->client->request('GET', '/s/segments/edit/'.$segment->getId());
        self::assertResponseIsSuccessful();
        self::assertSame('Actualités Mautic', $crawler->filter('#leadlist_translations_0_publicName')->attr('value'));

        $form   = $crawler->selectButton('leadlist_buttons_apply')->form();
        $values = $form->getPhpValues();
        $values['leadlist']['translations'] = [
            ['locale' => 'fr', 'publicName' => 'Nouvelles Mautic'],
        ];
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseIsSuccessful();
        self::assertSame(['fr' => 'Nouvelles Mautic'], $this->getTranslations($segment));
    }

    public function testTurningOffPreferenceCenterRetainsTranslations(): void
    {
        $segment = new LeadList();
        $segment->setName('Private later');
        $segment->setAlias('private-later');
        $segment->setPublicName('News');
        $segment->setIsPreferenceCenter(true);
        $this->em->persist($segment);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/s/segments/edit/'.$segment->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $values  = $form->getPhpValues();
        $values['leadlist']['translations'] = [['locale' => 'fr', 'publicName' => 'Actualités']];
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseIsSuccessful();

        $crawler = $this->client->request('GET', '/s/segments/edit/'.$segment->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $values  = $form->getPhpValues();
        $values['leadlist']['isPreferenceCenter'] = '0';
        $values['leadlist']['translations']       = [['locale' => 'invalid', 'publicName' => '']];
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseIsSuccessful();
        self::assertSame(['fr' => 'Actualités'], $this->getTranslations($segment));

        $crawler = $this->client->request('GET', '/s/segments/edit/'.$segment->getId());
        self::assertNotNull($crawler->filter('#translations-tab')->attr('hidden'));
        $form   = $crawler->selectButton('leadlist_buttons_apply')->form();
        $values = $form->getPhpValues();
        $values['leadlist']['isPreferenceCenter'] = '1';
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseIsSuccessful();
        self::assertSame(['fr' => 'Actualités'], $this->getTranslations($segment));

        $crawler = $this->client->request('GET', '/s/segments/edit/'.$segment->getId());
        self::assertNull($crawler->filter('#translations-tab')->attr('hidden'));
        self::assertSame('Actualités', $crawler->filter('#leadlist_translations_0_publicName')->attr('value'));
    }

    public function testDuplicateLocalesLeaveSegmentUnsaved(): void
    {
        $crawler = $this->client->request('GET', '/s/segments/new');
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $values  = $form->getPhpValues();
        $values['leadlist']['name']               = 'Duplicate locale';
        $values['leadlist']['alias']              = 'duplicate-locale-editor';
        $values['leadlist']['isPreferenceCenter'] = '1';
        $values['leadlist']['translations']       = [
            ['locale' => 'fr', 'publicName' => 'Actualités'],
            ['locale' => 'fr', 'publicName' => 'Nouvelles'],
        ];

        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Each locale may be added only once.', (string) $this->client->getResponse()->getContent());
        self::assertSame(0, $this->em->getRepository(LeadList::class)->count(['alias' => 'duplicate-locale-editor']));
    }

    public function testRemovingLastTranslationDeletesIt(): void
    {
        $segment = new LeadList();
        $segment->setName('Remove row');
        $segment->setAlias('remove-translation-row');
        $segment->setPublicName('News');
        $segment->setIsPreferenceCenter(true);
        $this->em->persist($segment);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/s/segments/edit/'.$segment->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $values  = $form->getPhpValues();
        $values['leadlist']['translations'] = [['locale' => 'fr', 'publicName' => 'Actualités']];
        $this->client->request('POST', $form->getUri(), $values);
        self::assertSame(['fr' => 'Actualités'], $this->getTranslations($segment));

        $crawler = $this->client->request('GET', '/s/segments/edit/'.$segment->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $values  = $form->getPhpValues();
        unset($values['leadlist']['translations']);
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->getTranslations($segment));
    }

    /**
     * @return array<string, string>
     */
    private function getTranslations(LeadList $segment): array
    {
        $repository = self::getContainer()->get(LeadListTranslationRepository::class);
        self::assertInstanceOf(LeadListTranslationRepository::class, $repository);

        return $repository->findForSegmentIds([$segment->getId()])[$segment->getId()] ?? [];
    }
}
