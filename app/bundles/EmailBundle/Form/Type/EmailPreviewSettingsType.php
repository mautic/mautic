<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\SelectType;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailPreviewSettingsType extends AbstractType
{
    private const CHOICE_TYPE_TRANSLATION = 'translation';
    private const CHOICE_TYPE_VARIANT     = 'variant';

    /**
     * @var string
     */
    private $onChangeContent;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $emailId               = $options['emailId'];
        $this->onChangeContent = "Mautic.emailPreview.regenerateUrl({$emailId})";
        $translations          = $options['translations'];
        $variants              = $options['variants'];

        $this->addTranslationOrVariantChoicesElement($builder, self::CHOICE_TYPE_TRANSLATION, $translations, $emailId);
        $this->addTranslationOrVariantChoicesElement($builder, self::CHOICE_TYPE_VARIANT, $variants, $emailId);

        $builder->add(
            'contact',
            SelectType::class,
            [
                'attr' => [
                    'onChange'         => $this->onChangeContent,
                    'data-placeholder' => 'Choose contact ...',
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'emailId'      => null,
                'translations' => null,
                'variants'     => null,
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

        /** @var Email */
        $child = $variants['parent'];

        $variantChoices = [
            // The first will be parent one
            $this->addOrderNoToChoiceName($child, $type) => $child->getId(),
        ];

        /** @var Email $child */
        foreach ($variants['children'] as $child) {
            // Add children
            $variantChoices[$this->addOrderNoToChoiceName($child, $type)] = $child->getId();
        }

        $builder->add(
            $type,
            ChoiceType::class,
            [
                'choices' => $variantChoices,
                'attr'    => [
                    'onChange' => $this->onChangeContent,
                ],
                'placeholder' => 'Choose ...',
            ]
        );
    }

    private function addOrderNoToChoiceName(Email $email, string $type): string
    {
        $identifier = '';

        switch ($type) {
            case self::CHOICE_TYPE_TRANSLATION:
                $identifier = $email->getLanguage();
                break;
            case self::CHOICE_TYPE_VARIANT:
                $identifier = "ID {$email->getId()}";
                break;
        }

        return "{$email->getName()} ($identifier)";
    }
}
