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
    private \PHPUnit\Framework\MockObject\MockObject $coreParametersHelper;

    private \Mautic\PageBundle\Form\Type\RedirectListType $form;

    public function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->form                 = new RedirectListType($this->coreParametersHelper);
    }

    public function testGetParent(): void
    {
        $this->assertSame(ChoiceType::class, $this->form->getParent());
    }

    public function testConfigureOptionsChoicesDefined()
    {
        $choices = [
            'mautic.page.form.redirecttype.permanent' => 301,
            'mautic.page.form.redirecttype.temporary' => 302,
        ];

        $resolver = new OptionsResolver();
        $this->form->configureOptions($resolver);

        $expectedOptions = [
            'choices'    => $choices,
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

    public function testGetBlockPrefix(): void
    {
        $this->assertSame('redirect_list', $this->form->getBlockPrefix());
    }
}
