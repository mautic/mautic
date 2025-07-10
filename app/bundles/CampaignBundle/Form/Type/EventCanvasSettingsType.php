<?php

namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class EventCanvasSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('droppedX', HiddenType::class);

        $builder->add('droppedY', HiddenType::class);
    }

    public function getBlockPrefix(): string
    {
        return 'campaignevent_canvassettings';
    }
}
