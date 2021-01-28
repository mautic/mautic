<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\LookupType;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class EmailPreviewSettingsType extends AbstractType
{
    private const CHOICE_TYPE_TRANSLATION = 'translation';
    private const CHOICE_TYPE_VARIANT     = 'variant';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $onChangeContent;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $emailId               = $options['emailId'];
        $this->onChangeContent = "Mautic.emailPreview.regenerateUrl({$emailId})";
        $translations          = $options['translations'];
        $variants              = $options['variants'];

        $this->addTranslationOrVariantChoicesElement($builder, self::CHOICE_TYPE_TRANSLATION, $translations);
        $this->addTranslationOrVariantChoicesElement($builder, self::CHOICE_TYPE_VARIANT, $variants);

        $builder->add(
            'contact',
            LookupType::class,
            [
                'attr' => [
                    'class'                   => 'form-control',
                    'data-callback'           => 'activateContactLookupField',
                    'data-toggle'             => 'field-lookup',
                    'data-lookup-callback'    => 'updateLookupListFilter',
                    'data-chosen-lookup'      => 'lead:contactList',
                    'placeholder'             => $this->translator->trans(
                        'mautic.lead.list.form.startTyping'
                    ),
                    'data-no-record-message'=> $this->translator->trans(
                        'mautic.core.form.nomatches'
                    ),
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
                'placeholder' => $this->translator->trans('mautic.core.form.chooseone'),
            ]
        );
    }

    private function addOrderNoToChoiceName(Email $email, string $type): string
    {
        $identifier = '';

        switch ($type) {
            case self::CHOICE_TYPE_TRANSLATION:
                $identifier = Intl::getLocaleBundle()->getLocaleName($email->getLanguage());
                break;
            case self::CHOICE_TYPE_VARIANT:
                $identifier = "ID {$email->getId()}";
                break;
        }

        return "{$email->getName()} - $identifier";
    }
}
