<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Helper;

use Symfony\Component\Translation\TranslatorInterface;

class FormHelper
{

    public static function buildForm(TranslatorInterface $translator, &$builder, $overrides = array())
    {
        $attr = array(
            'label'      => 'mautic.category.form.category',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'tooltip'     => 'mautic.core.help.autocomplete',
                'placeholder' => $translator->trans('mautic.core.form.uncategorized')
            ),
            'mapped'     => false,
            'required'   => false
        );

        $attr = array_merge($attr, $overrides);

        $builder->add('category_lookup', 'text', $attr);

        $builder->add('category', 'hidden_entity', array(
            'required'       => false,
            'repository'     => 'MauticCategoryBundle:Category',
            'error_bubbling' => false
        ));
    }
}