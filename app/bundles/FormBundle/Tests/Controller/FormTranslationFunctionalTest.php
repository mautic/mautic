<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;

final class FormTranslationFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testNewFormsUseMauticLocale(): void
    {
        $this->setUpSymfony($this->configParams + ['locale' => 'fr']);
        $model = self::getContainer()->get(FormModel::class);
        $this->assertInstanceOf(FormModel::class, $model);
        $this->assertSame('fr', $model->getEntity()->getLanguage());
    }

    public function testSavingEmptyLanguageUsesMauticLocale(): void
    {
        $this->setUpSymfony($this->configParams + ['locale' => 'fr']);
        $model = self::getContainer()->get(FormModel::class);
        $form = $model->getEntity();
        $form->setName('Default language');
        $form->setLanguage(null);
        $model->saveEntity($form);
        $id = $form->getId();
        $this->em->clear();
        $this->assertSame('fr', $this->em->find(Form::class, $id)->getLanguage());
    }

    public function testTranslationCanBeSavedAndViewedInParentTab(): void
    {
        $parent = new Form();
        $parent->setName('Parent form');
        $parent->setAlias('parent-form');
        $this->em->persist($parent);
        $this->em->flush();
        $parentId = $parent->getId();

        $child = new Form();
        $child->setName('Unlinked form');
        $child->setAlias('unlinked_form');
        $child->setPostActionProperty('Success');
        $field = new \Mautic\FormBundle\Entity\Field();
        $field->setLabel('Name');
        $field->setAlias('name');
        $field->setType('text');
        $field->setForm($child);
        $child->addField(0, $field);
        $this->em->persist($child);
        $this->em->persist($field);
        $this->em->flush();
        $crawler = $this->client->request('GET', '/s/forms/edit/'.$child->getId());
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Save')->form();
        $language = $form['mauticform[language]'];
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Field\ChoiceFormField::class, $language);
        $language->disableValidation();
        $form->setValues([
            'mauticform[name]'              => 'French translation',
            'mauticform[language]'          => 'fr',
            'mauticform[translationParent]' => (string) $parentId,
        ]);
        $values = $form->getPhpValues();
        // The title input is disabled until the JavaScript inline editor opens it.
        $values['mauticform']['name'] = 'French translation';
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseIsSuccessful();
        $this->em->clear();
        $child = $this->em->getRepository(Form::class)->findOneBy(['name' => 'French translation']);
        $this->assertInstanceOf(Form::class, $child, $this->client->getCrawler()->filter('.has-error, .alert-danger')->text('', true));
        $this->assertSame('fr', $child->getLanguage());
        $this->assertSame($parentId, $child->getTranslationParent()->getId());
        $childId = $child->getId();
        $parent = $this->em->find(Form::class, $parentId);
        $this->assertInstanceOf(Form::class, $parent);
        $this->assertSame(1, $parent->hasTranslations());

        $crawler = $this->client->request('GET', '/s/forms/view/'.$parentId);
        self::assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('a[href="#translation-container"]'));
        $this->assertStringContainsString('French translation', $crawler->filter('#translation-container')->text());
        $this->assertGreaterThan(0, $crawler->filter('#translation-container a[href="/s/forms/view/'.$childId.'"]')->count());

        $crawler = $this->client->request('GET', '/s/forms/edit/'.$childId);
        self::assertResponseIsSuccessful();
        $this->assertSame((string) $parentId, $crawler->filter('#mauticform_translationParent option:selected')->attr('value'));
    }

    public function testClonesDoNotRetainTranslationRelationships(): void
    {
        $parent = new Form();
        $parent->setName('Translation parent');
        $parent->setAlias('translation-parent');
        $child = new Form();
        $child->setName('Translation child');
        $child->setAlias('translation-child');
        $child->setLanguage('fr');
        $child->setTranslationParent($parent);
        $parent->addTranslationChild($child);
        $this->em->persist($parent);
        $this->em->persist($child);
        $this->em->flush();
        $parentId = $parent->getId();
        $childId  = $child->getId();

        foreach ([$parent, $child] as $original) {
            $clone = clone $original;
            $this->assertNull($clone->getId());
            $this->assertNotInstanceOf(\Mautic\CoreBundle\Entity\TranslationEntityInterface::class, $clone->getTranslationParent());
            $this->assertSame(0, $clone->hasTranslations());
            $clone->setAlias($original->getAlias().'-clone');
            $this->em->persist($clone);
        }
        $this->em->flush();
        $this->em->clear();
        $parent = $this->em->find(Form::class, $parentId);
        $child  = $this->em->find(Form::class, $childId);
        $this->assertSame($parent, $child->getTranslationParent());
        $this->assertSame(1, $parent->hasTranslations());
    }
}
