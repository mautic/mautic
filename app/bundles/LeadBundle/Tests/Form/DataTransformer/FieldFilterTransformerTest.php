<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Form\DataTransformer;

use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Translation\TranslatorInterface;

final class FieldFilterTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var FieldFilterTransformer
     */
    private $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->transformer = new FieldFilterTransformer($this->translator);
    }

    public function testTransform(): void
    {
        $filters = $this->transformer->transform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter' => '2020-03-17 17:22:34',
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => '2020-03-17 17:22',
                    ],
                ],
            ],
            $filters
        );
    }

    public function testTransformWithBcFilter(): void
    {
        $filters = $this->transformer->transform([
            [
                'type'   => 'datetime',
                'filter' => '2020-03-17 17:22:34',
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'filter'     => '2020-03-17 17:22:34',
                    'properties' => [
                        'filter' => '2020-03-17 17:22',
                    ],
                ],
            ],
            $filters
        );
    }

    public function testReverseTransform(): void
    {
        $filters = $this->transformer->reverseTransform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter' => '2020-03-17 17:22:34',
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => '2020-03-17 17:22',
                    ],
                ],
            ],
            $filters
        );
    }

    public function testReverseTransformWithBcFilter(): void
    {
        $filters = $this->transformer->reverseTransform([
            [
                'type'   => 'datetime',
                'filter' => '2020-03-17 17:22:34',
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'filter'     => '2020-03-17 17:22:34',
                    'properties' => [
                        'filter' => '2020-03-17 17:22',
                    ],
                ],
            ],
            $filters
        );
    }
}
