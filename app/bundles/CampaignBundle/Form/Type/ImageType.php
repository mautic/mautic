<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * @extends AbstractType<mixed>
 */
final class ImageType extends AbstractType
{
    private const MIME_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required'   => false,
            'mapped'     => true,
            'max_size'   => '1M',
            'mime_types' => self::MIME_TYPES,
        ]);

        $resolver->setAllowedTypes('max_size', 'string');
        $resolver->setAllowedTypes('mime_types', 'array');

        $resolver->setNormalizer('constraints', static fn (Options $options, $value): array => [
            new File(maxSize: $options['max_size'], mimeTypes: $options['mime_types']),
        ]);

        $resolver->setNormalizer('attr', static function (Options $options, $value): array {
            $accept = implode(',', $options['mime_types']);

            return array_merge(['class' => 'form-control', 'accept' => $accept], (array) $value);
        });
    }

    public function getParent(): string
    {
        return FileType::class;
    }
}
