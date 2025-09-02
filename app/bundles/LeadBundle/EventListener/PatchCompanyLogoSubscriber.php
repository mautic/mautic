<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Controller\CompanyController;
use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class PatchCompanyLogoSubscriber implements EventSubscriberInterface
{
    public const DEFAULT_VALUES = [
        'object'                  => 'company',
        'group'                   => 'core',
        'alias'                   => 'companylogourl',
        'is_required'             => 0,
        'is_fixed'                => 1,
        'is_visible'              => 1,
        'is_short_visible'        => 0,
        'is_listable'             => 1,
        'is_publicity_updatable'  => 0,
        'is_unique_identifier'    => 0,
        'is_index'                => 0,
    ];

    public function __construct(
        private \Mautic\LeadBundle\Model\FieldModel $fieldModel,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Symfony\Contracts\Translation\TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => [
                ['installCompanyLogoCustomField', 0],
            ],
        ];
    }

    public function installCompanyLogoCustomField(\Symfony\Component\HttpKernel\Event\ControllerEvent $event): void
    {
        if (!array_key_exists(0, (array) $event->getController()) || !$event->getController()[0] instanceof CompanyController) {
            return;
        }
        $existingField = $this->fieldModel->getRepository()->findOneBy([
            'alias'  => self::DEFAULT_VALUES['alias'],
            'object' => self::DEFAULT_VALUES['object'],
        ]);
        if ($existingField) {
            return;
        }
        $this->createField(
            'mautic.lead.field.companylogourl',
            'url',
            [
                'limit' => 255,
            ]
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createField(string $label, string $type, array $options = []): void
    {
        // Create new field
        $properties = [];
        if (!empty($options['properties'])) {
            $properties = $options['properties'];
        }

        $label = $this->translator->trans($label);

        $field = new LeadField();
        $field->setAlias(self::DEFAULT_VALUES['alias']);
        $field->setLabel($label);
        $field->setType($type);
        $field->setObject(self::DEFAULT_VALUES['object']);
        $field->setGroup(self::DEFAULT_VALUES['group']);
        $field->setIsRequired(self::DEFAULT_VALUES['is_required']);
        $field->setIsFixed(self::DEFAULT_VALUES['is_fixed']);
        $field->setIsVisible(self::DEFAULT_VALUES['is_visible']);
        $field->setIsShortVisible(self::DEFAULT_VALUES['is_short_visible']);
        $field->setIsListable(self::DEFAULT_VALUES['is_listable']);
        $field->setIsPubliclyUpdatable(self::DEFAULT_VALUES['is_publicity_updatable']);
        $field->setIsUniqueIdentifier(self::DEFAULT_VALUES['is_unique_identifier']);
        $field->setIsIndex(self::DEFAULT_VALUES['is_index']);

        if (!empty($options['limit']) && is_int($options['limit'])) {
            $field->setCharLengthLimit($options['limit']);
        }

        if (!empty($properties) && is_array($properties)) {
            $result = $this->fieldModel->setFieldProperties($field, $properties);
        }

        try {
            $this->fieldModel->saveEntity($field);
        } catch (\Exception $e) {
            $this->logger->error('Field could not be saved : '.$e->getMessage());
        }
    }
}
