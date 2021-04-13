<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Form\Type;

use Mautic\ConfigBundle\Form\Helper\RestrictionHelper;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    /**
     * @var RestrictionHelper
     */
    private $restrictionHelper;

    /**
     * @var EscapeTransformer
     */
    private $escapeTransformer;

    public function __construct(RestrictionHelper $restrictionHelper, EscapeTransformer $escapeTransformer)
    {
        $this->restrictionHelper = $restrictionHelper;
        $this->escapeTransformer = $escapeTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // TODO very dirty quick fix for https://github.com/mautic/mautic/issues/8854
        if (isset($options['data']['apiconfig']['parameters']['api_oauth2_access_token_lifetime'])
            && 3600 === $options['data']['apiconfig']['parameters']['api_oauth2_access_token_lifetime']
        ) {
            $options['data']['apiconfig']['parameters']['api_oauth2_access_token_lifetime'] = 60;
        }

        if (isset($options['data']['apiconfig']['parameters']['api_oauth2_refresh_token_lifetime'])
            && 1209600 === $options['data']['apiconfig']['parameters']['api_oauth2_refresh_token_lifetime']
        ) {
            $options['data']['apiconfig']['parameters']['api_oauth2_refresh_token_lifetime'] = 14;
        }

        foreach ($options['data'] as $config) {
            if (isset($config['formAlias']) && !empty($config['parameters'])) {
                $checkThese = array_intersect(array_keys($config['parameters']), $options['fileFields']);
                foreach ($checkThese as $checkMe) {
                    // Unset base64 encoded values
                    unset($config['parameters'][$checkMe]);
                }
                $builder->add(
                    $config['formAlias'],
                    $config['formType'],
                    [
                        'data' => $config['parameters'],
                    ]
                );

                $this->addTransformers($builder->get($config['formAlias']));
            }
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();

                foreach ($form as $configForm) {
                    foreach ($configForm as $child) {
                        $this->restrictionHelper->applyRestrictions($child, $configForm);
                    }
                }
            }
        );

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_onclick' => 'Mautic.activateBackdrop()',
                'save_onclick'  => 'Mautic.activateBackdrop()',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'config';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'fileFields' => [],
            ]
        );
    }

    private function addTransformers(FormBuilderInterface $builder): void
    {
        if (0 === $builder->count()) {
            $builder->addModelTransformer($this->escapeTransformer);

            return;
        }

        foreach ($builder as $childBuilder) {
            $this->addTransformers($childBuilder);
        }
    }
}
