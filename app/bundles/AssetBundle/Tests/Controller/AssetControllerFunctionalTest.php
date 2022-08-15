<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Tests\Asset\AbstractAssetTest;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;
use Mautic\PageBundle\Tests\Controller\PageControllerTest;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetControllerFunctionalTest extends AbstractAssetTest
{
    use ControllerTrait;

    private const USER_EDITOR_USERNAME  = 'sales';
    private const USER_CREATOR_USERNAME = 'admin';

    /**
     * Index action should return status code 200.
     */
    public function testIndexAction(): void
    {
        $asset = new Asset();
        $asset->setTitle('test');
        $asset->setAlias('test');
        $asset->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $asset->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $asset->setCreatedByUser('Test User');

        $this->em->persist($asset);
        $this->em->flush();
        $this->em->detach($asset);

        $urlAlias   = 'assets';
        $routeAlias = 'asset';
        $column     = 'dateModified';
        $column2    = 'title';
        $tableAlias = 'a.';

        $this->getControllerColumnTests($urlAlias, $routeAlias, $column, $tableAlias, $column2);
    }

    /**
     * Preview action should return the file content.
     */
    public function testPreviewActionStreamByDefault(): void
    {
        $this->client->request('GET', '/s/assets/preview/'.$this->asset->getId());
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($this->expectedMimeType, $response->headers->get('Content-Type'));
        $this->assertNotSame($this->expectedContentDisposition.$this->asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($this->expectedPngContent, $content);
    }

    /**
     * Preview action should return the file content.
     */
    public function testPreviewActionStreamIsZero(): void
    {
        $this->client->request('GET', '/s/assets/preview/'.$this->asset->getId().'?stream=0&download=1');
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($this->expectedContentDisposition.$this->asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($this->expectedPngContent, $content);
    }

    /**
     * Preview action should return the html code.
     */
    public function testPreviewActionStreamDownloadAreZero(): void
    {
        $this->client->request('GET', '/s/assets/preview/'.$this->asset->getId().'?stream=0&download=0');
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), $content);
        $this->assertNotEquals($this->expectedPngContent, $content);
        PageControllerTest::assertTrue($response->isOk());

        $assetSlug = $this->asset->getId().':'.$this->asset->getAlias();
        PageControllerTest::assertStringContainsString(
            '/asset/'.$assetSlug,
            $content,
            'The return must contain the assert slug'
        );
    }

    /**
     * @param array<string, string[]> $permission
     *
     * @dataProvider getValuesProvider
     */
    public function testEditWithPermissions(array $permission, int $expectedStatusCode, string $userCreatorUN): void
    {
        $userCreator = $this->getUser($userCreatorUN);
        $userEditor  = $this->getUser(self::USER_EDITOR_USERNAME);
        $this->setPermission($userEditor, $permission);

        $asset = new Asset();
        $asset->setTitle('Asset A');
        $asset->setAlias('asset-a');
        $asset->setStorageLocation('local');
        $asset->setPath('broken-image.jpg');
        $asset->setExtension('jpg');
        $asset->setCreatedByUser($userCreator->getUsername());
        $asset->setCreatedBy($userCreator->getId());
        $this->em->persist($asset);
        $this->em->flush();
        $this->em->clear();

        // Logout admin.
        $this->client->request(Request::METHOD_GET, '/s/logout');

        $this->loginUser(self::USER_EDITOR_USERNAME);

        $this->client->setServerParameter('PHP_AUTH_USER', self::USER_EDITOR_USERNAME);
        $this->client->request(Request::METHOD_GET, sprintf('/s/assets/edit/%d', $asset->getId()));

        Assert::assertSame($expectedStatusCode, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return \Generator<string, mixed[]>
     */
    public function getValuesProvider(): \Generator
    {
        yield 'The sales user edits its own asset' => [
            'permission' => [
                'asset:assets' => ['editown', 'editother'],
            ],
            'expectedStatusCode' => Response::HTTP_OK,
            'userCreatorUN'      => self::USER_EDITOR_USERNAME,
        ];

        yield 'The sales user cannot edit asset created by admin' => [
            'permission' => [
                'asset:assets' => ['editown'],
            ],
            'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            'userCreatorUN'      => self::USER_CREATOR_USERNAME,
        ];

        yield 'The sales user can edit asset created by admin' => [
            'permission' => [
                'asset:assets' => ['editown', 'editother'],
            ],
            'expectedStatusCode' => Response::HTTP_OK,
            'userCreatorUN'      => self::USER_CREATOR_USERNAME,
        ];

        yield 'The sales user cannot edit or view asset created by admin' => [
            'permission' => [
                'asset:assets' => ['viewown'],
            ],
            'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            'userCreatorUN'      => self::USER_CREATOR_USERNAME,
        ];

        yield 'The sales user can edit or view asset created by admin' => [
            'permission' => [
                'asset:assets' => ['viewown', 'viewother'],
            ],
            'expectedStatusCode' => Response::HTTP_OK,
            'userCreatorUN'      => self::USER_CREATOR_USERNAME,
        ];
    }

    private function getUser(string $username): User
    {
        $repository = $this->em->getRepository(User::class);

        return $repository->findOneBy(['username' => $username]);
    }

    /**
     * @param array<string, string[]> $permissions
     */
    private function setPermission(User $user, array $permissions): void
    {
        $role = $user->getRole();

        // Delete previous permissions
        $this->em->createQueryBuilder()
            ->delete(Permission::class, 'p')
            ->where('p.bundle = :bundle')
            ->andWhere('p.role = :role_id')
            ->setParameters(['bundle' => 'asset', 'role_id' => $role->getId()])
            ->getQuery()
            ->execute();

        // Set new permissions
        $role->setIsAdmin(false);
        $roleModel = self::$container->get('mautic.user.model.role');
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();
    }
}
