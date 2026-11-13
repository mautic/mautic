<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Form\Type;

use MauticPlugin\MauticEmailMarketingBundle\Client\IcontactClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use MauticPlugin\MauticEmailMarketingBundle\Helper\IntegrationKeys;
use MauticPlugin\MauticEmailMarketingBundle\Integration\IcontactIntegration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * iContact settings of a "Push contact to integration" action.
 *
 * @extends AbstractType<array<string, mixed>>
 */
final class IcontactType extends AbstractType
{
    public function __construct(
        private readonly IntegrationKeys $integrationKeys,
        private readonly IcontactClient $client,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $keys   = $this->integrationKeys->get(IcontactIntegration::NAME);
        $errors = [];
        try {
            $lists = $this->client->getLists($keys);
            asort($lists);
        } catch (ApiException $e) {
            $lists    = [];
            $errors[] = $e->getMessage();
        }

        $fields = [];
        foreach (IcontactIntegration::FIELDS as $field) {
            $fields[$field] = [
                'label'    => $this->translator->trans('mautic.icontact.field.'.$field),
                'required' => 'email' === $field,
            ];
        }

        try {
            foreach ($this->client->getCustomFields($keys) as $id => $name) {
                $fields[IcontactIntegration::CUSTOM_FIELD_PREFIX.$id] = [
                    'label'    => $name,
                    'required' => false,
                ];
            }
        } catch (ApiException $e) {
            $errors[] = $e->getMessage();
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
        $listSettings->add('leadFields', FieldMappingType::class, [
            'integration_fields' => $fields,
        ]);
        $builder->add($listSettings);

        if ([] !== $errors) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($errors): void {
                foreach (array_unique($errors) as $error) {
                    $event->getForm()->get('list_settings')->get('list')->addError(new FormError($error));
                }
            });
        }
    }

    public function getBlockPrefix(): string
    {
        return 'emailmarketing_icontact';
    }
}
