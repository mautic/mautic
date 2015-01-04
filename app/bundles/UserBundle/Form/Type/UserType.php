<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserType
 */
class UserType extends AbstractType
{

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @var bool|mixed
     */
    private $supportedLanguages;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Mautic\UserBundle\Model\UserModel
     */
    private $model;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator         = $factory->getTranslator();
        $this->supportedLanguages = $factory->getParameter('supported_languages');
        $this->em                 = $factory->getEntityManager();
        $this->model              = $factory->getModel('user');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber('user.user', $options));

        $builder->add('username', 'text', array(
            'label'      => 'mautic.user.user.form.username',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'    => 'form-control',
                'preaddon' => 'fa fa-user'
            )
        ));

        $builder->add('firstName', 'text', array(
            'label'      => 'mautic.user.user.form.firstname',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('lastName',  'text', array(
            'label'      => 'mautic.user.user.form.lastname',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));


        $positions = $this->model->getLookupResults('position', null, 0, true);
        $builder->add('position',  'text', array(
            'label'      => 'mautic.user.user.form.position',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-options' => json_encode($positions)
            ),
            'required'   => false
        ));

        $builder->add('email', 'email', array(
            'label'      => 'mautic.user.user.form.email',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'    => 'form-control',
                'preaddon' => 'fa fa-envelope'
            )
        ));

        $existing = (!empty($options['data']) && $options['data']->getId());
        $placeholder = ($existing) ?
            $this->translator->trans('mautic.user.user.form.passwordplaceholder') : '';
        $required = ($existing) ? false : true;
        $builder->add('plainPassword', 'repeated', array(
            'first_name'        => 'password',
            'first_options'     => array(
                'label'      => 'mautic.user.user.form.password',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'placeholder' => $placeholder,
                    'tooltip'     => 'mautic.user.user.form.help.passwordrequirements',
                    'preaddon'    => 'fa fa-lock'
                ),
                'required'   => $required,
                'error_bubbling'    => false
            ),
            'second_name'       => 'confirm',
            'second_options'    => array(
                'label'      => 'mautic.user.user.form.passwordconfirm',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'placeholder' => $placeholder,
                    'tooltip'     => 'mautic.user.user.form.help.passwordrequirements',
                    'preaddon'    => 'fa fa-lock'
                ),
                'required'   => $required,
                'error_bubbling'    => false
            ),
            'type'              => 'password',
            'invalid_message'   => 'mautic.user.user.password.mismatch',
            'required'          => $required,
            'error_bubbling'    => false
        ));

        $builder->add('timezone', 'timezone', array(
            'label'       => 'mautic.user.user.form.timezone',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class'   => 'form-control chosen'
            ),
            'multiple'    => false,
            'empty_value' => 'mautic.user.user.form.defaulttimezone'
        ));

        $builder->add('locale', 'choice', array(
            'choices'     => $this->supportedLanguages,
            'label'       => 'mautic.user.user.form.locale',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class'   => 'form-control chosen'
            ),
            'multiple'    => false,
            'empty_value' => 'mautic.user.user.form.defaultlocale'
        ));

        if (empty($options['in_profile'])) {
            $builder->add(
                $builder->create('role', 'entity', array(
                        'label'         => 'mautic.user.user.form.role',
                        'label_attr'    => array('class' => 'control-label'),
                        'attr'          => array(
                            'class' => 'form-control chosen'
                        ),
                        'class'         => 'MauticUserBundle:Role',
                        'property'      => 'name',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('r')
                                ->where('r.isPublished = true')
                                ->orderBy('r.name', 'ASC');
                        }
                    )
                ));

            $builder->add('isPublished', 'published_button_group');

            $builder->add('buttons', 'form_buttons');

        } else {

            $builder->add('buttons', 'form_buttons', array(
                'save_text' => 'mautic.core.form.apply',
                'apply_text' => false
            ));
        }

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\UserBundle\Entity\User',
            'validation_groups' => array(
                'Mautic\UserBundle\Entity\User',
                'determineValidationGroups',
            ),
            'ignore_formexit' => false,
            'in_profile' => false
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "user";
    }
}
