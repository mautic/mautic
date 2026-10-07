<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Serializer;

use Mautic\CoreBundle\Serializer\ImportEntityDenormalizer;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ImportEntityDenormalizerTest extends TestCase
{
    private DenormalizerInterface&MockObject $decorated;

    private ImportEntityDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->decorated    = $this->createMock(DenormalizerInterface::class);
        $this->denormalizer = new ImportEntityDenormalizer($this->decorated);
    }

    public function testSnakeCaseKeysAreMappedToTheEntityProperty(): void
    {
        $mapped = $this->denormalizer->mapKeys([
            'custom_html'    => '<p>Body</p>',
            'plain_text'     => 'Body',
            'preheader_text' => 'Preheader',
            'email_type'     => 'transactional',
        ]);

        $this->assertSame('<p>Body</p>', $mapped['customHtml']);
        $this->assertSame('Body', $mapped['plainText']);
        $this->assertSame('Preheader', $mapped['preheaderText']);
        $this->assertSame('transactional', $mapped['emailType']);
    }

    public function testOriginalKeysArePreserved(): void
    {
        $mapped = $this->denormalizer->mapKeys(['custom_html' => '<p>Body</p>']);

        $this->assertArrayHasKey('custom_html', $mapped, 'Callers may still read the exported key.');
        $this->assertSame('<p>Body</p>', $mapped['custom_html']);
    }

    public function testSingleWordKeysAreLeftAlone(): void
    {
        $data   = ['name' => 'Newsletter', 'subject' => 'Hello', 'uuid' => 'abc'];
        $mapped = $this->denormalizer->mapKeys($data);

        $this->assertSame($data, $mapped);
    }

    /**
     * @return \Iterator<string, array{string, string}>
     */
    public static function aliasProvider(): \Iterator
    {
        yield 'language is not a case conversion' => ['lang', 'language'];
        yield 'form attributes' => ['form_attr', 'formAttributes'];
        yield 'field container attributes' => ['container_attr', 'containerAttributes'];
        yield 'field input attributes' => ['input_attr', 'inputAttributes'];
        yield 'field label attributes' => ['label_attr', 'labelAttributes'];
        yield 'form field order' => ['field_order', 'order'];
        yield 'custom field group' => ['field_group', 'group'];
    }

    #[DataProvider('aliasProvider')]
    public function testExplicitAliases(string $exported, string $property): void
    {
        $mapped = $this->denormalizer->mapKeys([$exported => 'value']);

        $this->assertSame('value', $mapped[$property]);
    }

    public function testAnExistingCanonicalKeyIsNotOverwritten(): void
    {
        $mapped = $this->denormalizer->mapKeys([
            'custom_html' => 'exported',
            'customHtml'  => 'canonical',
        ]);

        $this->assertSame('canonical', $mapped['customHtml']);
    }

    public function testNonStringKeysAreIgnored(): void
    {
        $mapped = $this->denormalizer->mapKeys([0 => 'first', 'custom_html' => '<p>Body</p>']);

        $this->assertSame('first', $mapped[0]);
        $this->assertSame('<p>Body</p>', $mapped['customHtml']);
    }

    /**
     * @return \Iterator<string, array{string, class-string}>
     */
    public static function aliasTargetProvider(): \Iterator
    {
        yield 'lang on an email' => ['lang', Email::class];
        yield 'form attributes' => ['form_attr', Form::class];
        yield 'field container attribute' => ['container_attr', Field::class];
        yield 'field input attribute' => ['input_attr', Field::class];
        yield 'field label attribute' => ['label_attr', Field::class];
        yield 'form field order' => ['field_order', Field::class];
        yield 'custom field order' => ['field_order', LeadField::class];
        yield 'custom field group' => ['field_group', LeadField::class];
    }

    /**
     * The alias list is maintained by hand, which is exactly how the original bug slipped in.
     * If an entity property is ever renamed, this fails instead of silently dropping the field
     * again.
     *
     * @param class-string $entityClass
     */
    #[DataProvider('aliasTargetProvider')]
    public function testEveryAliasPointsAtAnExistingSetter(string $exported, string $entityClass): void
    {
        $mapped = array_keys($this->denormalizer->mapKeys([$exported => 'value']));
        $target = end($mapped);

        $this->assertTrue(
            method_exists($entityClass, 'set'.ucfirst((string) $target)),
            sprintf('%s::set%s() does not exist, so "%s" would be dropped on import.', $entityClass, ucfirst((string) $target), $exported)
        );
    }

    /**
     * These describe the install the export came from, not the entity, so a copy must not
     * inherit them.
     */
    public function testStateOfTheSourceInstallIsNotMapped(): void
    {
        $mapped = $this->denormalizer->mapKeys([
            'unique_hits'           => 1234,
            'variant_hits'          => 567,
            'column_is_not_created' => true,
        ]);

        $this->assertArrayNotHasKey('uniqueHits', $mapped);
        $this->assertArrayNotHasKey('variantHits', $mapped);
        $this->assertArrayNotHasKey('columnIsNotCreated', $mapped);
    }

    public function testDenormalizePassesTheMappedPayloadToTheDecoratedService(): void
    {
        $email = new Email();

        $received = [];

        $this->decorated->expects($this->once())
            ->method('denormalize')
            ->willReturnCallback(static function (array $data) use (&$received, $email): Email {
                $received = $data;

                return $email;
            });

        $result = $this->denormalizer->denormalize(
            ['email_type' => 'transactional'],
            Email::class,
            null,
            ['object_to_populate' => $email]
        );

        $this->assertSame($email, $result);
        $this->assertSame('transactional', $received['emailType'] ?? null);
    }
}
