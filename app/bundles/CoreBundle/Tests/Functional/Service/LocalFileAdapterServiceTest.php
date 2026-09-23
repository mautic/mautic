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

final class LocalFileAdapterServiceTest extends MauticMysqlTestCase
{
    private ?string $folderName = null;

    protected function beforeTearDown(): void
    {
        /** @var PathsHelper $pathsHelper */
        $pathsHelper = self::getContainer()->get(PathsHelper::class);
        $folderPath  = "{$pathsHelper->getImagePath()}/{$this->folderName}";

        if (is_dir($folderPath)) {
            rmdir($folderPath);
        }
    }

    public function testElfinderCreateFolderPermissions(): void
    {
        $elFinderLoader = new class(self::getContainer()) extends ElFinderLoader {
            public function __construct(ContainerInterface $container)
            {
                /** @phpstan-ignore symfonyContainer.privateService */
                parent::__construct($container->get('fm_elfinder.configurator'));
            }

            /**
             * @return array<mixed>
             */
            public function load(Request $request): array|string
            {
                $connector = new ElFinderConnector($this->bridge);
                $result    = $connector->execute($request->query->all());
                if (null === $result) {
                    return []; // Can't return null, so return an empty array instead
                }

                return $result;
            }

            // The real efconnect controller calls initBridge() again per-request; keep the bridge built
            // here so the hash we compute below still matches the volume the actual request will hit.
            public function initBridge(string $instance, array $efParameters): void
            {
                if (isset($this->bridge)) {
                    return;
                }

                parent::initBridge($instance, $efParameters);
            }
        };

        self::getContainer()->set('fm_elfinder.loader', $elFinderLoader);

        $this->folderName = (string) time();
        $user             = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_POST;

        // elFinder assigns volume hash prefixes from a process-wide static counter, so it can't be hardcoded here.
        // Bootstrap the bridge via a throwaway request (the config reader needs a live request context),
        // then reuse it (via the idempotent initBridge() override above) for the real mkdir request below.
        $this->client->request(Request::METHOD_POST, 'efconnect?cmd=mkdir&name=throwaway&target=');
        $rootHash = $elFinderLoader->encode('/');
        $this->assertIsString($rootHash);

        $this->client->request(
            Request::METHOD_POST,
            "efconnect?cmd=mkdir&name={$this->folderName}&target={$rootHash}"
        );
        self::assertResponseIsSuccessful();
        /** @var PathsHelper $pathsHelper */
        $pathsHelper = self::getContainer()->get(PathsHelper::class);
        $folderPath  = "{$pathsHelper->getImagePath()}/{$this->folderName}";
        $this->assertDirectoryExists($folderPath);
        $this->assertSame('777', substr(sprintf('%o', fileperms($folderPath)), -3));
    }
}
