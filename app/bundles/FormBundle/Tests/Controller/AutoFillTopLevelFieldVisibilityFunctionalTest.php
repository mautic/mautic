<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

final class AutoFillTopLevelFieldVisibilityFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['form_field_autofill'] = false;

        parent::setUp();
    }

    public function testTopLevelAutofillFieldRemainsVisibleWhenGlobalAutofillIsDisabled(): void
    {
        $formId = $this->createFormWithTopLevelFirstNameField();

        $lead = new Lead();
        $lead->setEmail('john@doe.com');
        $lead->setFirstname('John');
        $this->em->persist($lead);
        $this->em->flush();

        $this->logoutUser();

        // Keep the same service container for this request so tracker state survives.
        $this->client->disableReboot();

        // Boot the browser kernel and set tracker state on the same container used for HTTP requests.
        $this->client->request(Request::METHOD_GET, '/');

        /** @var ContactTracker $contactTracker */
        $contactTracker = self::getContainer()->get(ContactTracker::class);
        $contactTracker->setUseSystemContact(true);
        $contactTracker->setSystemContact($lead);

        /** @var FormModel $formModel */
        $formModel = self::getContainer()->get(FormModel::class);
        $form      = $formModel->getEntity($formId);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);
        $requestStack->push(Request::create('/form/'.$formId, Request::METHOD_GET));

        try {
            $html = $formModel->generateHtml($form, false);

            $this->assertMatchesRegularExpression('/<input[^>]*(name="mauticform\[firstname\]"[^>]*type="text"|type="text"[^>]*name="mauticform\[firstname\]")[^>]*>/', $html, $html);
            $this->assertDoesNotMatchRegularExpression('/<input[^>]*(name="mauticform\[firstname\]"[^>]*type="hidden"|type="hidden"[^>]*name="mauticform\[firstname\]")[^>]*>/', $html, $html);
        } finally {
            $contactTracker->setSystemContact();
            $contactTracker->setUseSystemContact(false);
            $requestStack->pop();
            $this->client->enableReboot();
            // Kernel boots can stack multiple handlers; PHPUnit 11.5 fails if any remain.
            // @see https://github.com/sebastianbergmann/phpunit/issues/5721
            restore_exception_handler();
        }
    }

    private function createFormWithTopLevelFirstNameField(): int
    {
        $payload = [
            'name'        => 'Top-level auto-fill visibility test',
            'alias'       => 'top_level_autofill_visibility_'.uniqid(),
            'formType'    => 'standalone',
            'description' => 'Top-level auto-fill visibility test form',
            'fields'      => [
                [
                    'label'               => 'First name',
                    'alias'               => 'firstname',
                    'type'                => 'text',
                    'alwaysDisplay'       => false,
                    'mappedField'         => 'firstname',
                    'mappedObject'        => 'contact',
                    'isAutoFill'          => true,
                    'showWhenValueExists' => false,
                ],
                [
                    'label' => 'Submit',
                    'alias' => 'submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);

        return (int) $response['form']['id'];
    }
}
