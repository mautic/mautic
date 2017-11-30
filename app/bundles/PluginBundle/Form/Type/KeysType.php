<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class SocialMediaKeysType.
 */
class KeysType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $object       = $options['integration_object'];
        $secretKeys   = $object->getSecretKeys();
        $requiredKeys = $object->getRequiredKeyFields();

        foreach ($options['integration_keys'] as $key => $label) {
            $isSecret = in_array($key, $secretKeys);
            $required = (isset($requiredKeys[$key]));

            // Password fields are going to be blank even if a value exists so only require if a password is not already saved
            if ($isSecret && !empty($options['data'][$key])) {
                $required = false;
            }

            $constraints = ($required)
                ? [
                    new Callback(
                        function ($validateMe, ExecutionContextInterface $context) use ($options) {
                            if (empty($validateMe) && !empty($options['is_published'])) {
                                $context->buildViolation('mautic.core.value.required')->addViolation();
                            }
                        }
                    ),
                ] : [];

            $type = ($isSecret) ? 'password' : 'text';

            $builder->add(
                $key,
                $type,
                [
                    'label'      => $label,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                        'placeholder'  => ($type == 'password') ? '**************' : '',
                        'autocomplete' => 'off',
                    ],
                    'required'       => $required,
                    'constraints'    => $constraints,
                    'error_bubbling' => false,
                ]
            );
        }
        $object->appendToForm($builder, $options['data'], 'keys');
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['integration_object', 'integration_keys']);
        $resolver->setOptional(['secret_keys']);
        $resolver->setDefaults(['secret_keys' => [], 'is_published' => true]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'integration_keys';
    }
}
