<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DashboardSentEmailToContactsWidgetType.
 */
class DashboardSentEmailToContactsWidgetType extends AbstractType
{
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var CompanyRepository
     */
    private $companyRepository;

    /**
     * @var LeadListRepository
     */
    private $segmentsRepository;

    /**
     * DashboardSentEmailToContactsWidgetType constructor.
     *
     * @param CampaignRepository $campaignRepository
     * @param CompanyRepository  $companyRepository
     * @param LeadListRepository $leadListRepository
     */
    public function __construct(
        CampaignRepository $campaignRepository,
        CompanyRepository $companyRepository,
        LeadListRepository $leadListRepository
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->companyRepository  = $companyRepository;
        $this->segmentsRepository = $leadListRepository;
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
        /** @var Campaign[] $campaigns */
        $campaigns        = $this->campaignRepository->findAll();
        $campaignsChoices = [];
        foreach ($campaigns as $campaign) {
            $campaignsChoices[$campaign->getId()] = $campaign->getName();
        }
        $builder->add('campaignId', 'choice', [
                'label'      => 'mautic.email.campaignId.filter',
                'choices'    => $campaignsChoices,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'empty_data' => '',
                'required'   => false,
            ]
        );
        /** @var LeadList[] $segments */
        $segments        = $this->segmentsRepository->findAll();
        $segmentsChoices = [];
        foreach ($segments as $segment) {
            $segmentsChoices[$segment->getId()] = $segment->getName();
        }
        $builder->add('segmentId', 'choice', [
                'label'      => 'mautic.email.segmentId.filter',
                'choices'    => $segmentsChoices,
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
