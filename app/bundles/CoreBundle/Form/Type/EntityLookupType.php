<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
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
     * @var ModelFactory
     */
    private $modelFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Router
     */
    private $router;

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
        $this->modelFactory = $modelFactory;
        $this->translator   = $translator;
        $this->connection   = $connection;
        $this->router       = $router;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if (!$data) {
                    return;
                }

                $options            = $form->getConfig()->getOptions();
                $options['choices'] = $this->getChoices($data, $options);

                $form->getParent()->add(
                    $form->getName(),
                    $form->getConfig()->getType()->getName(),
                    $options
                );
            }
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
                'choices'                => function (Options $options) {
                    $data = (isset($options['data'])) ? $options['data'] : [];

                    return $this->getChoices($data, $options);
                },
                'expanded'               => false,
                'multiple'               => false,
                'required'               => false,
                'empty_value'            => function (Options $options) {
                    if (empty($options['modal_route'])) {
                        return $this->translator->trans('mautic.core.lookup.search_options', [], 'javascript');
                    }

                    return false;
                },
                'attr'                   => function (Options $options) {
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

    /**
     * @param $data
     * @param $options
     *
     * @return array
     */
    protected function getChoices($data, $options)
    {
        $labelColumn = $options['entity_label_column'];
        $idColumn    = $options['entity_id_column'];
        $model       = $options['model'];
        $modalRoute  = (!empty($options['modal_route'])) ? $options['modal_route'] : false;

        if (empty($data)) {
            return ($modalRoute) ? ['new' => $this->translator->trans('mautic.core.createnew')] : [];
        }

        array_map(
            function ($v) {
                return (int) $v;
            },
            $data
        );

        if (!$this->modelFactory->hasModel($model)) {
            throw new \InvalidArgumentException("$model not found as a registered model service.");
        }
        $model = $this->modelFactory->getModel($model);
        if (!$model instanceof AjaxLookupModelInterface) {
            throw new \InvalidArgumentException(get_class($model)." must implement ".AjaxLookupModelInterface::class);
        }

        $alias     = $model->getRepository()->getTableAlias();
        $expr      = new ExpressionBuilder($this->connection);
        $composite = $expr->andX();
        $composite->add(
            $expr->in($alias.".id", $data)
        );

        $validChoices = $model->getRepository()->getSimpleList($composite, [], $labelColumn, $idColumn);
        $choices      = [];
        foreach ($validChoices as $choice) {
            $choices[$choice['value']] = $choice['label'];
        }

        return ($modalRoute) ? array_replace(['new' => $this->translator->trans('mautic.core.createnew')], $choices) : $choices;
    }
}
