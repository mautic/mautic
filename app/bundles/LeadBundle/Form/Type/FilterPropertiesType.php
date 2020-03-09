<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This form is filled with the LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD subscribers.
 */
class FilterPropertiesType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        // This form is loaded via AJAX as part of another form.
        // Disable CSRF protection to avoid validation errors with unexpected fileds.
        $resolver->setDefaults(['csrf_protection' => false]);
    }
}
