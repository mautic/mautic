<?php

namespace Mautic\LeadBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class FieldLogoUrlTransformer implements DataTransformerInterface
{
    public function transform($value): ?string
    {
        return $this->sanitizeUrlImage($value);
    }

    public function reverseTransform($value): ?string
    {
        return $this->sanitizeUrlImage($value);
    }

    /**
     * @param string|null $value
     */
    private function sanitizeUrlImage($value): ?string
    {
        if (null === $value || (is_string($value) && '' === $value)) {
            return $value;
        }

        // Remove leading/trailing whitespace
        $value = trim($value);

        // Check if the URL starts with http:// or https://, if not, prepend http://
        if (!str_starts_with($value, 'https://') && !str_starts_with($value, 'http://')) {
            $value = 'https://'.$value;
        }

        return $value;
    }
}
