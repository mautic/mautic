<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Tests\Asset\AbstractAssetTest;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;
use Mautic\PageBundle\Tests\Controller\PageControllerTest;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetControllerFunctionalTest extends AbstractAssetTest
{
    use ControllerTrait;

    private const SALES_USER = 'sales';
    private const ADMIN_USER = 'admin';

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
    public function testEditWithPermissions(string $route, array $permission, int $expectedStatusCode, string $userCreatorUN): void
    {
        $userCreator = $this->getUser($userCreatorUN);
        $userEditor  = $this->getUser(self::SALES_USER);
        $this->setPermission($userEditor, ['asset:assets' => $permission]);

        $asset = new Asset();
        $asset->setTitle('Asset A');
        $asset->setAlias('asset-a');
        $asset->setStorageLocation('local');
        $asset->setPath('broken-image.jpg');
        $asset->setExtension('jpg');
        $asset->setCreatedByUser($userCreator->getUserIdentifier());
        $asset->setCreatedBy($userCreator->getId());
        $this->em->persist($asset);
        $this->em->flush();
        $this->em->clear();

        // Logout admin.
        $this->client->request(Request::METHOD_GET, '/s/logout');

        $this->loginUser(self::SALES_USER);

        $this->client->setServerParameter('PHP_AUTH_USER', self::SALES_USER);
        $this->client->request(Request::METHOD_GET, "/s/assets/{$route}/{$asset->getId()}");

        Assert::assertSame($expectedStatusCode, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return \Generator<string, mixed[]>
     */
    public function getValuesProvider(): \Generator
    {
        yield 'The sales user with edit own permission can edits its own asset' => [
            'route'              => 'edit',
            'permission'         => ['editown'],
            'expectedStatusCode' => Response::HTTP_OK,
            'userCreatorUN'      => self::SALES_USER,
        ];

        yield 'The sales user with edit own permission cannot edit asset created by admin' => [
            'route'              => 'edit',
            'permission'         => ['editown'],
            'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            'userCreatorUN'      => self::ADMIN_USER,
        ];

        yield 'The sales user with edit other permission can edit asset created by admin' => [
            'route'              => 'edit',
            'permission'         => ['editown', 'editother'],
            'expectedStatusCode' => Response::HTTP_OK,
            'userCreatorUN'      => self::ADMIN_USER,
        ];

        yield 'The sales user with view own permission cannot edit or asset created by admin' => [
            'route'              => 'edit',
            'permission'         => ['viewown'],
            'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            'userCreatorUN'      => self::ADMIN_USER,
        ];

        yield 'The sales user with view other permission cannot edit asset created by admin' => [
            'route'              => 'edit',
            'permission'         => ['viewown', 'viewother'],
            'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            'userCreatorUN'      => self::ADMIN_USER,
        ];

        yield 'The sales user with view own permission cannot view asset created by admin' => [
            'route'              => 'view',
            'permission'         => ['viewown'],
            'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            'userCreatorUN'      => self::ADMIN_USER,
        ];

        yield 'The sales user with view others permission can view asset created by admin' => [
            'route'              => 'view',
            'permission'         => ['viewown', 'viewother'],
            'expectedStatusCode' => Response::HTTP_OK,
            'userCreatorUN'      => self::ADMIN_USER,
        ];

        yield 'The sales user with view own permission can view its own asset' => [
            'route'              => 'view',
            'permission'         => ['viewown'],
            'expectedStatusCode' => Response::HTTP_OK,
            'userCreatorUN'      => self::SALES_USER,
        ];
    }

    private function getUser(string $username): User
    {
        $repository = $this->em->getRepository(User::class);

        return $repository->findOneBy(['username' => $username]);
    }

    /**
     * @param array<string, array<string, array<string>>> $permissions
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
        $roleModel = static::getContainer()->get('mautic.user.model.role');
        \assert($roleModel instanceof RoleModel);
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();
    }
}
