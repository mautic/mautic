<?php
/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\MauticFocusBundle\Form\Type\FocusConfigTrackingPageType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

class FocusConfigTrackingPageTypeTest extends TestCase
{
    /**
     * @var FocusConfigTrackingPageType
     */
    private $form;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $formBuilder;

    public function setUp(): void
    {
        $this->form                 = new FocusConfigTrackingPageType();
        $this->formBuilder          = $this->createMock(FormBuilderInterface::class);
    }

    public function testBuildForm()
    {
        $options = [
            'data' => true,
        ];

        $this->formBuilder->expects($this->at(0))
            ->method('add')
            ->with(
                'focus_pixel_enabled',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.page.config.form.focus.pixel.enabled',
                    'attr'  => [
                        'tooltip' => 'mautic.page.config.form.focus.pixel.enabled.tooltip',
                    ],
                'data'  => (bool) $options['data']['focus_pixel_enabled']) ?? true,
                ]
            );

        $this->form->buildForm($this->formBuilder, $options);
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertSame('focusconfig', $this->form->getBlockPrefix());
    }
}
