<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Form\Type;

use Mautic\ApiBundle\Form\Validator\Constraints\OAuthCallback;
use Mautic\CoreBundle\Form\DataTransformer as Transformers;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ValidatorInterface;

/**
 * Class ClientType.
 */
class ClientType extends AbstractType
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @var \Symfony\Component\Validator\Validator
     */
    private $validator;

    /**
     * @var bool|mixed
     */
    private $apiMode;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * Constructor.
     *
     * @param RequestStack        $requestStack
     * @param TranslatorInterface $translator
     * @param ValidatorInterface  $validator
     * @param Session             $session
     * @param RouterInterface     $router
     */
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        Session $session,
        RouterInterface $router
    ) {
        $this->translator = $translator;
        $this->validator  = $validator;
        $this->apiMode    = $requestStack->getCurrentRequest()->get(
            'api_mode',
            $session->get('mautic.client.filter.api_mode', 'oauth1a')
        );
        $this->router     = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber('api.client', $options));

        if (!$options['data']->getId()) {
            $builder->add(
                'api_mode',
                'choice',
                [
                    'mapped'     => false,
                    'label'      => 'mautic.api.client.form.auth_protocol',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'onchange' => 'Mautic.refreshApiClientForm(\''.$this->router->generate('mautic_client_action', ['objectAction' => 'new']).'\', this)',
                    ],
                    'choices' => [
                        'oauth1a' => 'OAuth 1.0a',
                        'oauth2'  => 'OAuth 2',
                    ],
                    'required'    => false,
                    'empty_value' => false,
                    'data'        => $this->apiMode,
                ]
            );
        }

        $builder->add(
            'name',
            'text',
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        if ($this->apiMode == 'oauth2') {
            $arrayStringTransformer = new Transformers\ArrayStringTransformer();
            $builder->add(
                $builder->create(
                    'redirectUris',
                    'text',
                    [
                        'label'      => 'mautic.api.client.redirecturis',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.api.client.form.help.requesturis',
                        ],
                    ]
                )
                    ->addViewTransformer($arrayStringTransformer)
            );

            $builder->add(
                'publicId',
                'text',
                [
                    'label'      => 'mautic.api.client.form.clientid',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'disabled'   => true,
                    'required'   => false,
                    'mapped'     => false,
                    'data'       => $options['data']->getPublicId(),
                ]
            );

            $builder->add(
                'secret',
                'text',
                [
                    'label'      => 'mautic.api.client.form.clientsecret',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'disabled'   => true,
                    'required'   => false,
                ]
            );

            $translator = $this->translator;
            $validator  = $this->validator;

            $builder->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) use ($translator, $validator) {
                    $form = $event->getForm();
                    $data = $event->getData();

                    if ($form->has('redirectUris')) {
                        foreach ($data->getRedirectUris() as $uri) {
                            $urlConstraint = new OAuthCallback();
                            $urlConstraint->message = $translator->trans(
                                'mautic.api.client.redirecturl.invalid',
                                ['%url%' => $uri],
                                'validators'
                            );

                            $errors = $validator->validateValue(
                                $uri,
                                $urlConstraint
                            );

                            if (!empty($errors)) {
                                foreach ($errors as $error) {
                                    $form['redirectUris']->addError(new FormError($error->getMessage()));
                                }
                            }
                        }
                    }
                }
            );
        } else {
            $builder->add(
                $builder->create(
                    'callback',
                    'text',
                    [
                        'label'      => 'mautic.api.client.form.callback',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.api.client.form.help.callback',
                        ],
                        'required' => false,
                    ]
                )->addModelTransformer(new Transformers\NullToEmptyTransformer())
            );

            $builder->add(
                'consumerKey',
                'text',
                [
                    'label'      => 'mautic.api.client.form.consumerkey',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'onclick' => 'this.setSelectionRange(0, this.value.length);',
                    ],
                    'read_only' => true,
                    'required'  => false,
                    'mapped'    => false,
                    'data'      => $options['data']->getConsumerKey(),
                ]
            );

            $builder->add(
                'consumerSecret',
                'text',
                [
                    'label'      => 'mautic.api.client.form.consumersecret',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'onclick' => 'this.setSelectionRange(0, this.value.length);',
                    ],
                    'read_only' => true,
                    'required'  => false,
                ]
            );

            $translator = $this->translator;
            $validator  = $this->validator;

            $builder->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) use ($translator, $validator) {
                    $form = $event->getForm();
                    $data = $event->getData();

                    if ($form->has('callback')) {
                        $uri = $data->getCallback();
                        $urlConstraint = new OAuthCallback();
                        $urlConstraint->message = $translator->trans('mautic.api.client.redirecturl.invalid', ['%url%' => $uri], 'validators');

                        $errors = $validator->validateValue(
                            $uri,
                            $urlConstraint
                        );

                        if (!empty($errors)) {
                            foreach ($errors as $error) {
                                $form['callback']->addError(new FormError($error->getMessage()));
                            }
                        }
                    }
                }
            );
        }

        $builder->add('buttons', FormButtonsType::class);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $dataClass = ($this->apiMode == 'oauth2') ? 'Mautic\ApiBundle\Entity\oAuth2\Client' : 'Mautic\ApiBundle\Entity\oAuth1\Consumer';
        $resolver->setDefaults(
            [
                'data_class' => $dataClass,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'client';
    }
}
