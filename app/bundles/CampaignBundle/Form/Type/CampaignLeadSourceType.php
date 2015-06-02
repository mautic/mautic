<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class CampaignLeadSourceType
 */
class CampaignLeadSourceType extends AbstractType
{
    /**
     * @var
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __constuct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sourceType = $options['data']['sourceType'];

        switch ($sourceType) {
            case 'lists':
                $builder->add(
                    'lists',
                    'choice',
                    array(
                        'choices'     => $options['source_choices'],
                        'multiple'    => true,
                        'label'       => 'mautic.campaign.leadsource.lists',
                        'label_attr'  => array('class' => 'control-label'),
                        'attr'        => array(
                            'class'   => 'form-control'
                        ),
                        'constraints' => array(
                            new NotBlank(
                                array(
                                    'message' => 'mautic.core.value.required'
                                )
                            )
                        )
                    )
                );
                break;
            case 'forms':
                $builder->add(
                    'forms',
                    'choice',
                    array(
                        'choices'     => $options['source_choices'],
                        'multiple'    => true,
                        'label'       => 'mautic.campaign.leadsource.forms',
                        'label_attr'  => array('class' => 'control-label'),
                        'attr'        => array(
                            'class'   => 'form-control'
                        ),
                        'constraints' => array(
                            new NotBlank(
                                array(
                                    'message' => 'mautic.core.value.required'
                                )
                            )
                        )
                    )
                );
                break;
            default:
                break;
        }

        $builder->add('sourceType', 'hidden');

        $builder->add('droppedX', 'hidden');

        $builder->add('droppedY', 'hidden');

        $update = !empty($options['data'][$sourceType]);
        if (!empty($update)) {
            $btnValue = 'mautic.core.form.update';
            $btnIcon  = 'fa fa-pencil';
        } else {
            $btnValue = 'mautic.core.form.add';
            $btnIcon  = 'fa fa-plus';
        }

        $builder->add('buttons', 'form_buttons', array(
            'save_text' => $btnValue,
            'save_icon' => $btnIcon,
            'save_onclick' => 'Mautic.submitCampaignSource(event)',
            'apply_text' => false,
            'container_class' => 'bottom-form-buttons'
        ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('source_choices'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "campaign_leadsource";
    }
}