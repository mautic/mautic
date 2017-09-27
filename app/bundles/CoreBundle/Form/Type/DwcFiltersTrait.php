<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Entity\FiltersEntityTrait;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

trait DwcFiltersTrait
{
    protected function addFiltersField(FormBuilderInterface $builder)
    {
        $builder->add(
            'filters',
            CollectionType::class,
            [
                'entry_type'   => DwcFiltersType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'label'        => false,
                'options'      => [
                    'label'              => false,
                    'allow_extra_fields' => true,
                ],
            ]
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                /** @var FiltersEntityTrait $entity */
                $entity = $event->getForm()->getData();

                if (empty($data['filters'])) {
                    $data['filters'] = $entity->getDefaultFilters();
                    unset($data['filters']['filter']);
                    $event->setData($data);
                }
            }
        );
    }
}
