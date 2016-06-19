<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\DataTransformer\EmojiToShortTransformer;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class EmailType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class EmailType extends AbstractType
{

    private $translator;
    private $defaultTheme;
    private $em;
    private $request;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator   = $factory->getTranslator();
        $this->defaultTheme = $factory->getParameter('theme');
        $this->em           = $factory->getEntityManager();
        $this->request      = $factory->getRequest();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html', 'customHtml' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('email.email', $options));

        $builder->add(
            'name',
            'text',
            array(
                'label'      => 'mautic.email.form.internal.name',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            )
        );

        $emojiTransformer = new EmojiToShortTransformer();
        $builder->add(
            $builder->create(
                'subject',
                'text',
                array(
                    'label'      => 'mautic.email.subject',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array('class' => 'form-control'),
                    'required'   => false
                )
            )->addModelTransformer($emojiTransformer)
        );

        $builder->add(
            'fromName',
            'text',
            array(
                'label'      => 'mautic.email.from_name',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-user',
                    'tooltip'  => 'mautic.email.from_name.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'fromAddress',
            'text',
            array(
                'label'      => 'mautic.email.from_email',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-envelope',
                    'tooltip'  => 'mautic.email.from_email.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'replyToAddress',
            'text',
            array(
                'label'      => 'mautic.email.reply_to_email',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-envelope',
                    'tooltip'  => 'mautic.email.reply_to_email.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'bccAddress',
            'text',
            array(
                'label'      => 'mautic.email.bcc',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-envelope',
                    'tooltip'  => 'mautic.email.bcc.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'template',
            'theme_list',
            array(
                'feature'     => 'email',
                'attr'        => array(
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.form.template.help',
                    'onchange' => 'Mautic.onBuilderModeSwitch(this);'
                ),
                'empty_value' => 'mautic.core.none'
            )
        );

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add(
            'publishUp',
            'datetime',
            array(
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishup',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime'
                ),
                'format'     => 'yyyy-MM-dd HH:mm',
                'required'   => false
            )
        );

        $builder->add(
            'publishDown',
            'datetime',
            array(
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishdown',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime'
                ),
                'format'     => 'yyyy-MM-dd HH:mm',
                'required'   => false
            )
        );

        $builder->add(
            'plainText',
            'textarea',
            array(
                'label'      => 'mautic.email.form.plaintext',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'tooltip'              => 'mautic.email.form.plaintext.help',
                    'class'                => 'form-control',
                    'rows'                 => '15',
                    'data-token-callback'  => 'email:getBuilderTokens',
                    'data-token-activator' => '{',
                    'data-token-visual'    => 'false'
                ),
                'required'   => false
            )
        );

        $builder->add(
            $builder->create(
                'customHtml',
                'textarea',
                array(
                    'label'      => 'mautic.email.form.body',
                    'label_attr' => array('class' => 'control-label'),
                    'required'   => false,
                    'attr'       => array(
                        'class'                => 'form-control editor-fullpage editor-builder-tokens',
                        'data-token-callback'  => 'email:getBuilderTokens',
                        'data-token-activator' => '{'
                    )
                )
            )->addModelTransformer($emojiTransformer)
        );

        $transformer = new IdToEntityModelTransformer($this->em, 'MauticFormBundle:Form', 'id');
        $builder->add(
            $builder->create(
                'unsubscribeForm',
                'form_list',
                array(
                    'label'       => 'mautic.email.form.unsubscribeform',
                    'label_attr'  => array('class' => 'control-label'),
                    'attr'        => array(
                        'class'            => 'form-control',
                        'tootlip'          => 'mautic.email.form.unsubscribeform.tooltip',
                        'data-placeholder' => $this->translator->trans('mautic.core.form.chooseone')
                    ),
                    'required'    => false,
                    'multiple'    => false,
                    'empty_value' => '',
                )
            )
                ->addModelTransformer($transformer)
        );


        $transformer = new IdToEntityModelTransformer($this->em, 'MauticEmailBundle:Email');
        $builder->add(
            $builder->create(
                'variantParent',
                'hidden'
            )->addModelTransformer($transformer)
        );

        $url = $this->request->getSchemeAndHttpHost().$this->request->getBasePath();
        $formModifier = function(FormEvent $event, $eventName, $isVariant) use ($url) {
            if (FormEvents::PRE_SUBMIT == $eventName) {
                $parser = new PlainTextHelper(
                    array(
                        'base_url' => $url
                    )
                );

                $data = $event->getData();

                // Then strip out HTML
                $data['plainText'] = $parser->setHtml($data['plainText'])->getText();
                $event->setData($data);
            }

            if ($isVariant) {
                $event->getForm()->add(
                    'variantSettings',
                    'emailvariant',
                    array(
                        'label' => false
                    )
                );
            }
        };

        // Building the form
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $formModifier(
                    $event,
                    FormEvents::PRE_SET_DATA,
                    $event->getData()->getVariantParent()
                );
            }
        );

        // After submit
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier(
                    $event,
                    FormEvents::PRE_SUBMIT,
                    $data['variantParent']
                );
            }
        );

        //add category
        $builder->add(
            'category',
            'category',
            array(
                'bundle' => 'email'
            )
        );

        //add lead lists
        $transformer = new IdToEntityModelTransformer($this->em, 'MauticLeadBundle:LeadList', 'id', true);
        $builder->add(
            $builder->create(
                'lists',
                'leadlist_choices',
                array(
                    'label'      => 'mautic.email.form.list',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class' => 'form-control'
                    ),
                    'multiple'   => true,
                    'expanded'   => false,
                    'required'   => true
                )
            )
                ->addModelTransformer($transformer)
        );

        $builder->add(
            'language',
            'locale',
            array(
                'label'      => 'mautic.core.language',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class' => 'form-control'
                ),
                'required'   => false,
            )
        );

        //add lead lists
        $transformer = new IdToEntityModelTransformer(
            $this->em,
            'MauticAssetBundle:Asset',
            'id',
            true
        );
        $builder->add(
            $builder->create('assetAttachments', 'asset_list', array(
                'label'      => 'mautic.email.attachments',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class' => 'form-control',
                    'onchange' => 'Mautic.getTotalAttachmentSize();'
                ),
                'multiple' => true,
                'expanded' => false
            ))
                ->addModelTransformer($transformer)
        );

        $builder->add('sessionId', 'hidden');
        $builder->add('emailType', 'hidden');

        $customButtons = array(
            array(
                'name'  => 'builder',
                'label' => 'mautic.core.builder',
                'attr'  => array(
                    'class'   => 'btn btn-default btn-dnd btn-nospin text-primary btn-builder',
                    'icon'    => 'fa fa-cube',
                    'onclick' => "Mautic.launchBuilder('emailform', 'email');"
                )
            )
        );

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                'form_buttons',
                array(
                    'apply_text'        => false,
                    'pre_extra_buttons' => $customButtons
                )
            );
            $builder->add(
                'updateSelect',
                'hidden',
                array(
                    'data'   => $options['update_select'],
                    'mapped' => false
                )
            );
        } else {
            $builder->add(
                'buttons',
                'form_buttons',
                array(
                    'pre_extra_buttons' => $customButtons
                )
            );
        }


        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Mautic\EmailBundle\Entity\Email'
            )
        );

        $resolver->setOptional(array('update_select'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "emailform";
    }
}
