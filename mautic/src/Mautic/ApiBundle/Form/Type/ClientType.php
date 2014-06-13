<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Form\Type;

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
 *
 * @package Mautic\ApiBundle\Form\Type
 */
class ClientType extends AbstractType
{

    private $translator;
    private $validator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->validator  = $factory->getValidator();;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber($this->translator->trans(
            'mautic.core.form.inform'
        )));

        $builder->add('name', 'text', array(
            'label'      => 'mautic.api.client.form.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $arrayStringTransformer = new Transformers\ArrayStringTransformer();
        $builder->add(
            $builder->create('redirectUris', 'text', array(
                'label'      => 'mautic.api.client.form.redirecturis',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.api.client.form.help.requesturis',
                )
            ))
            ->addViewTransformer($arrayStringTransformer)
        );

        $builder->add('publicId', 'text', array(
            'label'      => 'mautic.api.client.form.clientid',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'disabled'   => true,
            'required'   => false,
            'mapped'     => false,
            'data'       => $options['data']->getPublicId()
        ));

        $builder->add('secret', 'text', array(
            'label'      => 'mautic.api.client.form.clientsecret',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'disabled'   => true,
            'required'   => false
        ));

        $builder->add('save', 'submit', array(
            'label' => 'mautic.core.form.save',
            'attr'  => array(
                'class' => 'btn btn-primary',
                'icon'  => 'fa fa-check padding-sm-right'
            ),
        ));

        $builder->add('cancel', 'submit', array(
            'label' => 'mautic.core.form.cancel',
            'attr'  => array(
                'class'   => 'btn btn-danger',
                'icon'    => 'fa fa-times padding-sm-right'
            )
        ));

        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($form->has('redirectUris')) {
                foreach ($data->getRedirectUris() as $uri) {
                    $urlConstraint = new Assert\Url(array(
                        'protocols' => array('https')
                    ));
                    $urlConstraint->message = $this->translator->trans(
                        'mautic.api.client.redirecturl.invalid',
                        array('%url%' => $uri),
                        'validators'
                    );

                    $errors = $this->validator->validateValue(
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
        });

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }


    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\ApiBundle\Entity\Client'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "client";
    }
}