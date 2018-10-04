<?php
/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 * @link        http://Mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticDNCEventBundle\Form\Type;

use Mautic\CoreBundle\Factory\ModelFactory;
use MauticPlugin\MauticDNCEventBundle\Model\DNCEventModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FormFieldSelectType.
 */
class FormDNCRemoveType extends AbstractType
{
    protected $dncEventModel;

    public function __construct(ModelFactory $modelFactory, RouterInterface $router, DNCEventModel $dncEventModel)
    {
        $this->dncEventModel = $dncEventModel;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'comments',
            'text',
            [
                'label'       => 'plugin.dncevent.form.dnccoments.label',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'plugin.dncevent.form.dnccoments.msgrequired']
                    ),
                ],
            ]
        );
        $builder->add(
            'channel',
            'choice',
            [
                'choices'    => [
                    'email' => 'Email',
                    'sms'   => 'SMS',
                ],
                'label'       => 'plugin.dncevent.form.channel.label',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'plugin.dncevent.form.channel.required',
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * return alias name of form in config.php.
     */
    public function getName()
    {
        return 'dncevent_remove_type_form';
    }
}
