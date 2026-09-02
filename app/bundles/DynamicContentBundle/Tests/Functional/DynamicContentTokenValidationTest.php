<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form as TemplateForm;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;

final class DynamicContentTokenValidationTest extends MauticMysqlTestCase
{
    use DynamicContentReOrderingTrait;
    public const INVALID_TOKEN_WITH_MISSING_CONTENT = 'Invalid Dynamic Web Content token or closing tag missing or default content missing between opening and closing tags.';

    protected function setUp(): void
    {
        $this->configParams['dynamic_content_use_token_eligibility_validation'] = ' with data set "Token eligibility validation disabled"' !== $this->dataSetAsString();

        parent::setUp();
    }

    #[DataProvider('dwcTokenDataProvider')]
    public function testDwcTokenValidation(bool $success, string $token): void
    {
        $subject = 'subject'.$token;
        $content = '<html><body><p>some text</p></body></html>';
        $form    = $this->prepareEmailForm($content, $subject);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());
        if (!$success) {
            $this->assertStringContainsString(self::INVALID_TOKEN_WITH_MISSING_CONTENT, (string) $this->client->getResponse()->getContent());
        } else {
            $this->assertStringNotContainsString(self::INVALID_TOKEN_WITH_MISSING_CONTENT, (string) $this->client->getResponse()->getContent());
        }
    }

    private function prepareEmailForm(string $content, string $subject): Form
    {
        $crawler        = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        $buttonCrawler  =  $crawler->selectButton('Save & Close');
        $form           = $buttonCrawler->form();
        $form['emailform[subject]']->setValue($subject);
        $form['emailform[name]']->setValue('Email A');
        $form['emailform[customHtml]']->setValue($content);

        return $form;
    }

    private function preparePageForm(string $content): Form
    {
        $crawler        = $this->client->request(Request::METHOD_GET, '/s/pages/new');
        $buttonCrawler  =  $crawler->selectButton('Save & Close');
        $form           = $buttonCrawler->form();
        $form['page[template]']->setValue('mautic_code_mode');
        $form['page[title]']->setValue('Page A');
        $form['page[customHtml]']->setValue($content);

        return $form;
    }

    public function testEmailWithPageRelatedToken(): void
    {
        $form      = $this->createForm();
        $formToken = sprintf('{form=%s}', $form->getId());
        $dwc       = $this->createDynamicContent(
            'DC-1', 'DC-1', 0, $formToken
        );

        $form = $this->prepareEmailForm('<div data-slot="dwc" data-param-slot-name="'.$dwc->getSlotName().'">Default content</div>', 'Email A subject ');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $errorMsg = 'The email contains disallowed token(s) &quot;'.$formToken.'&quot; in DWC ID '.$dwc->getId().'. Please remove or correct them before saving.';
        $this->assertStringContainsString($errorMsg, (string) $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, bool[]>
     */
    public static function dataUseTokenEligibilityValidation(): iterable
    {
        yield 'Token eligibility validation enabled' => [true];
        yield 'Token eligibility validation disabled' => [false];
    }

    #[DataProvider('dataUseTokenEligibilityValidation')]
    public function testPageWithEmailRelatedToken(bool $useTokenEligibilityValidation): void
    {
        $contactToken = '{contactfield=city}';
        $dwc          = $this->createDynamicContent(
            'DC-1', 'DC-1', 0, $contactToken
        );

        $form = $this->preparePageForm('<div data-slot="dwc" data-param-slot-name="'.$dwc->getSlotName().'">Default content</div>');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $errorMsg = 'The page contains disallowed token(s) &quot;'.$contactToken.'&quot; in DWC ID '.$dwc->getId().'. Please remove or correct them before saving.';
        $content  = $this->client->getResponse()->getContent();

        if ($useTokenEligibilityValidation) {
            $this->assertStringContainsString($errorMsg, (string) $content);
        } else {
            $this->assertStringNotContainsString($errorMsg, (string) $content);
        }
    }

    private function createForm(): TemplateForm
    {
        $field = new Field();
        $field->setAlias('test');
        $field->setLabel('test');
        $field->setType('text');

        $form = new TemplateForm();
        $form->setName('Test form');
        $form->setAlias('test-form');
        $form->addField(0, $field);
        $field->setForm($form);

        $this->em->persist($field);
        $this->em->persist($form);
        $this->em->flush();

        return $form;
    }

    /**
     * @return iterable<int, array{bool, string}>
     */
    public static function dwcTokenDataProvider(): iterable
    {
        yield [false, '{dwc}{dwc=DC-1}'];
        yield [false, '{dwc=DC-1}'];
        yield [false, '{dwc=DC-1}{/dwc}'];
        yield [true, '{dwc=DC-1}default{/dwc}'];
    }
}
