<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Recurr\Transformer\TranslatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterType extends AbstractType
{
    use FilterTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param TranslatorInterface $translator
     * @param RequestStack        $requestStack
     */
    public function __construct(TranslatorInterface $translator, RequestStack $requestStack)
    {
        $this->translator   = $translator;
        $this->requestStack = $requestStack;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'glue',
            'choice',
            [
                'label'   => false,
                'choices' => [
                    'and' => 'mautic.lead.list.form.glue.and',
                    'or'  => 'mautic.lead.list.form.glue.or',
                ],
                'attr' => [
                    'class'    => 'form-control not-chosen glue-select',
                    'onchange' => 'Mautic.updateFilterPositioning(this)',
                ],
            ]
        );

        $formModifier = function (FormEvent $event, $eventName) {
            $this->buildFiltersForm(
                $eventName,
                $event,
                $this->translator,
                $this->requestStack->getCurrentRequest()->attributes->get('objectId', false)
            );
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event, FormEvents::PRE_SET_DATA);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event, FormEvents::PRE_SUBMIT);
            }
        );

        $builder->add('field', 'hidden');
        $builder->add('object', 'hidden');
        $builder->add('type', 'hidden');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'timezones',
                'countries',
                'regions',
                'fields',
                'lists',
                'campaign',
                'emails',
                'deviceTypes',
                'deviceBrands',
                'deviceOs',
                'assets',
                'tags',
                'stage',
                'locales',
                'globalcategory',
            ]
        );

        $resolver->setDefaults(
            [
                'label'          => false,
                'error_bubbling' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fields'] = $options['fields'];
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadlist_filter';
    }
}
