<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\MauticEmailMarketingBundle\Client\MailchimpClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use MauticPlugin\MauticEmailMarketingBundle\Helper\IntegrationKeys;
use MauticPlugin\MauticEmailMarketingBundle\Integration\MailchimpIntegration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Mailchimp settings of a "Push contact to integration" action.
 *
 * @extends AbstractType<array<string, mixed>>
 */
final class MailchimpType extends AbstractType
{
    public function __construct(
        private readonly IntegrationKeys $integrationKeys,
        private readonly MailchimpClient $client,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $keys  = $this->integrationKeys->get(MailchimpIntegration::NAME);
        $error = null;
        try {
            $lists = $this->client->getLists($keys);
            asort($lists);
        } catch (ApiException $e) {
            $lists = [];
            $error = $e->getMessage();
        }

        $listSettings = $builder->create('list_settings', FormType::class, ['label' => false]);
        $listSettings->add('list', ChoiceType::class, [
            'choices'  => array_flip($lists),
            'label'    => 'mautic.emailmarketing.list',
            'required' => false,
            'attr'     => [
                'class'    => 'form-control',
                'tooltip'  => 'mautic.emailmarketing.list.tooltip',
                // Reloads these settings so the field mapping shows the selected list's merge fields
                'onchange' => 'Mautic.getIntegrationConfig(mQuery(\'[name="\' + this.name.replace(\'[config][list_settings][list]\', \'[integration]\') + \'"]\')[0], {config: {list_settings: {list: this.value}}});',
            ],
        ]);
        $listSettings->add('doubleOptin', YesNoButtonGroupType::class, [
            'label' => 'mautic.mailchimp.double_optin',
        ]);
        $listSettings->add('sendWelcome', YesNoButtonGroupType::class, [
            'label' => 'mautic.emailmarketing.send_welcome',
        ]);
        $builder->add($listSettings);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($keys, $error): void {
            $data                                 = is_array($event->getData()) ? $event->getData() : [];
            $data['list_settings']              ??= [];
            $data['list_settings']['doubleOptin'] ??= true;
            $data['list_settings']['sendWelcome'] ??= true;
            $event->setData($data);

            if (null !== $error) {
                $event->getForm()->get('list_settings')->get('list')->addError(new FormError($error));
            }

            $this->addFieldMapping($event->getForm(), $keys, $data['list_settings']['list'] ?? null);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($keys): void {
            $data = $event->getData();
            $this->addFieldMapping($event->getForm(), $keys, is_array($data) ? ($data['list_settings']['list'] ?? null) : null);
        });
    }

    public function getBlockPrefix(): string
    {
        return 'emailmarketing_mailchimp';
    }

    /**
     * @param array<string, mixed> $keys
     */
    private function addFieldMapping(FormInterface $form, array $keys, mixed $listId): void
    {
        if (!is_string($listId) || '' === $listId) {
            return;
        }

        try {
            $fields = $this->client->getMergeFields($keys, $listId);
        } catch (ApiException $e) {
            $form->get('list_settings')->get('list')->addError(new FormError($e->getMessage()));

            return;
        }

        $form->get('list_settings')->add('leadFields', FieldMappingType::class, [
            'integration_fields' => $fields,
        ]);
    }
}
