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
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    public function testAssetSizes(): void
    {
        $this->client->request('GET', '/s/ajax?action=email:getAttachmentsSize&assets%5B%5D='.$this->asset->getId());
        $this->assertResponseIsSuccessful();
        Assert::assertSame('{"size":"178 bytes"}', $this->client->getResponse()->getContent());
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

        $this->logoutUser();

        $this->loginUser($userEditor);

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

    public function testAssetUploadPathTraversal(): void
    {
        $client    = $this->client;
        $container = $this->getContainer();

        // Get CSRF token
        $csrfToken = $container->get('security.csrf.token_manager')->getToken('mautic_ajax_post')->getValue();

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '111');

        // Prepare the file for upload
        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $tmpDir = 'tmp_'.substr(md5(uniqid()), 0, 13);
        $client->request(
            'POST',
            '/s/_uploader/asset/upload',
            ['tempId' => '../../'.$tmpDir],
            ['file'   => $uploadedFile],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'HTTP_X-CSRF-Token'     => $csrfToken,
            ]
        );

        $response = $client->getResponse();

        // Assert response is successful
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Decode JSON response
        $responseData = json_decode($response->getContent(), true);

        // Assert the response contains expected keys
        $this->assertArrayHasKey('tmpFileName', $responseData);

        // Assert file was created in the correct directory
        $expectedDir      = $container->getParameter('mautic.upload_dir').join('/', ['', 'tmp', $tmpDir]);
        $expectedFilePath = join('/', [$expectedDir, $responseData['tmpFileName']]);
        $this->assertFileExists($expectedFilePath);

        // Clean up
        if (file_exists($expectedFilePath)) {
            unlink($expectedFilePath);
        }
        if (is_dir($expectedDir)) {
            rmdir($expectedDir);
        }
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
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

    public function testPostRequestWithWrongTempNameAndOriginalFileNameFileExtension(): void
    {
        $response = $this->client->request(
            Request::METHOD_GET,
            '/s/assets/new',
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $form                              = $response->filter('form[name="asset"]')->form();
        $data                              = $form->getPhpValues();
        $data['asset']['tempName']         = 'image2.php';
        $data['asset']['originalFileName'] = 'originalImage2.php';
        $data['asset']['storageLocation']  = 'local';
        $data['asset']['title']            = 'title';
        $data['asset']['description']      = 'description';
        $this->client->submit($form, $data);
        preg_match_all('/Upload failed as the file extension, php/', $this->client->getResponse()->getContent(), $matches);
        $this->assertCount(2, $matches[0]);
        $this->assertStringContainsString('Upload failed as the file extension, php', $this->client->getResponse()->getContent());
    }

    public function testPostRequestWithWrongTempNameFileExtension(): void
    {
        $response = $this->client->request(
            Request::METHOD_GET,
            '/s/assets/new',
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $form                              = $response->filter('form[name="asset"]')->form();
        $data                              = $form->getPhpValues();
        $data['asset']['tempName']         = 'image2.php';
        $data['asset']['originalFileName'] = 'originalImage2.png';
        $data['asset']['storageLocation']  = 'local';
        $data['asset']['title']            = 'title';
        $data['asset']['description']      = 'description';
        $this->client->submit($form, $data);
        preg_match_all('/Upload failed as the file extension, php/', $this->client->getResponse()->getContent(), $matches);
        $this->assertCount(1, $matches[0]);
        $this->assertStringContainsString('Upload failed as the file extension, php', $this->client->getResponse()->getContent());
    }

    public function testPostResquetSuccessWithCorrectFileExtension(): void
    {
        $response = $this->client->request(
            Request::METHOD_GET,
            '/s/assets/new',
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $form                              = $response->filter('form[name="asset"]')->form();
        $data                              = $form->getPhpValues();
        $data['asset']['tempName']         = 'image.png';
        $data['asset']['originalFileName'] = 'originalImage.png';
        $data['asset']['storageLocation']  = 'local';
        $data['asset']['title']            = 'title';
        $data['asset']['description']      = 'description';
        $this->client->submit($form, $data);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringNotContainsString('Upload failed as the file extension, php', $this->client->getResponse()->getContent());
    }
}
