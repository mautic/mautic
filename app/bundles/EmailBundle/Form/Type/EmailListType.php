<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Entity\EmailRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class EmailListType.
 */
class EmailListType extends AbstractType
{
    /**
     * @var EmailRepository
     */
    private $repo;

    /**
     * @var bool
     */
    private $viewOther;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->viewOther = $factory->getSecurity()->isGranted('email:emails:viewother');
        $this->repo      = $factory->getModel('email')->getRepository();

        $this->repo->setCurrentUser($factory->getUser());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => function (Options $options) {
                    $choices = [];

                    $emails = $this->repo->getEmailList(
                        '',
                        0,
                        0,
                        $this->viewOther,
                        $options['top_level'],
                        $options['email_type'],
                        $options['ignore_ids'],
                        $options['variant_parent']
                    );
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
                'email_type' => 'template',
                'disabled'   => function (Options $options) {
                    return empty($options['choices']);
                },
                'top_level'      => 'variant',
                'variant_parent' => null,
                'ignore_ids'     => [],
            ]
        );

        $resolver->setDefined(['email_type', 'top_level', 'top_level_parent', 'ignore_ids']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'email_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}
