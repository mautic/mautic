<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\LookupType;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailPreviewSettingsType extends AbstractType
{
    private const CHOICE_TYPE_TRANSLATION = 'translation';
    private const CHOICE_TYPE_VARIANT     = 'variant';
    private const ON_CHANGE_CONTENT       = 'Mautic.emailPreview.regenerateUrl()';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translations = $options['translations'];
        $variants     = $options['variants'];

        $this->addTranslationOrVariantChoicesElement($builder, self::CHOICE_TYPE_TRANSLATION, $translations);
        $this->addTranslationOrVariantChoicesElement($builder, self::CHOICE_TYPE_VARIANT, $variants);

        $builder->add(
            'contact',
            LookupType::class,
            [
                'attr' => [
                    'onChange' => self::ON_CHANGE_CONTENT,
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'variants'     => null,
                'translations' => null,
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'email_preview_settings';
    }

    private function addTranslationOrVariantChoicesElement(
        FormBuilderInterface $builder,
        string $type,
        array $variants
    ): void {
        if (!count($variants['children'])) {
            return;
        }

        // Use order no in names to avoid missing choices with the same name
        $orderNo = 1;

        /** @var Email */
        $child = $variants['parent'];

        $variantChoices = [
            // The first will be parent one
            $this->addOrderNoToChoiceName($child->getName(), $orderNo) => $child->getId(),
        ];

        /** @var Email $child */
        foreach ($variants['children'] as $child) {
            // Add children
            ++$orderNo;
            $variantChoices[$this->addOrderNoToChoiceName($child->getName(), $orderNo)] = $child->getId();
        }

        $builder->add(
            $type,
            ChoiceType::class,
            [
                'choices' => $variantChoices,
                'attr'    => [
                    'onChange' => self::ON_CHANGE_CONTENT,
                ],
            ]
        );
    }

    private function addOrderNoToChoiceName(string $name, int $orderNo): string
    {
        return "$name ($orderNo)";
    }
}
