<?php

namespace Mautic\PageBundle\Tests\Form\Type;

use Mautic\PageBundle\Form\Type\RedirectListType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RedirectListTypeTest extends TestCase
{
    private RedirectListType $form;

    protected function setUp(): void
    {
        $this->form = new RedirectListType();
    }

    public function testGetParent(): void
    {
        $this->assertSame(ChoiceType::class, $this->form->getParent());
    }

    public function testConfigureOptionsChoicesDefined(): void
    {
        $choices = [
            'mautic.page.form.redirecttype.permanent'     => 301,
            'mautic.page.form.redirecttype.temporary'     => 302,
            'mautic.page.form.redirecttype.303_temporary' => 303,
            'mautic.page.form.redirecttype.307_temporary' => 307,
            'mautic.page.form.redirecttype.308_permanent' => 308,
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
