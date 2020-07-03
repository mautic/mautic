<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Services\ContactColumnsDictionary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactColumnsType extends AbstractType
{
    private $columnsDictionary;

    /**
     * ContactColumnsType constructor.
     */
    public function __construct(ContactColumnsDictionary $columnsDictionary)
    {
        $this->columnsDictionary = $columnsDictionary;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
          [
              'choices'    => array_flip($this->columnsDictionary->getFields()),
              'label'      => false,
              'label_attr' => ['class' => 'control-label'],
              'required'   => false,
              'multiple'   => true,
              'expanded'   => false,
              'attr'       => [
                  'class'         => 'form-control',
              ],
          ]
        );
    }

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
