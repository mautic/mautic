<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PointActionFormSubmitType
 */
class FormListType extends AbstractType
{

    /**
     * @var array
     */
    private $choices = array();

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $viewOther = $factory->getSecurity()->isGranted('form:forms:viewother');
        $choices = $factory->getModel('form')->getRepository()->getFormList('', 0, 0, $viewOther);

        foreach ($choices as $form) {
            $this->choices[$form['id']] = "{$form['name']} ({$form['id']})";
        }

        //sort by language
        ksort($this->choices);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices'       => $this->choices,
            'expanded'      => false,
            'multiple'      => true,
            'empty_value'   => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "form_list";
    }

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return 'choice';
    }
}
