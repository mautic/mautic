<?php

namespace Mautic\PageBundle\Tests\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PageBundle\Form\Type\RedirectListType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RedirectListTypeTest extends TestCase
{
    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var RedirectListType
     */
    private $form;

    public function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->form                 = new RedirectListType($this->coreParametersHelper);
    }

    public function testGetParent()
    {
        $this->assertSame(ChoiceType::class, $this->form->getParent());
    }

    public function testConfigureOptionsChoicesUndefined()
    {
        $resolver = new OptionsResolver();
        $this->form->configureOptions($resolver);

        $expectedOptions = [
            'choices'    => [],
            'expanded'   => false,
            'multiple'   => false,
            'label'      => 'mautic.page.form.redirecttype',
            'label_attr' => [
                'class' => 'control-label',
            ],
            'placeholder' => false,
            'required'    => false,
            'attr'        => [
                'class' => 'form-control',
            ],
            'feature' => 'all',
        ];

        $this->assertSame($expectedOptions, $resolver->resolve());
    }

    public function testConfigureOptionsChoicesDefined()
    {
        $choices = [
            '1' => 'Jarda',
            '2' => 'Pepa',
        ];

        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->willReturn($choices);

        $resolver = new OptionsResolver();
        $this->form->configureOptions($resolver);

        $expectedOptions = [
            'choices'    => array_flip($choices),
            'expanded'   => false,
            'multiple'   => false,
            'label'      => 'mautic.page.form.redirecttype',
            'label_attr' => [
                'class' => 'control-label',
            ],
            'placeholder' => false,
            'required'    => false,
            'attr'        => [
                'class' => 'form-control',
            ],
            'feature' => 'all',
        ];

        $this->assertSame($expectedOptions, $resolver->resolve());
    }

    public function testGetBlockPrefix()
    {
        $this->assertSame('redirect_list', $this->form->getBlockPrefix());
    }
}
