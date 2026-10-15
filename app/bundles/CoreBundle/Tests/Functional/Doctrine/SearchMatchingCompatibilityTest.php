<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\ORMSetup;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\DQL\CaseInsensitiveLike;
use Mautic\CoreBundle\Doctrine\DQL\UnicodeCi;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SearchMatchingCompatibilityTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        EnvLoader::load();
        $this->connection = DriverManager::getConnection([
            'driver'   => $_ENV['DB_DRIVER'] ?? getenv('DB_DRIVER') ?: 'pdo_mysql',
            'host'     => $_ENV['DB_HOST'] ?? getenv('DB_HOST'),
            'port'     => $_ENV['DB_PORT'] ?? getenv('DB_PORT'),
            'dbname'   => $_ENV['DB_NAME'] ?? getenv('DB_NAME'),
            'user'     => $_ENV['DB_USER'] ?? getenv('DB_USER'),
            'password' => $_ENV['DB_PASSWD'] ?? getenv('DB_PASSWD'),
        ]);
        $this->connection->beginTransaction();
        if (DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform())) {
            $schema = 'mautic_search_'.bin2hex(random_bytes(6));
            $this->connection->executeStatement('CREATE SCHEMA '.$schema);
            $this->connection->executeStatement('SET LOCAL search_path TO '.$schema.', public');
            foreach (PostgreSqlCompatibilityFunctions::getSql() as $sql) {
                $this->connection->executeStatement($sql);
            }
            $column = 'TEXT COLLATE mautic_unicode_ci';
        } else {
            $column = 'TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        }
        $this->connection->executeStatement('CREATE TEMPORARY TABLE mautic_search_parity (id INTEGER NOT NULL, value '.$column.', pattern '.$column.')');
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();
        $this->connection->close();
    }

    #[DataProvider('likeCases')]
    public function testSharedAndSegmentLikeMatchMysqlUnicodeCi(?string $value, string $pattern, bool $expected): void
    {
        $this->connection->insert('mautic_search_parity', ['id' => 1, 'value' => $value, 'pattern' => $pattern]);
        $platform = $this->connection->getDatabasePlatform();
        $expressions = new ExpressionBuilder($this->connection);
        foreach ([DatabasePlatform::getCaseInsensitiveLike($platform, 'value', 'pattern'), $expressions->like('value', 'pattern')] as $expression) {
            $this->assertSame($expected, 1 === (int) $this->connection->fetchOne('SELECT count(*) FROM mautic_search_parity WHERE '.$expression));
        }
        $this->assertSame(null !== $value && !$expected, 1 === (int) $this->connection->fetchOne('SELECT count(*) FROM mautic_search_parity WHERE '.$expressions->notLike('value', 'pattern')));
    }

    public static function likeCases(): iterable
    {
        yield 'case' => ['MiXeD', 'mixed', true];
        yield 'accent' => ['café', 'cafe', true];
        yield 'accent pattern' => ['cafe', 'CAFÉ', true];
        yield 'combining mark keeps its width' => ["cafe\u{0301}", 'cafe', false];
        yield 'contains combining mark' => ["cafe\u{0301}", '%cafe%', true];
        yield 'single accented character' => ['é', '_', true];
        yield 'combining mark is another character' => ["e\u{0301}", '_', false];
        yield 'sharp s preserves width' => ['Straße', 'strasse', false];
        yield 'capital sharp s postdates UCA4' => ['ẞ', 'ß', false];
        yield 'ligature preserves width' => ['Æther', 'aether', false];
        yield 'oe ligature preserves width' => ['œuvre', 'oeuvre', false];
        yield 'stroke is not an accent' => ['ø', 'o', false];
        yield 'accented stroke' => ['ǿ', 'ø', true];
        yield 'ring accent' => ['Å', 'a', true];
        yield 'long s' => ['ſ', 's', true];
        yield 'Greek micro' => ['µ', 'μ', true];
        yield 'Greek final sigma' => ['ς', 'σ', true];
        yield 'dotted i' => ['İ', 'i', true];
        yield 'dotless i' => ['ı', 'i', false];
        yield 'escaped percent' => ['foo%bar', 'foo\\%bar', true];
        yield 'escaped percent is literal' => ['foobar', 'foo\\%bar', false];
        yield 'escaped underscore' => ['foo_bar', 'foo\\_bar', true];
        yield 'escaped underscore is literal' => ['foobar', 'foo\\_bar', false];
        yield 'escaped backslash' => ['foo\\bar', 'foo\\\\bar', true];
        yield 'trailing escape is literal in MySQL' => ['foo\\', 'foo\\', true];
        yield 'fullwidth percent is literal' => ['foobar', 'foo％bar', false];
        yield 'fullwidth percent equivalence' => ['foo%bar', 'foo％bar', true];
        yield 'fullwidth underscore is literal' => ['foobar', 'foo＿bar', false];
        yield 'fullwidth underscore equivalence' => ['foo_bar', 'foo＿bar', true];
        yield 'supplementary same codepoint' => ['😀', '😀', true];
        yield 'supplementary distinct codepoints' => ['😀', '😁', false];
        yield 'supplementary mathematical letter' => ['𝔸', 'A', false];
        yield 'supplementary music symbol' => ['𝄞', '𝄢', false];
        yield 'unassigned supplementary' => ["\u{1FFFF}", "\u{1FFFE}", false];
        yield 'empty' => ['', '', true];
        yield 'null stays unknown' => [null, '%', false];
    }

    public function testEveryGeneratedCharacterEquivalenceMatches(): void
    {
        $mapping = json_decode(file_get_contents(__DIR__.'/../../../Resources/unicode/utf8mb4_unicode_ci.json'), true, 512, JSON_THROW_ON_ERROR);
        $characters = preg_split('//u', $mapping['from'], -1, PREG_SPLIT_NO_EMPTY);
        $equivalents = preg_split('//u', $mapping['to'], -1, PREG_SPLIT_NO_EMPTY);
        foreach ($characters as $id => $character) {
            // Pattern metacharacters remain wildcards; compare their escaped literal form.
            $pattern = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $equivalents[$id]);
            $this->connection->insert('mautic_search_parity', ['id' => $id, 'value' => $character, 'pattern' => $pattern]);
        }
        $expression = DatabasePlatform::getCaseInsensitiveLike($this->connection->getDatabasePlatform(), 'value', 'pattern');
        $this->assertSame([], $this->connection->fetchFirstColumn('SELECT id FROM mautic_search_parity WHERE NOT ('.$expression.')'));
    }

    public function testExplicitLikeEscapeCharacter(): void
    {
        $this->connection->insert('mautic_search_parity', ['id' => 1, 'value' => 'foo%bar']);
        $this->connection->insert('mautic_search_parity', ['id' => 2, 'value' => 'foobar']);
        $expression = (new ExpressionBuilder($this->connection))->like('value', ':pattern', $this->connection->quote('!'));

        $this->assertSame([1], array_map(intval(...), $this->connection->fetchFirstColumn('SELECT id FROM mautic_search_parity WHERE '.$expression, ['pattern' => 'foo!%bar'])));
    }

    public function testOrmSearchUsesTheSameCharacterEquivalences(): void
    {
        $this->connection->insert('mautic_search_parity', ['id' => 1, 'value' => 'CAFÉ']);
        $this->connection->insert('mautic_search_parity', ['id' => 2, 'value' => 'ẞ']);
        $config = ORMSetup::createAttributeMetadataConfiguration([], true);
        $config->addCustomStringFunction('mautic_unicode_ci', UnicodeCi::class);
        $config->addCustomStringFunction('mautic_like', CaseInsensitiveLike::class);
        $em = new EntityManager($this->connection, $config);
        $where = DatabasePlatform::getCaseInsensitiveLike($this->connection->getDatabasePlatform(), 'e.value', ':pattern', DatabasePlatform::FLAG_FORCE_LOWER_COLUMN | DatabasePlatform::FLAG_FORCE_LOWER_VALUE);
        $query = $em->createQuery('SELECT e.id FROM '.SearchMatchingRecord::class.' e WHERE '.$where);

        $this->assertSame([['id' => 1]], $query->setParameter('pattern', 'cafe')->getArrayResult());
        $this->assertSame([], $query->setParameter('pattern', 'ß')->getArrayResult());
    }

    #[DataProvider('forcedLikeCases')]
    public function testForcedRepositoryLikeUsesSharedMatching(string $value, string $operator, string|int $pattern, bool $strict, string $column, bool $expected): void
    {
        $this->connection->insert('mautic_search_parity', ['id' => 1, 'value' => $value]);
        $config = ORMSetup::createAttributeMetadataConfiguration([], true);
        $config->addCustomStringFunction('mautic_unicode_ci', UnicodeCi::class);
        $config->addCustomStringFunction('mautic_like', CaseInsensitiveLike::class);
        $em = new EntityManager($this->connection, $config);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);
        $repository = new CommonRepository($registry, SearchMatchingRecord::class);
        $dbal = $this->connection->createQueryBuilder()->select('e.id')->from('mautic_search_parity', 'e');
        $orm = $em->createQueryBuilder()->select('e.id')->from(SearchMatchingRecord::class, 'e');

        foreach ([$dbal, $orm] as $builder) {
            [$expression, $parameters] = $repository->getFilterExpr($builder, ['column' => 'e.'.$column, 'expr' => $operator, 'value' => $pattern, 'strict' => $strict], 'pattern');
            $builder->where($expression)->setParameters($parameters);
            $rows = $builder === $dbal ? $dbal->executeQuery()->fetchAllAssociative() : $orm->getQuery()->getArrayResult();
            $this->assertSame($expected, 1 === count($rows));
        }
    }

    /**
     * @return iterable<string, array{string, string, string|int, bool, string, bool}>
     */
    public static function forcedLikeCases(): iterable
    {
        yield 'serialized field alias keeps mixed case' => [serialize(['type' => 'text', 'field' => 'field_first']), 'LIKE', '%;s:5:"field";s:11:"field_First";%', true, 'value', true];
        yield 'mixed operator case' => ['MiXeD', 'LiKe', 'mixed', true, 'value', true];
        yield 'accent insensitive' => ['CAFÉ', 'like', 'cafe', true, 'value', true];
        yield 'modern sharp S is distinct' => ['ẞ', 'LIKE', 'ß', true, 'value', false];
        yield 'negated mixed-case match' => ['MiXeD', 'NOTLIKE', 'mixed', true, 'value', false];
        yield 'negated operator with space' => ['MiXeD', 'NOT LIKE', 'other', true, 'value', true];
        yield 'negated sharp S stays distinct' => ['ẞ', 'notLike', 'ß', true, 'value', true];
        yield 'non-strict contains' => ['MiXeD', 'LIKE', 'ixe', false, 'value', true];
        yield 'strict does not add wildcards' => ['MiXeD', 'LIKE', 'ixe', true, 'value', false];
        yield 'non-strict numeric uses equality' => ['numeric', 'LIKE', 1, false, 'id', true];
        yield 'non-strict numeric negation uses inequality' => ['numeric', 'NOTLIKE', 1, false, 'id', false];
    }

    public function testRepositoryArrayWhereLikeUsesSharedMatching(): void
    {
        $this->connection->insert('mautic_search_parity', ['id' => 1, 'value' => 'CAFÉ']);
        $this->connection->insert('mautic_search_parity', ['id' => 2, 'value' => null]);
        $config = ORMSetup::createAttributeMetadataConfiguration([], true);
        $config->addCustomStringFunction('mautic_like', CaseInsensitiveLike::class);
        $em = new EntityManager($this->connection, $config);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);
        $repository = new CommonRepository($registry, SearchMatchingRecord::class);

        foreach ([['like', '%cafe%', 1], ['notLike', '%cafe%', 0], ['like', '%other%', 0], ['notLike', '%other%', 1]] as [$operator, $pattern, $expected]) {
            $where = [['col' => 'value', 'expr' => $operator, 'val' => $pattern]];
            $rows = $repository->getRows(where: $where);
            $this->assertSame($expected, (int) $rows['total']);
            $this->assertCount($expected, $rows['results']);
            $this->assertCount($expected, $repository->getEntities(['filter' => ['where' => $where], 'ignore_paginator' => true]));
        }
    }

    #[DataProvider('regexpCases')]
    public function testSegmentRegexpDefaults(string $value, string $pattern, bool $expected, ?bool $mariaDbExpected = null): void
    {
        $this->connection->insert('mautic_search_parity', ['id' => 1, 'value' => $value, 'pattern' => $pattern]);
        $expression = (new ExpressionBuilder($this->connection))->regexp('value', 'pattern');
        // MySQL 8 uses ICU; MariaDB keeps PCRE class escapes and newline anchors.
        if (null !== $mariaDbExpected && str_contains((string) $this->connection->fetchOne('SELECT VERSION()'), 'MariaDB')) {
            $expected = $mariaDbExpected;
        }

        $this->assertSame($expected, 1 === (int) $this->connection->fetchOne('SELECT count(*) FROM mautic_search_parity WHERE '.$expression));
    }

    public static function regexpCases(): iterable
    {
        yield 'case insensitive' => ['WORD', '^word$', true];
        yield 'accent sensitive' => ['élan', 'elan', false];
        yield 'dot matches a normal character' => ['a-b', '^a.b$', true];
        yield 'dot excludes newline' => ["a\nb", '^a.b$', false];
        yield 'explicit dotall' => ["a\nb", '(?s)^a.b$', true];
        foreach (['carriage return' => "\r", 'next line' => "\u{0085}", 'line separator' => "\u{2028}", 'paragraph separator' => "\u{2029}"] as $label => $terminator) {
            yield 'dot excludes '.$label => ['a'.$terminator.'b', '^a.b$', false, true];
            yield 'dotall includes '.$label => ['a'.$terminator.'b', '(?s)^a.b$', true];
            yield 'character class includes '.$label => ['a'.$terminator.'b', '^a['.$terminator.']b$', true];
        }
        yield 'escaped dot remains literal' => ['a.b', '^a\.b$', true];
        yield 'escaped dot excludes carriage return' => ["a\rb", '^a\.b$', false];
        yield 'character class dot remains literal' => ['a.b', '^a[.]b$', true];
        yield 'character class dot excludes other characters' => ['axb', '^a[.]b$', false];
        yield 'negated character class dot remains literal' => ['axb', '^a[^.]b$', true];

        yield 'anchors do not imply multiline' => ["a\nb", '^b$', false];
        yield 'explicit multiline' => ["a\nb", '(?m)^b$', true];
        yield 'word boundaries' => ['WORD', '\\bword\\b', true];
        yield 'Unicode case' => ['CAFÉ', '^café$', true];
        yield 'Unicode word boundary' => ['café', '\\bcafé\\b', true];
        yield 'Greek case' => ['Σ', '^σ$', true];
        yield 'word boundary excludes substring' => ['sword', '\\bword\\b', false];
        yield 'non-word boundary' => ['sword', '\\Bword', true];
        yield 'literal backslash before b' => ['a\\b', '^a\\\\b$', true];
        yield 'b inside character class is literal' => ['b', '[\\b]', true, false];
        yield 'class escape is not backspace' => ["\x08", '[\\b]', false, true];
        yield 'word boundary after character class' => ['word', '[w]ord\\b', true];
        yield 'POSIX class' => ['word', '[[:alpha:]]+\\b', true];
        yield 'dollar before final newline' => ["a\n", '^a$', true];
        yield 'dollar before final carriage return' => ["a\r", '^a$', true, false];
        yield 'dollar before final CRLF' => ["a\r\n", '^a$', true, false];
        yield 'dollar before final Unicode separator' => ["a\u{2028}", '^a$', true, false];
        yield 'absolute end excludes final newline' => ["a\n", '^a\\z', false];
        yield 'soft end permits final newline' => ["a\n", '^a\\Z', true];
        yield 'escaped dollar is literal' => ['cost$', 'cost\\$', true];
        yield 'literal closing bracket in class' => [']word', '[]]word\\b', true];
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'mautic_search_parity')]
class SearchMatchingRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $value;
}
