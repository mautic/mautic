<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use FM\ElfinderBundle\Connector\ElFinderConnector;
use FM\ElfinderBundle\Loader\ElFinderLoader;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ElFinderControllerTest extends MauticMysqlTestCase
{
    #[DataProvider('xssPayloadProvider')]
    public function testXssInRenameCommandIsFixed(string $xssPayload): void
    {
        $originalElfinderLoader = self::getContainer()->get(ElFinderLoader::class);
        $elFinderLoader         = new class(self::getContainer()) extends ElFinderLoader {
            public function __construct(ContainerInterface $container)
            {
                /** @phpstan-ignore symfonyContainer.privateService, mautic.noContainerGet */
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
        };

        self::getContainer()->set(ElFinderLoader::class, $elFinderLoader);

        $encodedXss = rawurlencode($xssPayload);

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);

        $originalRequestMethod     = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        try {
            $this->client->request(
                Request::METHOD_GET,
                "/efconnect?cmd=rename&name=CHANGE.png&target={$encodedXss}&reqid=1975170e82727f"
            );

            $response = $this->client->getResponse();
            $this->assertInstanceOf(JsonResponse::class, $response);

            $content = $response->getContent();
            $this->assertNotEmpty($content, 'Response should not be empty');

            $this->assertStringNotContainsString($xssPayload, (string) $content);

            $json = json_decode((string) $content, true);
            $this->assertIsArray($json, 'Response JSON must decode properly');

            $errorString = json_encode($json);
            $this->assertStringNotContainsString($xssPayload, (string) $errorString);

            $this->assertArrayHasKey('error', $json);
        } finally {
            // Restore so this test's mutations don't leak into other tests in the same process.
            self::getContainer()->set(ElFinderLoader::class, $originalElfinderLoader);

            if (null === $originalRequestMethod) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $originalRequestMethod;
            }
        }
    }

    /**
     * @return iterable<array<string>>
     */
    public static function xssPayloadProvider(): iterable
    {
        // Basic payload
        yield ['<script>alert(1)</script>'];
        yield ['<img src=x onerror=alert(1)>'];
        yield ['"><svg/onload=alert(1)>'];

        // Protocol handlers
        yield ['javascript:alert(1)'];
        yield ['<a href="javascript:alert(1)">click me</a>'];

        // CSS injection
        yield ['<div style="background-image: url(javascript:alert(1))"></div>'];

        // SVG/MathML
        yield ['<svg/onload=alert(1)>'];
        yield ['<math><a xlink:href="javascript:alert(2)">test</a></math>'];

        // Iframe/Object
        yield ['<iframe src="javascript:alert(1)"></iframe>'];
        yield ['<object data="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=="></object>'];

        // Encoded/Obfuscated
        yield ['<scr%00ipt>alert(1)</scr%00ipt>'];
        yield ['<s%00c%00r%00i%00p%00t>alert(1)</s%00c%00r%00i%00p%00t>'];

        // Harmless but useful mutations
        yield ['<img src="x" onerror="document.body.style.backgroundColor=\'red\'">'];
        yield ['<img src="x" onerror="this.src=\'https://example.com/logo.png\'">'];
    }
}
