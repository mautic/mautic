<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Service;

use FM\ElfinderBundle\Connector\ElFinderConnector;
use FM\ElfinderBundle\Loader\ElFinderLoader;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class LocalFileAdapterServiceTest extends MauticMysqlTestCase
{
    /**
     * @var string
     */
    private $folderName;

    protected function beforeTearDown(): void
    {
        $pathsHelper = static::getContainer()->get('mautic.helper.paths');
        $folderPath  = "{$pathsHelper->getImagePath()}/$this->folderName";

        if (is_dir($folderPath)) {
            rmdir($folderPath);
        }
    }

    public function testElfinderCreateFolderPermissions(): void
    {
        $elFinderLoader = new class(static::getContainer()) extends ElFinderLoader {
            public function __construct(ContainerInterface $container)
            {
                parent::__construct($container->get('fm_elfinder.configurator'));
            }

            /**
             * @return array<mixed>
             */
            public function load(Request $request)
            {
                $connector = new ElFinderConnector($this->bridge);

                return $connector->execute($request->query->all());
            }
        };

        static::getContainer()->set('fm_elfinder.loader', $elFinderLoader);

        $this->folderName = (string) time();
        $user             = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_POST;
        $this->client->request(
            Request::METHOD_POST,
            "efconnect?cmd=mkdir&name=$this->folderName&target=fls1_Lw"
        );
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        /** @var PathsHelper $pathsHelper */
        $pathsHelper = static::getContainer()->get('mautic.helper.paths');
        $folderPath  = "{$pathsHelper->getImagePath()}/$this->folderName";
        self::assertDirectoryExists($folderPath);
        self::assertSame('777', substr(sprintf('%o', fileperms($folderPath)), -3));
    }
}
