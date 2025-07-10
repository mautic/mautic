<?php

namespace Mautic\CoreBundle\Form\Type;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Form\ChoiceLoader\EntityLookupChoiceLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class EntityLookupType extends AbstractType
{
    /**
     * @var EntityLookupChoiceLoader[]
     */
    private ?array $choiceLoaders = null;

    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        private ModelFactory $modelFactory,
        private TranslatorInterface $translator,
        private Connection $connection,
        private RouterInterface $router,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Let the form builder notify us about initial/submitted choices
        $formModifier = function (FormEvent $event): void {
            $options = $event->getForm()->getConfig()->getOptions();
            $model   = $this->getModelName($options);
            $this->choiceLoaders[$model]->setOptions($options);
            $this->choiceLoaders[$model]->onFormPostSetData($event);
        };

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $formModifier
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $formModifier
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['model', 'ajax_lookup_action']);
        $resolver->setDefined(['model_lookup_method', 'repo_lookup_method', 'lookup_arguments', 'model_key']);
        $resolver->setDefaults(
            [
                'modal_route'            => false,
                'modal_route_parameters' => ['objectAction' => 'new'],
                'modal_header'           => '',
                'force_popup'            => false,
                'entity_label_column'    => 'name',
                'entity_id_column'       => 'id',
                'choice_loader'          => function (Options $options) {
                    // This class is defined as a service therefore the choice loader has to be unique per field that inherits this class as a parent
                    // if you have multiple lookup fields with same type then use different - 2 'key' for all fields
                    $model                       = $this->getModelName($options);
                    $this->choiceLoaders[$model] = new EntityLookupChoiceLoader(
                        $this->modelFactory,
                        $this->translator,
                        $this->connection,
                        $options
                    );

                    return $this->choiceLoaders[$model];
                },
                'choice_translation_domain' => false,
                'expanded'                  => false,
                'multiple'                  => false,
                'required'                  => false,
                'placeholder'               => '',
            ]
        );
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
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
                    'data-new-route'          => $this->router->generate($options['modal_route'], $options['modal_route_parameters']),
                    'data-header'             => $options['modal_header'] ? $this->translator->trans($options['modal_header']) : 'false',
                    'data-chosen-placeholder' => $this->translator->trans('mautic.core.lookup.search_options', [], 'javascript'),
                ]
            );
        }

        if ($options['force_popup']) {
            $attr['data-popup'] = 'true';
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], $attr);
    }

    /**
     * @param Options<mixed[]>|array<mixed> $options
     */
    private function getModelName($options): string
    {
        $key = $options['model_key'] ?? null;
        if (!$key) {
            return $options['model'];
        }

        return sprintf('%s.%s', $options['model'], $key);
    }
}
