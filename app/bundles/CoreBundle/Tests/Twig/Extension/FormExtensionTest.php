<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\FormExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;

final class FormExtensionTest extends TestCase
{
    public function testRowIfExistsReturnsEmptyStringWhenFieldDoesNotExist(): void
    {
        $renderer = $this->createMock(FormRendererInterface::class);
        $renderer->expects($this->never())->method('searchAndRenderBlock');

        $extension = new FormExtension($renderer);
        $form      = new FormView();

        $this->assertSame('', $extension->rowIfExists($form, 'missing_field'));
    }

    public function testRowIfExistsRendersExistingField(): void
    {
        $form                             = new FormView();
        $childView                        = new FormView($form);
        $form->children['existing_field'] = $childView;

        $renderer = $this->createMock(FormRendererInterface::class);
        $renderer->expects($this->once())
            ->method('searchAndRenderBlock')
            ->with($childView, 'row', ['label' => 'My label'])
            ->willReturn('<div>row</div>');

        $extension = new FormExtension($renderer);

        $this->assertSame('<div>row</div>', $extension->rowIfExists($form, 'existing_field', ['label' => 'My label']));
    }
}
