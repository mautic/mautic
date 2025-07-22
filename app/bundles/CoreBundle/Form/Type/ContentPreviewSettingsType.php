<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Preview settings form used for pages and emails in detail view page.
 */
class ContentPreviewSettingsType extends AbstractType
{
    public const TYPE_EMAIL = 'email';
    public const TYPE_PAGE  = 'page';

    private const CHOICE_TYPE_TRANSLATION = 'translation';
    private const CHOICE_TYPE_VARIANT     = 'variant';

    public function __construct(private TranslatorInterface $translator, private CorePermissions $security, private UserHelper $userHelper)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $objectId     = $options['objectId'];
        $translations = $options['translations'];
        $variants     = $options['variants'];

        $this->addTranslationOrVariantChoicesElement($builder, self::CHOICE_TYPE_TRANSLATION, $translations, $objectId);
        $this->addTranslationOrVariantChoicesElement($builder, self::CHOICE_TYPE_VARIANT, $variants, $objectId);

        if ($this->security->isAdmin()
            || $this->security->hasEntityAccess(
                'lead:leads:viewown',
                'lead:leads:viewother',
                $this->userHelper->getUser()->getId()
            )) {
            $builder->add(
                'contact',
                LookupType::class,
                [
                    'attr' => [
                        'class'                => 'form-control',
                        'data-callback'        => 'activatePreviewContactLookupField',
                        'data-toggle'          => 'field-lookup',
                        'data-lookup-callback' => 'updatePreviewContactLookupListFilter',
                        'data-chosen-lookup'   => 'lead:contactList',
                        'placeholder'          => $this->translator->trans(
                            'mautic.lead.list.form.startTyping'
                        ),
                        'data-no-record-message' => $this->translator->trans(
                            'mautic.core.form.nomatches'
                        ),
                    ],
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'type'         => null,
                'objectId'     => null,
                'translations' => null,
                'variants'     => null,
            ]
        );

        $resolver->setRequired(['type', 'objectId']);
        $resolver->addAllowedValues('type', [self::TYPE_PAGE, self::TYPE_EMAIL]);
        $resolver->addAllowedTypes('objectId', 'int');
    }

    /**
     * @param mixed[] $variants
     */
    private function addTranslationOrVariantChoicesElement(
        FormBuilderInterface $builder,
        string $type,
        array $variants,
        int $objectId,
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
                    'onChange' => "Mautic.contentPreviewUrlGenerator.regenerateUrl({$objectId}, this)",
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
            $identifier .= ' - '.Locales::getName($email->getLanguage());
        }

        $identifier .= " - ID {$email->getId()}";

        return "{$email->getName()}{$identifier}";
    }
}
