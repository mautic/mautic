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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ClientType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Session
     */
    private $session;

    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        Session $session,
        RouterInterface $router
    ) {
        $this->translator   = $translator;
        $this->validator    = $validator;
        $this->requestStack = $requestStack;
        $this->session      = $session;
        $this->router       = $router;
    }

    /**
     * @return bool|mixed
     */
    private function getApiMode()
    {
        return $this->requestStack->getCurrentRequest()->get(
            'api_mode',
            $this->session->get('mautic.client.filter.api_mode', 'oauth1a')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $apiMode = $this->getApiMode();
        $builder->addEventSubscriber(new CleanFormSubscriber([]));
        $builder->addEventSubscriber(new FormExitSubscriber('api.client', $options));

        if (!$options['data']->getId()) {
            $builder->add(
                'api_mode',
                ChoiceType::class,
                [
                    'mapped'     => false,
                    'label'      => 'mautic.api.client.form.auth_protocol',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'onchange' => 'Mautic.refreshApiClientForm(\''.$this->router->generate('mautic_client_action', ['objectAction' => 'new']).'\', this)',
                    ],
                    'choices' => [
                        'OAuth 1.0a' => 'oauth1a',
                        'OAuth 2'    => 'oauth2',
                    ],
                    'required'          => false,
                    'placeholder'       => false,
                    'data'              => $apiMode,
                ]
            );
        }

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        if ('oauth2' == $apiMode) {
            $arrayStringTransformer = new Transformers\ArrayStringTransformer();
            $builder->add(
                $builder->create(
                    'redirectUris',
                    TextType::class,
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
                TextType::class,
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
                TextType::class,
                [
                    'label'      => 'mautic.api.client.form.clientsecret',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'disabled'   => true,
                    'required'   => false,
                ]
            );

            $builder->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    $form = $event->getForm();
                    $data = $event->getData();

                    if ($form->has('redirectUris')) {
                        foreach ($data->getRedirectUris() as $uri) {
                            $urlConstraint = new OAuthCallback();
                            $urlConstraint->message = $this->translator->trans(
                                'mautic.api.client.redirecturl.invalid',
                                ['%url%' => $uri],
                                'validators'
                            );

                            $errors = $this->validator->validate($uri, $urlConstraint);

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
                    TextType::class,
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
                TextType::class,
                [
                    'label'      => 'mautic.api.client.form.consumerkey',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'onclick'  => 'this.setSelectionRange(0, this.value.length);',
                        'readonly' => true,
                    ],
                    'required'  => false,
                    'mapped'    => false,
                    'data'      => $options['data']->getConsumerKey(),
                ]
            );

            $builder->add(
                'consumerSecret',
                TextType::class,
                [
                    'label'      => 'mautic.api.client.form.consumersecret',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'onclick'  => 'this.setSelectionRange(0, this.value.length);',
                        'readonly' => true,
                    ],
                    'required'  => false,
                ]
            );

            $builder->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    $form = $event->getForm();
                    $data = $event->getData();

                    if ($form->has('callback')) {
                        $uri = $data->getCallback();
                        $urlConstraint = new OAuthCallback();
                        $urlConstraint->message = $this->translator->trans('mautic.api.client.redirecturl.invalid', ['%url%' => $uri], 'validators');

                        $errors = $this->validator->validate($uri, $urlConstraint);

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
        $apiMode   = $this->getApiMode();
        $dataClass = ('oauth2' == $apiMode) ? 'Mautic\ApiBundle\Entity\oAuth2\Client' : 'Mautic\ApiBundle\Entity\oAuth1\Consumer';
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
