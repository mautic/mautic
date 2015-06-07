<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class EmailListType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class EmailListType extends AbstractType
{
    private $repo;
    private $viewOther;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->viewOther = $factory->getSecurity()->isGranted('email:emails:viewother');
        $this->repo      = $factory->getModel('email')->getRepository();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $viewOther = $this->viewOther;
        $repo      = $this->repo;

        $resolver->setDefaults(
            array(
                'choices'     => function (Options $options) use ($repo, $viewOther) {
                    static $choices;

                    if (is_array($choices)) {
                        return $choices;
                    }

                    $choices = array();

                    $emails  = $repo->getEmailList('', 0, 0, $viewOther, true, $options['email_type']);
                    foreach ($emails as $email) {
                        $choices[$email['language']][$email['id']] = $email['name'];
                    }

                    //sort by language
                    ksort($choices);

                    return $choices;
                },
                'expanded'    => false,
                'multiple'    => true,
                'required'    => false,
                'empty_value' => function (Options $options) {
                    return (empty($options['choices'])) ? 'mautic.email.no.emails.note' : 'mautic.core.form.chooseone';
                },
                'email_type'  => 'template',
                'disabled'    => function (Options $options) {
                    return (empty($options['choices']));
                },
            )
        );

        $resolver->setOptional(array('email_type'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "email_list";
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}