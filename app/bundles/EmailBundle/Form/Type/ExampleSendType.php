<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ExampleSendType extends AbstractType
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * ExampleSendType constructor.
     *
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $leads = $this->leadModel->getEntities(
            [
                'limit'          => 25,
                'filter'         => $options['filter'],
                'orderBy'        => 'l.firstname,l.lastname,l.company,l.email',
                'orderByDir'     => 'ASC',
                'withTotalCount' => false,
            ]
        );

        $leadChoices = [];
        foreach ($leads as $l) {
            $leadChoices[$l->getId()] = $l->getPrimaryIdentifier();
        }

        $builder->add(
            'lead_to_example',
            'choice',
            [
                'choices'     => $leadChoices,
                'label'       => 'mautic.email.send.example.contact',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => false,
                'empty_value' => '',
                'attr'        => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'emails',
            'sortablelist',
            [
                'entry_type'       => EmailType::class,
                'label'            => 'mautic.email.example_recipients',
                'add_value_button' => 'mautic.email.add_recipient',
                'option_notblank'  => true,
            ]
        );

        $builder->add(
            'buttons',
            'form_buttons',
            [
                'apply_text' => false,
                'save_text'  => 'mautic.email.send',
                'save_icon'  => 'fa fa-send',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['filter']);
    }
}
