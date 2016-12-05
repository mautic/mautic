<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Form\ChoiceLoader\EntityLookupChoiceLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class EntityLookupType.
 */
class EntityLookupType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var EntityLookupChoiceLoader
     */
    private $choiceLoader;

    /**
     * EntityLookupType constructor.
     *
     * @param ModelFactory        $modelFactory
     * @param TranslatorInterface $translator
     * @param Connection          $connection
     * @param Router              $router
     */
    public function __construct(ModelFactory $modelFactory, TranslatorInterface $translator, Connection $connection, Router $router)
    {
        $this->translator   = $translator;
        $this->router       = $router;
        $this->choiceLoader = new EntityLookupChoiceLoader($modelFactory, $translator, $connection);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Let the form builder notify us about initial/submitted choices
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            [$this->choiceLoader, 'onFormPostSetData']
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            [$this->choiceLoader, 'onFormPostSetData']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['model', 'ajax_lookup_action']);
        $resolver->setDefaults(
            [
                'modal_route'            => false,
                'modal_header'           => '',
                'entity_label_column'    => 'name',
                'entity_id_column'       => 'id',
                'modal_route_parameters' => ['objectAction' => 'new'],
                'choice_loader'          => function (Options $options) {
                    $this->choiceLoader->setOptions($options);

                    return $this->choiceLoader;
                },
                'expanded'    => false,
                'multiple'    => false,
                'required'    => false,
                'empty_value' => function (Options $options) {
                    if (empty($options['modal_route'])) {
                        return $this->translator->trans('mautic.core.lookup.search_options', [], 'javascript');
                    }

                    return false;
                },
                'attr' => function (Options $options) {
                    $attr =
                        [
                            'class'              => "form-control {$options['model']}-select",
                            'data-chosen-lookup' => $options['ajax_lookup_action'],
                            'data-model'         => $options['model'],
                        ];

                    if (!empty($options['modal_route'])) {
                        $attr = array_merge(
                            $attr,
                            [
                                'data-new-route' => $this->router->generate($options['modal_route'], $options['modal_route_parameters']),
                                'data-header'    => $options['modal_header'] ? $this->translator->trans($options['modal_header']) : 'false',
                            ]
                        );
                    }

                    return $attr;
                },
            ]
        );
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}
