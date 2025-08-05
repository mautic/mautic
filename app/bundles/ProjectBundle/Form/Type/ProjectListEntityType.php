<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class ProjectListEntityType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required'            => false,
            'multiple'            => false,
            'ajax_lookup_action'  => fn (Options $options): string => 'project:getLookupChoiceList&'.http_build_query([
                'entityType' => $options['entityType'],
                'projectId'  => $options['projectId'] ?? null,
            ]),
            'modal_route'                       => false,
            'modal_route_parameters'            => [],
            'model'                             => 'project',
            'model_lookup_method'               => 'getLookupResults',
            'lookup_arguments'                  => fn (Options $options): array => [
                'type'    => $options['entityType'],
                'filter'  => '$data',
                'limit'   => 100,
                'start'   => 0,
                'options' => [
                    'entityType' => $options['entityType'],
                    'projectId'  => $options['projectId'] ?? null,
                ],
            ],
            'entityType'          => 'email',
            'projectId'           => null,
            'label_parameters'    => [],
        ]);

        $resolver->setRequired(['entityType']);
        $resolver->setAllowedTypes('entityType', 'string');
        $resolver->setAllowedTypes('projectId', ['int', 'string', 'null']);
    }

    public function getParent(): string
    {
        return EntityLookupType::class;
    }
}
