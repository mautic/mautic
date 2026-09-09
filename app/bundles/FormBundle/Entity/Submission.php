<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['tracking_id'], name: 'form_submission_tracking_search')]
#[ORM\Index(columns: ['date_submitted'], name: 'form_date_submitted')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Submission
{
    public const TABLE_NAME = 'form_submissions';

    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: 'Form', inversedBy: 'submissions')]
    #[ORM\JoinColumn(name: 'form_id', nullable: false, onDelete: 'CASCADE')]
    private ?\Mautic\FormBundle\Entity\Form $form = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\CoreBundle\Entity\IpAddress::class, cascade: ['persist', 'merge', 'detach'])]
    #[ORM\JoinColumn(name: 'ip_id', onDelete: 'SET NULL')]
    private ?\Mautic\CoreBundle\Entity\IpAddress $ipAddress = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'SET NULL')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'tracking_id', type: 'string', length: 191, nullable: true)]
    private $trackingId;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_submitted', type: 'datetime')]
    private $dateSubmitted;

    /**
     * @var string
     */
    #[ORM\Column(type: 'text')]
    private $referer;

    #[ORM\ManyToOne(targetEntity: Page::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'page_id', onDelete: 'SET NULL')]
    private ?\Mautic\PageBundle\Entity\Page $page = null;

    /**
     * @var array
     */
    private $results = [];

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('submission')
            ->addProperties(
                [
                    'id',
                    'ipAddress',
                    'form',
                    'lead',
                    'trackingId',
                    'dateSubmitted',
                    'referer',
                    'page',
                    'results',
                ]
            )
            ->setGroupPrefix('submissionEvent')
            ->addProperties(
                [
                    'id',
                    'ipAddress',
                    'form',
                    'trackingId',
                    'dateSubmitted',
                    'referer',
                    'page',
                    'results',
                ]
            )
            ->build();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @param \DateTime $dateSubmitted
     */
    public function setDateSubmitted($dateSubmitted): static
    {
        $this->dateSubmitted = $dateSubmitted;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateSubmitted()
    {
        return $this->dateSubmitted;
    }

    /**
     * @param string $referer
     */
    public function setReferer($referer): static
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReferer()
    {
        return $this->referer;
    }

    public function setForm(Form $form): static
    {
        $this->form = $form;

        return $this;
    }

    public function getForm(): ?\Mautic\FormBundle\Entity\Form
    {
        return $this->form;
    }

    public function setIpAddress(?IpAddress $ipAddress = null): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getIpAddress(): ?\Mautic\CoreBundle\Entity\IpAddress
    {
        return $this->ipAddress;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    public function setResults($results): static
    {
        $this->results = $results;

        return $this;
    }

    public function setPage(?Page $page = null): static
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): ?\Mautic\PageBundle\Entity\Page
    {
        return $this->page;
    }

    public function getLead(): ?\Mautic\LeadBundle\Entity\Lead
    {
        return $this->lead;
    }

    public function setLead(?Lead $lead = null): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    public function setTrackingId($trackingId): static
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    /**
     * This method is used by standard entity algorithms to check if the current
     * user has permission to view/edit/delete this item. Provide the form creator for it.
     *
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->form->getCreatedBy();
    }

    /**
     * @param string $alias
     *
     * @return Field|null
     */
    public function getFieldByAlias($alias)
    {
        foreach ($this->form->getFields() as $field) {
            if ($field->getAlias() === $alias) {
                return $field;
            }
        }

        return null;
    }
}
