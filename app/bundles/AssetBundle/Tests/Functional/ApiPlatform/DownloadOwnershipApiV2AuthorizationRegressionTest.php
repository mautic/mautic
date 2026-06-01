<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Functional\ApiPlatform;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\LeadBundle\Tests\Functional\ApiPlatform\OwnershipScopedApiAuthorizationTestBase;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests that Download entity correctly uses getPermissionUser() to resolve ownership
 * through its parent Asset entity, since Download doesn't have a direct createdBy field.
 */
final class DownloadOwnershipApiV2AuthorizationRegressionTest extends OwnershipScopedApiAuthorizationTestBase
{
    public function testViewOwnItemCannotReadForeignDownloadOnApiV2(): void
    {
        $ownerUser = $this->createUserWithPermissions(
            username: 'owner.user',
            email: 'owner.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'asset:assets' => ['viewother', 'editother', 'deleteother'],
                'api:access'   => ['full'],
            ]
        );
        $restrictedUser = $this->createUserWithPermissions(
            username: 'restricted.user',
            email: 'restricted.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'asset:assets' => ['viewown', 'editown', 'deleteown'],
                'api:access'   => ['full'],
            ]
        );

        // Create an asset owned by ownerUser
        $foreignAsset = new Asset();
        $foreignAsset->setTitle('Foreign Asset');
        $foreignAsset->setStorageLocation('local');
        $foreignAsset->setPath('test.pdf');
        $foreignAsset->setCreatedBy($ownerUser);
        $this->em->persist($foreignAsset);

        // Create a download for the foreign asset
        $foreignDownload = new Download();
        $foreignDownload->setAsset($foreignAsset);
        $foreignDownload->setDateDownload(new \DateTime());
        $foreignDownload->setCode(200);
        $foreignDownload->setTrackingId(789012);
        $this->em->persist($foreignDownload);

        $this->em->flush();
        $this->em->clear();

        // Authenticate as restricted user
        $restrictedUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'restricted.user']);
        \assert($restrictedUser instanceof User);
        $this->loginUser($restrictedUser);
        $this->client->setServerParameter('PHP_AUTH_USER', $restrictedUser->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        // Try to access the foreign download - should be forbidden
        $this->client->request('GET', '/api/v2/downloads/'.$foreignDownload->getId());
        $response = $this->client->getResponse();

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $response->getStatusCode(),
            'User with viewown permission should not be able to access downloads of assets they do not own. '.
            'This validates that ApiPermissionVoter correctly uses getPermissionUser() for Download entities.'
        );
    }

    public function testViewOwnCollectionCannotSeeForeignDownloadOnApiV2(): void
    {
        $ownerUser = $this->createUserWithPermissions(
            username: 'owner.collection.user',
            email: 'owner.collection.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'asset:assets' => ['viewother', 'editother', 'deleteother'],
                'api:access'   => ['full'],
            ]
        );
        $restrictedUser = $this->createUserWithPermissions(
            username: 'restricted.collection.user',
            email: 'restricted.collection.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'asset:assets' => ['viewown', 'editown', 'deleteown'],
                'api:access'   => ['full'],
            ]
        );

        // Create an asset owned by restrictedUser
        $ownAsset = new Asset();
        $ownAsset->setTitle('Own Asset');
        $ownAsset->setStorageLocation('local');
        $ownAsset->setPath('own.pdf');
        $ownAsset->setCreatedBy($restrictedUser);
        $this->em->persist($ownAsset);

        // Create a download for the own asset
        $ownDownload = new Download();
        $ownDownload->setAsset($ownAsset);
        $ownDownload->setDateDownload(new \DateTime());
        $ownDownload->setCode(200);
        $ownDownload->setTrackingId(123456);
        $this->em->persist($ownDownload);

        // Create an asset owned by ownerUser
        $foreignAsset = new Asset();
        $foreignAsset->setTitle('Foreign Asset');
        $foreignAsset->setStorageLocation('local');
        $foreignAsset->setPath('foreign.pdf');
        $foreignAsset->setCreatedBy($ownerUser);
        $this->em->persist($foreignAsset);

        // Create a download for the foreign asset
        $foreignDownload = new Download();
        $foreignDownload->setAsset($foreignAsset);
        $foreignDownload->setDateDownload(new \DateTime());
        $foreignDownload->setCode(200);
        $foreignDownload->setTrackingId(345678);
        $this->em->persist($foreignDownload);

        $this->em->flush();
        $this->em->clear();

        // Authenticate as restricted user
        $restrictedUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'restricted.collection.user']);
        \assert($restrictedUser instanceof User);
        $this->loginUser($restrictedUser);
        $this->client->setServerParameter('PHP_AUTH_USER', $restrictedUser->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        // Request downloads collection
        $this->client->request('GET', '/api/v2/downloads?page=1&itemsPerPage=10');
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $downloads = $data['member'];
        self::assertCount(1, $downloads, 'Expected exactly 1 download (only the one for the owned asset)');
        self::assertSame($ownDownload->getId(), $downloads[0]['id']);
    }

    public function testViewOwnCollectionReportsOwnedTotalAcrossPagesOnApiV2(): void
    {
        $ownerUser = $this->createUserWithPermissions(
            username: 'owner.pagination.user',
            email: 'owner.pagination.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'asset:assets' => ['viewother', 'editother', 'deleteother'],
                'api:access'   => ['full'],
            ]
        );
        $restrictedUser = $this->createUserWithPermissions(
            username: 'restricted.pagination.user',
            email: 'restricted.pagination.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'asset:assets' => ['viewown', 'editown', 'deleteown'],
                'api:access'   => ['full'],
            ]
        );

        // Create 4 assets owned by restricted user with downloads
        for ($i = 1; $i <= 4; ++$i) {
            $asset = new Asset();
            $asset->setTitle(sprintf('Restricted Asset %d', $i));
            $asset->setStorageLocation('local');
            $asset->setPath(sprintf('restricted-%d.pdf', $i));
            $asset->setCreatedBy($restrictedUser);
            $this->em->persist($asset);

            $download = new Download();
            $download->setAsset($asset);
            $download->setDateDownload(new \DateTime());
            $download->setCode(200);
            $download->setTrackingId(100000 + $i);
            $this->em->persist($download);
        }

        // Create 12 assets owned by owner user with downloads (not visible to restricted user)
        for ($i = 1; $i <= 12; ++$i) {
            $asset = new Asset();
            $asset->setTitle(sprintf('Owner Asset %d', $i));
            $asset->setStorageLocation('local');
            $asset->setPath(sprintf('owner-%d.pdf', $i));
            $asset->setCreatedBy($ownerUser);
            $this->em->persist($asset);

            $download = new Download();
            $download->setAsset($asset);
            $download->setDateDownload(new \DateTime());
            $download->setCode(200);
            $download->setTrackingId(200000 + $i);
            $this->em->persist($download);
        }

        $this->em->flush();
        $this->em->clear();

        // Authenticate as restricted user
        $restrictedUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'restricted.pagination.user']);
        \assert($restrictedUser instanceof User);
        $this->loginUser($restrictedUser);
        $this->client->setServerParameter('PHP_AUTH_USER', $restrictedUser->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        // Request page 1 with all items on one page first
        $this->client->request('GET', '/api/v2/downloads?page=1&itemsPerPage=10');
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());

        $page1Data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('member', $page1Data);
        self::assertArrayHasKey('totalItems', $page1Data);
        self::assertSame(4, $page1Data['totalItems'], 'Should report totalItems 4 owned downloads');
        self::assertCount(4, $page1Data['member'], 'Should return all 4 owned downloads on single page');
    }
}
