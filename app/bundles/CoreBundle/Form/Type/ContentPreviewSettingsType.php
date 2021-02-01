<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Preview settings form used for pages and emails in detail view page.
 */
class ContentPreviewSettingsType extends AbstractType
{
    public const TYPE_EMAIL = 'email';
    public const TYPE_PAGE  = 'page';

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
        $objectId              = $options['objectId'];
        $this->onChangeContent = "Mautic.contentPreviewUrlGenerator.regenerateUrl({$objectId}, this)";
        $translations          = $options['translations'];
        $variants              = $options['variants'];

        $this->addTranslationOrVariantChoicesElement($builder, self::CHOICE_TYPE_TRANSLATION, $translations, $objectId);
        $this->addTranslationOrVariantChoicesElement($builder, self::CHOICE_TYPE_VARIANT, $variants, $objectId);

        $builder->add(
            'contact',
            LookupType::class,
            [
                'attr' => [
                    'class'                   => 'form-control',
                    'onChange'                => '', // @todo We need an action deleting value in #content_preview_settings_contact_id when selected contact in form is deleted
                    'data-callback'           => 'activateContactLookupField',
                    'data-toggle'             => 'field-lookup',
                    'data-lookup-callback'    => 'updateContactLookupListFilter',
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
                'type'         => null,
                'objectId'     => null,
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
        return 'content_preview_settings';
    }

    private function addTranslationOrVariantChoicesElement(
        FormBuilderInterface $builder,
        string $type,
        array $variants,
        int $objectId
    ): void {
        if (!count($variants['children'])) {
            return;
        }

        /** @var Email|Page */
        $child = $variants['parent'];

        $variantChoices = [
            // The first will be parent one
            $this->addOrderNoToChoiceName($child, $type) => $child->getId(),
        ];

        /** @var Email|Page $child */
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
                'placeholder'  => $this->translator->trans('mautic.core.form.chooseone'),
                'data'         => (string) $objectId,
            ]
        );
    }

    /**
     * @param Email|Page $email
     */
    private function addOrderNoToChoiceName(object $email, string $type): string
    {
        $identifier = '';

        if (self::CHOICE_TYPE_TRANSLATION === $type) {
            // Add localised translation name
            $identifier .= ' - '.Intl::getLocaleBundle()->getLocaleName($email->getLanguage());
        }

        $identifier .= " - ID {$email->getId()}";

        return "{$email->getName()}{$identifier}";
    }
}
