<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\DataTransformer\EmojiToShortTransformer;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class DynamicContentType
 *
 * @package Mautic\DynamicContentBundle\Form\Type
 */
class DynamicContentType extends AbstractType
{
    private $translator;
    private $request;
    private $em;
    private $dwcChoices = [];

    public function __construct(
        TranslatorInterface $translator,
        CorePermissions $security,
        DynamicContentModel $dynamicContentModel,
        RequestStack $requestStack,
        EntityManager $entityManager
    )
    {
        $this->translator = $translator;
        $this->request = $requestStack->getCurrentRequest();
        $this->em = $entityManager;

        // Emails
        $viewOther  = $security->isGranted('dynamicContent:dynamicContents:viewother');
        $entities   = $dynamicContentModel->getRepository()->getDynamicContentList('', 0, 0, $viewOther);
        
        foreach ($entities as $entity) {
            $this->dwcChoices[$entity['language']][$entity['id']] = $entity['name'];
        }

        ksort($this->dwcChoices);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['content' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('dynamicContent.dynamicContent', $options));

        $builder->add(
            'name',
            'text',
            array(
                'label'      => 'mautic.dynamicContent.form.internal.name',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            )
        );

        $emojiTransformer = new EmojiToShortTransformer();
        $builder->add(
            $builder->create(
                'description',
                'textarea',
                array(
                    'label'      => 'mautic.dynamicContent.description',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array('class' => 'form-control'),
                    'required'   => false
                )
            )->addModelTransformer($emojiTransformer)
        );

        $builder->add('isPublished', 'yesno_button_group');

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
            'content',
            'textarea',
            array(
                'label'      => 'mautic.dynamicContent.form.content',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'tooltip'              => 'mautic.dynamicContent.form.content.help',
                    'class'                => 'form-control editor-advanced editor-builder-tokens',
                    'rows'                 => '15'
                ),
                'required'   => false
            )
        );


        $transformer = new IdToEntityModelTransformer($this->em, 'MauticDynamicContentBundle:DynamicContent');
        $builder->add(
            $builder->create(
                'variantParent',
                'dwc_list',
                [
                    'label' => 'mautic.dynamicContent.form.variantParent',
                    'label_attr' => ['class' => 'control-label'],
                    'attr' => [
                        'class' => 'form-control',
                        'tooltip' => 'mautic.dynamicContent.form.variantParent.help'
                    ],
                    'required' => false,
                    'multiple' => false,
                    'empty_value' => 'mautic.dynamicContent.form.variantParent.empty',
                    'ignore_ids' => [(int) $options['data']->getId()]
                ]
            )->addModelTransformer($transformer)
        );

        $builder->add(
            'category',
            'category',
            ['bundle' => 'dynamicContent']
        );

        $builder->add(
            'buttons',
            'form_buttons'
        );


        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['data_class' => 'Mautic\DynamicContentBundle\Entity\DynamicContent']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "dwc";
    }
}