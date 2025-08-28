<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\AssetRepository;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\CoreBundle\Translation\Translator;
use PHPUnit\Framework\TestCase;

class AssetRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private AssetRepository $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject&Translator */
    private $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->configureRepository(Asset::class);

        $this->translator = $this->createMock(Translator::class);
        $this->translator->method('trans')->willReturnCallback(
            fn (string $key, array $parameters = [], ?string $domain = null, ?string $locale = null) => match ($key) {
                'mautic.asset.asset.searchcommand.isimage'    => 'is:image',
                'mautic.asset.asset.searchcommand.isvideo'    => 'is:video',
                'mautic.asset.asset.searchcommand.isaudio'    => 'is:audio',
                'mautic.asset.asset.searchcommand.isdocument' => 'is:document',
                default                                       => $key,
            }
        );
        $this->repository->setTranslator($this->translator);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function mimeCommandProvider(): iterable
    {
        yield ['mautic.asset.asset.searchcommand.isimage', 'image/%'];
        yield ['mautic.asset.asset.searchcommand.isvideo', 'video/%'];
        yield ['mautic.asset.asset.searchcommand.isaudio', 'audio/%'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mimeCommandProvider')]
    public function testAddSearchCommandWhereClauseForMimeCommands(string $commandKey, string $expectedValue): void
    {
        $filter = (object) [
            'command' => $this->translator->trans($commandKey),
            'string'  => '',
            'not'     => false,
            'strict'  => false,
        ];

        $q = new QueryBuilder($this->connection);

        $method = new \ReflectionMethod(AssetRepository::class, 'addSearchCommandWhereClause');
        $method->setAccessible(true);
        [$expr, $parameters] = $method->invoke($this->repository, $q, $filter);

        $this->assertCount(1, $parameters);
        $paramName = array_key_first($parameters);
        $this->assertSame($expectedValue, $parameters[$paramName]);
        $this->assertSame("a.mime LIKE :$paramName", $expr);
    }

    public function testAddSearchCommandWhereClauseForDocument(): void
    {
        $filter = (object) [
            'command' => $this->translator->trans('mautic.asset.asset.searchcommand.isdocument'),
            'string'  => '',
            'not'     => false,
            'strict'  => false,
        ];

        $q = new QueryBuilder($this->connection);

        $method = new \ReflectionMethod(AssetRepository::class, 'addSearchCommandWhereClause');
        $method->setAccessible(true);
        [$expr, $parameters] = $method->invoke($this->repository, $q, $filter);

        $this->assertCount(2, $parameters);
        $keys = array_keys($parameters);
        $this->assertSame('application/%', $parameters[$keys[0]]);
        $this->assertSame('text/%', $parameters[$keys[1]]);
        $this->assertSame(
            sprintf('(a.mime LIKE :%s OR a.mime LIKE :%s)', $keys[0], $keys[1]),
            $expr
        );
    }
}
