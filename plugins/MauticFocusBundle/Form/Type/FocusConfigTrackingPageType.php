<?php

declare(strict_types = 1);
/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\PageBundle\Form\Type\ConfigTrackingPageType;
use Symfony\Component\Form\FormBuilderInterface;

class FocusConfigTrackingPageType extends ConfigTrackingPageType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'focus_pixel_enabled',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.page.config.form.focus.pixel.enabled',
                'attr'  => [
                    'tooltip' => 'mautic.page.config.form.focus.pixel.enabled.tooltip',
                ],
                'data'  => ((bool) $options['data']['focus_pixel_enabled']) ?? true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'focusconfig';
    }
}
