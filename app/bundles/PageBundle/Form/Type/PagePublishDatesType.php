<?php

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\PublishDownDateType;
use Mautic\CoreBundle\Form\Type\PublishUpDateType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class PagePublishDatesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['content' => 'html', 'customHtml' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('page.page', $options));

        $builder->add('isPublished', YesNoButtonGroupType::class);
        $builder->add('publishUp', PublishUpDateType::class);
        $builder->add('publishDown', PublishDownDateType::class);
        $builder->add('sessionId', HiddenType::class);
        $builder->add('buttons', FormButtonsType::class, [
            'container_class' => 'lead-note-buttons',
            'apply_text'      => false,
            'save_text'       => 'mautic.core.form.save',
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
