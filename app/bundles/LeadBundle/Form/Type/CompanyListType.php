<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CompanyListType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class CompanyListType extends AbstractType
{
    /**
     * @var CompanyRepository
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
        $this->viewOther = $factory->getSecurity()->isGranted('lead:leads:viewother');
        $this->repo      = $factory->getModel('company')->getRepository();

        $this->repo->setCurrentUser($factory->getUser());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $companies = $this->repo->getCompanies(true);
        foreach ($companies as $company) {
            $companies_list[$company['id']] = $company['companyname'];
        }
        $resolver->setDefaults(
            [
                'choices'          => $companies_list,
                'expanded'         => false,
                'multiple'         => true,
                'required'         => false,
                'empty_value'      => function (Options $options) {
                    return (empty($options['choices'])) ? 'mautic.company.no.companies.note' : 'mautic.core.form.chooseone';
                }
            ]
        );
        $resolver->setDefined(['email_type', 'top_level', 'top_level_parent', 'ignore_ids']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "company_list";
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}
