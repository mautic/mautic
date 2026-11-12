<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\MauticEmailMarketingBundle\Client\ConstantContactClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use MauticPlugin\MauticEmailMarketingBundle\Helper\IntegrationKeys;
use MauticPlugin\MauticEmailMarketingBundle\Integration\ConstantContactIntegration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Constant Contact settings of a "Push contact to integration" action.
 *
 * @extends AbstractType<array<string, mixed>>
 */
final class ConstantContactType extends AbstractType
{
    public function __construct(
        private readonly IntegrationKeys $integrationKeys,
        private readonly ConstantContactClient $client,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $error = null;
        try {
            $lists = $this->client->getLists($this->integrationKeys->get(ConstantContactIntegration::NAME));
            asort($lists);
        } catch (ApiException $e) {
            $lists = [];
            $error = $e->getMessage();
        }

        $fields = [];
        foreach (ConstantContactIntegration::FIELDS as $field) {
            $fields[$field] = [
                'label'    => $this->translator->trans('mautic.constantcontact.field.'.$field),
                'required' => 'email' === $field,
            ];
        }
        for ($i = 1; $i <= ConstantContactIntegration::CUSTOM_FIELD_COUNT; ++$i) {
            $fields['customfield_'.$i] = [
                'label'    => $this->translator->trans('mautic.constantcontact.customfield'.$i),
                'required' => false,
            ];
        }

        $listSettings = $builder->create('list_settings', FormType::class, ['label' => false]);
        $listSettings->add('list', ChoiceType::class, [
            'choices'  => array_flip($lists),
            'label'    => 'mautic.emailmarketing.list',
            'required' => false,
            'attr'     => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.emailmarketing.list.tooltip',
            ],
        ]);
        $listSettings->add('sendWelcome', YesNoButtonGroupType::class, [
            'label' => 'mautic.emailmarketing.send_welcome',
        ]);
        $listSettings->add('leadFields', FieldMappingType::class, [
            'integration_fields' => $fields,
        ]);
        $builder->add($listSettings);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($error): void {
            $data                                 = is_array($event->getData()) ? $event->getData() : [];
            $data['list_settings']              ??= [];
            $data['list_settings']['sendWelcome'] ??= true;
            $event->setData($data);

            if (null !== $error) {
                $event->getForm()->get('list_settings')->get('list')->addError(new FormError($error));
            }
        });
    }

    public function getBlockPrefix(): string
    {
        return 'emailmarketing_constantcontact';
    }
}
