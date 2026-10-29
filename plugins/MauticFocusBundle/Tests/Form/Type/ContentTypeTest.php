<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Form\Type;

use PHPUnit\Framework\MockObject\MockObject;
use MauticPlugin\MauticFocusBundle\Form\Type\ContentType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

final class ContentTypeTest extends TestCase
{
    /**
     * @var MockObject&FormBuilderInterface
     */
    private MockObject $formBuilder;

    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
    }

    public function testBuilderForm(): void
    {
        $this->formBuilder->expects($this->exactly(7))->method('add')->willReturnSelf();
        $options     = [];
        $contentType = new ContentType();
        $contentType->buildForm($this->formBuilder, $options);
    }
}
