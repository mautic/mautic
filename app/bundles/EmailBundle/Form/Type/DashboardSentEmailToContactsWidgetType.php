<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\LeadBundle\Entity\CompanyRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DashboardSentEmailToContactsWidgetType.
 */
class DashboardSentEmailToContactsWidgetType extends AbstractType
{
    /**
     * @var CompanyRepository
     */
    private $companyRepository;

    /**
     * DashboardSentEmailToContactsWidgetType constructor.
     *
     * @param CompanyRepository $companyRepository
     */
    public function __construct(CompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $companies        = $this->companyRepository->getCompanies();
        $companiesChoises = [];
        foreach ($companies as $company) {
            $companiesChoises[$company['id']] = $company['companyname'];
        }
        $builder->add('companyId', 'choice', [
                'label'      => 'mautic.email.companyId.filter',
                'choices'    => $companiesChoises,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'empty_data' => '',
                'required'   => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'email_dashboard_sent_email_to_contacts_widget';
    }
}
