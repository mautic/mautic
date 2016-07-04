<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Form\Type;

use Mautic\ApiBundle\Form\Validator\Constraints\OAuthCallback;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Mautic\CoreBundle\Form\DataTransformer as Transformers;

/**
 * Class ClientType
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
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    private $router;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->validator  = $factory->getValidator();
        $this->apiMode    = $factory->getRequest()->get('api_mode', $factory->getSession()->get('mautic.client.filter.api_mode', 'oauth1a'));
        $this->router     = $factory->getRouter();
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
                array(
                    'mapped'      => false,
                    'label'       => 'mautic.api.client.form.auth_protocol',
                    'label_attr'  => array('class' => 'control-label'),
                    'attr'        => array(
                        'class'    => 'form-control',
                        'onchange' => 'Mautic.refreshApiClientForm(\'' . $this->router->generate('mautic_client_action', array('objectAction' => 'new')) . '\', this)'
                    ),
                    'choices'     => array(
                        'oauth1a' => 'OAuth 1.0a',
                        'oauth2'  => 'OAuth 2'
                    ),
                    'required'    => false,
                    'empty_value' => false,
                    'data'        => $this->apiMode
                )
            );
        }

        $builder->add(
            'name',
            'text',
            array(
                'label'      => 'mautic.core.name',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            )
        );

        if ($this->apiMode == 'oauth2') {
            $arrayStringTransformer = new Transformers\ArrayStringTransformer();
            $builder->add(
                $builder->create(
                    'redirectUris',
                    'text',
                    array(
                        'label'      => 'mautic.api.client.redirecturis',
                        'label_attr' => array('class' => 'control-label'),
                        'attr'       => array(
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.api.client.form.help.requesturis',
                        )
                    )
                )
                    ->addViewTransformer($arrayStringTransformer)
            );

            $builder->add(
                'publicId',
                'text',
                array(
                    'label'      => 'mautic.api.client.form.clientid',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array('class' => 'form-control'),
                    'disabled'   => true,
                    'required'   => false,
                    'mapped'     => false,
                    'data'       => $options['data']->getPublicId()
                )
            );

            $builder->add(
                'secret',
                'text',
                array(
                    'label'      => 'mautic.api.client.form.clientsecret',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array('class' => 'form-control'),
                    'disabled'   => true,
                    'required'   => false
                )
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
                            $urlConstraint          = new OAuthCallback();
                            $urlConstraint->message = $translator->trans(
                                'mautic.api.client.redirecturl.invalid',
                                array('%url%' => $uri),
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
                    array(
                        'label'      => 'mautic.api.client.form.callback',
                        'label_attr' => array('class' => 'control-label'),
                        'attr'       => array(
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.api.client.form.help.callback',
                        ),
                        'required'   => false
                    )
                )->addModelTransformer(new Transformers\NullToEmptyTransformer())
            );

            $builder->add(
                'consumerKey',
                'text',
                array(
                    'label'      => 'mautic.api.client.form.consumerkey',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control',
                        'onclick' => 'this.setSelectionRange(0, this.value.length);'
                    ),
                    'read_only'  => true,
                    'required'   => false,
                    'mapped'     => false,
                    'data'       => $options['data']->getConsumerKey()
                )
            );

            $builder->add(
                'consumerSecret',
                'text',
                array(
                    'label'      => 'mautic.api.client.form.consumersecret',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control',
                        'onclick' => 'this.setSelectionRange(0, this.value.length);'
                    ),
                    'read_only'  => true,
                    'required'   => false
                )
            );

            $translator = $this->translator;
            $validator  = $this->validator;

            $builder->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) use ($translator, $validator) {
                    $form = $event->getForm();
                    $data = $event->getData();

                    if ($form->has('callback')) {
                        $uri                    = $data->getCallback();
                        $urlConstraint          = new OAuthCallback();
                        $urlConstraint->message = $translator->trans('mautic.api.client.redirecturl.invalid', array('%url%' => $uri), 'validators');

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

        $builder->add('buttons', 'form_buttons');

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $dataClass = ($this->apiMode == 'oauth2') ? 'Mautic\ApiBundle\Entity\oAuth2\Client' : 'Mautic\ApiBundle\Entity\oAuth1\Consumer';
        $resolver->setDefaults(
            array(
                'data_class' => $dataClass
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "client";
    }
}
