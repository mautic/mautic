<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PatchCompanyLogoSubscriber implements EventSubscriberInterface
{
    public const DEFAULT_VALUES = [
        'object'                  => 'company',
        'group'                   => 'core',
        'alias'                   => 'companylogourl',
        'is_required'             => false,
        'is_fixed'                => true,
        'is_visible'              => true,
        'is_short_visible'        => false,
        'is_listable'             => true,
        'is_publicity_updatable'  => false,
        'is_unique_identifier'    => false,
        'is_index'                => false,
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
            ConsoleEvents::TERMINATE => [
                ['installCompanyLogoCustomField', 0],
            ],
        ];
    }

    public function installCompanyLogoCustomField(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();
        $output  = $event->getOutput();

        if ('doctrine:migrations:migrate' !== $command->getName()) {
            return;
        }

        $existingField = $this->fieldModel->getRepository()->findOneBy([
            'alias'  => self::DEFAULT_VALUES['alias'],
            'object' => self::DEFAULT_VALUES['object'],
        ]);
        if ($existingField) {
            $this->logger->info('lead_fields entry for companylogourl already exists; nothing to do.');
            $output->writeln('<info>[notice] Migration </info><info>Migration skipped: </info><comment>lead_fields</comment><info> entry for </info><comment>companylogourl</comment><info> already exists; <comment>nothing to do.</comment></info>');

            return;
        }

        $this->createField(
            'mautic.lead.field.companylogourl',
            'url',
            [
                'limit' => 255,
            ]
        );
        $this->logger->info('Inserted lead_fields entry for companylogourl (url, company).');
        $output->writeln('<info>[notice] Migration Inserted</info> <comment>lead_fields</comment><info> entry for </info><comment>companylogourl</comment><info> (url, company).</info>');
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
