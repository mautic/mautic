<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Form\Type;

use Mautic\PageBundle\Form\Type\PointActionUrlHitType;
use Mautic\PointBundle\Form\Type\PointActionType;
use Symfony\Component\Form\Test\TypeTestCase;

final class PointActionUrlHitTypeTest extends TypeTestCase
{
    public function testBuildFormWithEmptyData(): void
    {
        $form = $this->factory->create(PointActionType::class, [], ['formType' => PointActionUrlHitType::class]);

        $children = $form->createView()->children['properties']->children;

        $this->assertArrayHasKey('accumulative_time', $children);
        $this->assertArrayHasKey('returns_within', $children);
        $this->assertArrayHasKey('returns_after', $children);
        $this->assertSame('H', $children['accumulative_time_unit']->vars['value']);
    }
}
