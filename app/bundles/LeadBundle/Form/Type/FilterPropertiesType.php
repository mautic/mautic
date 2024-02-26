<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This form is filled with the LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD subscribers.
 *
 * @extends AbstractType<mixed>
 */
class FilterPropertiesType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        // This form is loaded via AJAX as part of another form.
        // Disable CSRF protection to avoid validation errors with unexpected fileds.
        $resolver->setDefaults(['csrf_protection' => false]);
    }
}
