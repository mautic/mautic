<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FormCaptchaHoneypotFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testHoneypotCaptchaFieldIsHiddenInGeneratedFormHtml(): void
    {
        $formId = $this->createFormWithHoneypotCaptcha();

        /** @var FormModel $formModel */
        $formModel = static::getContainer()->get(FormModel::class);
        $form      = $formModel->getEntity($formId);
        $this->assertInstanceOf(Form::class, $form);

        $html = $formModel->generateHtml($form, false);
        $this->assertStringContainsString('mauticform-honeypot', $html);

        $honeypotRow = [];
        if (preg_match('/<div[^>]*id="mauticform_[^"]*honeypot"[^>]*>/', $html, $honeypotRow)) {
            $this->assertStringContainsString('mauticform-honeypot', $honeypotRow[0], $html);
        } else {
            self::fail('Honeypot captcha row not found in generated HTML.');
        }
    }

    public function testHoneypotCaptchaFieldIsHiddenOnFormPreview(): void
    {
        $formId = $this->createFormWithHoneypotCaptcha();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/forms/preview/{$formId}");
        self::assertResponseIsSuccessful();

        $honeypotRow = $crawler->filter('[id$="_honeypot"]');
        $this->assertGreaterThan(0, $honeypotRow->count());
        $this->assertStringContainsString('mauticform-honeypot', (string) $honeypotRow->attr('class'), $crawler->html());
    }

    public function testHoneypotCaptchaFieldMergesHoneypotClassWithExistingContainerClasses(): void
    {
        $formId = $this->createFormWithHoneypotCaptcha('class="custom-class"');

        /** @var FormModel $formModel */
        $formModel = static::getContainer()->get(FormModel::class);
        $form      = $formModel->getEntity($formId);
        $this->assertInstanceOf(Form::class, $form);

        $html = $formModel->generateHtml($form, false);

        preg_match('/<div[^>]*\bid="mauticform_[^"]*honeypot"[^>]*>/', $html, $honeypotRow);
        $this->assertNotEmpty($honeypotRow, $html);
        $this->assertStringContainsString('custom-class', $honeypotRow[0], $html);
        $this->assertStringContainsString('mauticform-honeypot', $honeypotRow[0], $html);
        $this->assertSame(1, substr_count($honeypotRow[0], 'class="'), $html);
    }

    private function createFormWithHoneypotCaptcha(string $containerAttributes = ''): int
    {
        $payload = [
            'name'        => 'Honeypot captcha test form',
            'formType'    => 'standalone',
            'description' => 'Form with honeypot captcha',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'      => 'Email',
                    'alias'      => 'email',
                    'type'       => 'email',
                    'leadField'  => 'email',
                ],
                [
                    'label'      => 'Leave blank',
                    'alias'      => 'honeypot',
                    'type'       => 'captcha',
                    'properties' => [
                        'captcha' => '',
                    ],
                    'containerAttributes' => $containerAttributes,
                ],
                [
                    'label' => 'Submit',
                    'alias' => 'submit',
                    'type'  => 'button',
                ],
            ],
            'postAction' => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        return (int) $response['form']['id'];
    }
}
