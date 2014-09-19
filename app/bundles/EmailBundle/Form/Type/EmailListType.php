<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class EmailListType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class EmailListType extends AbstractType
{

    private $choices = array();

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $viewOther = $factory->getSecurity()->isGranted('email:emails:viewother');
        $choices = $factory->getModel('email')->getRepository()
            ->getEmailList('', 0, 0, $viewOther, true);
        foreach ($choices as $email) {
            $this->choices[$email['language']][$email['id']] = $email['id'] . ':' . $email['subject'];
        }

        //sort by language
        ksort($this->choices);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices'       => $this->choices,
            'empty_value'   => false,
            'expanded'      => false,
            'multiple'      => true,
            'required'      => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "email_list";
    }

    public function getParent()
    {
        return 'choice';
    }
}