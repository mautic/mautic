<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Redirect;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicControllerRedirectTest extends MauticMysqlTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('redirectTypeOptions')]
    public function testValidationRedirectWithoutUrl(string $redirectUrl, string $expectedMessage): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/pages/new');
        $saveButton = $crawler->selectButton('Save');
        $form       = $saveButton->form();
        $form['page[title]']->setValue('Redirect test');
        $form['page[redirectType]']->setValue((string) Response::HTTP_MOVED_PERMANENTLY);
        $form['page[redirectUrl]']->setValue($redirectUrl);
        $form['page[template]']->setValue('mautic_code_mode');

        $this->client->submit($form);

        Assert::assertStringContainsString($expectedMessage, $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function redirectTypeOptions(): iterable
    {
        yield 'redirect set, empty redirect URL' => ['', 'A value is required.'];
        yield 'redirect set, invalid redirect URL' => ['invalid.url', 'This value is not a valid URL.'];
        yield 'redirect set, valid redirect URL' => ['https://valid.url', 'Edit Page - Redirect test'];
    }

    public function testCreateRedirectWithNoUrlForExistingPages(): void
    {
        $page = new Page();
        $page->setTitle('Page A');
        $page->setAlias('page-a');
        $page->setIsPublished(false);
        $page->setRedirectType((string) Response::HTTP_MOVED_PERMANENTLY);
        $this->em->persist($page);
        $this->em->flush();

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, '/page-a');

        Assert::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('redirectUrlProvider')]
    public function testRedirectWithSpecialCharsInQuery(string $url): void
    {
        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId('57cf5a66a9f9414f301082cf0');
        $this->em->persist($redirect);
        $this->em->flush();

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, sprintf('/r/%s', $redirect->getRedirectId()));

        $response = $this->client->getResponse();
        \assert($response instanceof RedirectResponse);
        Assert::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        Assert::assertSame($url, $response->getTargetUrl());
    }

    /**
     * @return iterable<string, array<string, string>>
     */
    public static function redirectUrlProvider(): iterable
    {
        yield 'The spaces in the query part must not be encoded with plus signs.' => [
            'url' => 'https://google.com?q=this%20has%20spaces',
        ];

        yield 'The dot in the query part must not be replaced with underscore.' => [
            'url' => 'https://google.com?registrants.source=email',
        ];
    }

    public function testRedirectWithCorrectHitUrl(): void
    {
        $url            = 'https://example.com/test?registrants.source=email';
        $emailAddress   = 'testemail@domain.tld';
        $lead           = new Lead();
        $lead->setEmail($emailAddress);
        $this->em->persist($lead);

        $stat = new Stat();
        $stat->setTrackingHash('62970e83798e0668813916');
        $stat->setDateSent(new \DateTime());
        $stat->setEmailAddress($emailAddress);
        $this->em->persist($stat);

        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId('57cf5a66a9f9414f301082cf0');
        $this->em->persist($redirect);
        $this->em->flush();

        $ct = $this->getEncodedClickThroughValue($stat->getTrackingHash(), (int) $lead->getId());

        $this->logoutUser();

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, sprintf('/r/%s?ct=%s', $redirect->getRedirectId(), $ct));

        $response = $this->client->getResponse();
        \assert($response instanceof RedirectResponse);
        Assert::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        Assert::assertSame($url, $response->getTargetUrl(), 'The dots in the query part must be preserved.');

        $hit = $this->em->getRepository(Hit::class)->findOneBy(['url' => $url]);
        Assert::assertNotNull($hit);
    }

    private function getEncodedClickThroughValue(string $trackingHash, int $leadId): string
    {
        return ClickthroughHelper::encodeArrayForUrl(
            [
                'source' => [],
                'email'  => null,
                'stat'   => $trackingHash,
                'lead'   => $leadId,
            ]
        );
    }
}
