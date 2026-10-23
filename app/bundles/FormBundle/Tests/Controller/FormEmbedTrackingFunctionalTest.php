<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Loading a form must not leave tracking identifiers behind when the visitor is not tracked.
 * The form uses progressive profiling and autofill, which look up the visitor while rendering.
 */
final class FormEmbedTrackingFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['form_field_autofill'] = true;

        parent::setUp();
    }

    /**
     * @return iterable<string, array{string, array<string, string>}>
     */
    public static function formLoadProvider(): iterable
    {
        // The script embed used with mautic-essential.js never tracks the visitor
        yield 'script embed' => ['/form/generate.js?id=%d', []];
        yield 'iframe embed with Global Privacy Control' => ['/form/embed/%d', ['HTTP_SEC_GPC' => '1']];
        yield 'public form page with Do Not Track' => ['/form/%d', ['HTTP_DNT' => '1']];
    }

    /**
     * Run separately because the script embed defines the global MAUTIC_NON_TRACKABLE_REQUEST constant.
     *
     * @param array<string, string> $server
     */
    #[DataProvider('formLoadProvider')]
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testLoadingFormWritesNoIdentifiers(string $url, array $server): void
    {
        $formId = $this->createProgressiveProfilingForm();

        $this->logoutUser();
        $this->client->request(Request::METHOD_GET, sprintf($url, $formId), [], [], $server);
        $this->assertResponseIsSuccessful();

        $cookieJar = $this->client->getCookieJar();
        $this->assertNotInstanceOf(Cookie::class, $cookieJar->get('mautic_device_id'));
        $this->assertNotInstanceOf(Cookie::class, $cookieJar->get('mtc_id'));
        $this->assertCount(0, $this->em->getRepository(Lead::class)->findAll());
    }

    private function createProgressiveProfilingForm(): int
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'                      => 'Embed tracking test',
            'formType'                  => 'standalone',
            'isPublished'               => true,
            'progressiveProfilingLimit' => 1,
            'fields'                    => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                    'isAutoFill'   => true,
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, (string) $this->client->getResponse()->getContent());

        return (int) json_decode((string) $this->client->getResponse()->getContent(), true)['form']['id'];
    }
}
