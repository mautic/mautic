<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Form\Type;

use Mautic\IntegrationsBundle\Exception\InvalidFormOptionException;
use Mautic\IntegrationsBundle\Helper\FieldFilterHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class IntegrationSyncSettingsFieldMappingsType extends AbstractType
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->logger     = $logger;
        $this->translator = $translator;
    }

    /**
     * @throws InvalidFormOptionException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!is_array($options['objects'])) {
            throw new InvalidFormOptionException('objects must be an array');
        }

        /** @var ConfigFormSyncInterface $integrationObject */
        $integrationObject = $options['integrationObject'];
        if (!$integrationObject instanceof ConfigFormSyncInterface) {
            throw new InvalidFormOptionException('integrationObject must be an instance of ConfigFormSyncInterface');
        }

        $fieldFilterHelper = new FieldFilterHelper($integrationObject);

        foreach ($options['objects'] as $objectName => $objectLabel) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($integrationObject, $objectName, $fieldFilterHelper): void {
                $error = null;

                try {
                    $fieldFilterHelper->filterFieldsByPage($objectName, 1);
                } catch (\Throwable $exception) {
                    $this->logger->debug($exception->getMessage(), ['exception' => $exception]);

                    $error = $this->translator->trans($exception->getMessage());
                }

                $form = $event->getForm();
                $form->add(
                    $objectName,
                    IntegrationSyncSettingsObjectFieldMappingType::class,
                    [
                        'label'              => false,
                        'integrationFields'  => $fieldFilterHelper->getFilteredFields(),
                        'page'               => 1,
                        'keyword'            => null,
                        'totalFieldCount'    => $fieldFilterHelper->getTotalFieldCount(),
                        'object'             => $objectName,
                        'integrationObject'  => $integrationObject,
                        'error_bubbling'     => false,
                        'allow_extra_fields' => true,
                    ]
                );

                if ($error) {
                    $form[$objectName]->addError(new FormError($error));
                }
            });
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'integrationObject',
                'objects',
            ]
        );
    }
}
