<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\Entity\DownloadRepository;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AssetDownloadFunctionalTest extends MauticMysqlTestCase
{
    public function testDownloadOfNotFoundAsset(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');

        // The 500 error happened only on the second request.
        // It happened only if the device was already tracked.
        $this->client->request(Request::METHOD_GET, '/asset/unicorn'); // returns 404 correctly
        $this->client->request(Request::METHOD_GET, '/asset/unicorn'); // returned 500 but it should return 404

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDownloadByPageId(): void
    {
        $asset = $this->createAsset();

        $contact = new Lead();
        $contact->setEmail('a@example.com');
        $this->em->persist($contact);

        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('13.13.13.13');
        $this->em->persist($ipAddress);

        $pageA = $this->createPage($asset);
        $this->createAssetDownload($pageA, 'page', $asset, $contact, $ipAddress);

        $pageB = $this->createPage($asset);
        $this->createAssetDownload($pageB, 'page', $asset, $contact, $ipAddress);

        $this->em->flush();
        $this->em->clear();

        /** @var DownloadRepository $downloadRepo */
        $downloadRepo = $this->em->getRepository(Download::class);

        $countByPages = $downloadRepo->getDownloadCountsByPage([$pageA->getId(), $pageB->getId()]);

        $this->assertCount(2, $countByPages);
    }

    public function testDownloadByEmail(): void
    {
        $asset = $this->createAsset();

        $contact = new Lead();
        $contact->setEmail('a@example.com');
        $this->em->persist($contact);

        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('13.13.13.13');
        $this->em->persist($ipAddress);

        $emailA = $this->createEmail($asset);
        $this->createAssetDownload($emailA, 'email', $asset, $contact, $ipAddress);

        $emailB = $this->createEmail($asset);
        $this->createAssetDownload($emailB, 'email', $asset, $contact, $ipAddress);

        $this->em->flush();
        $this->em->clear();

        /** @var DownloadRepository $downloadRepo */
        $downloadRepo = $this->em->getRepository(Download::class);

        $countByPages = $downloadRepo->getDownloadCountsByEmail([$emailA->getId(), $emailB->getId()]);

        $this->assertCount(2, $countByPages);
    }

    private function createAsset(): Asset
    {
        $asset = new Asset();
        $asset->setTitle('Remote Link');
        $asset->setAlias('remote-link');
        $asset->setStorageLocation('remote');
        $asset->setRemotePath('https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
        $this->em->persist($asset);

        return $asset;
    }

    private function createEmail(Asset $asset): Email
    {
        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email');
        $email->setSubject('Asset subject');
        $email->setTemplate('Blank');
        $email->setCustomHtml(sprintf('{assetlink=%s}', $asset->getId()));

        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    private function createPage(Asset $asset): Page
    {
        $page = new Page();
        $page->setAlias('my-page');
        $page->setIsPublished(true);
        $page->setTitle('My Page');
        $page->setCustomHtml(sprintf('{assetlink=%s}', $asset->getId()));
        $page->setRevision(1);
        $page->setLanguage('en');

        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }

    private function createAssetDownload(Page|Email $entity, string $type, Asset $asset, Lead $contact, IpAddress $ipAddress): void
    {
        $assetDownload = new Download();
        $assetDownload->setAsset($asset);
        $assetDownload->setLead($contact);
        $assetDownload->setSource($type);
        $assetDownload->setSourceId($entity->getId());
        $assetDownload->setDateDownload(new \DateTime());
        $assetDownload->setCode(200);
        $assetDownload->setTrackingId('13');
        $assetDownload->setIpAddress($ipAddress);

        if ('email' === $type) {
            $assetDownload->setEmail($entity);
        }

        $this->em->persist($assetDownload);
    }
}
