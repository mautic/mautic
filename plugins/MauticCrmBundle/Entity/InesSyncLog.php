<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;


/**
 * @ORM\Entity
 * @ORM\Table(name="ines_sync_log")
 * @ORM\Entity(repositoryClass="MauticPlugin\MauticCrmBundle\Entity\InesSyncLogRepository")
 */
class InesSyncLog
{
	/**
     * @var int
     */
    private $id;

	/**
     * @var string	'UPDATE' | 'DELETE'
     */
    private $action;

	/**
     * @var int
     */
    private $leadId;

	/**
     * @var string
     */
    private $leadEmail;

	/**
     * @var string
     */
    private $leadCompany;

	/**
	 * @var \DateTime
	 */
	private $dateAdded;

	/**
	 * @var \DateTime
	 */
	private $dateLastUpdate;

	/**
     * @var string	'PENDING' | 'DONE' | 'FAILED'
     */
    private $status;

	/**
     * @var int		Nombre de tentatives
     */
    private $counter;


    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ines_sync_log')
        		->setCustomRepositoryClass('MauticPlugin\MauticCrmBundle\Entity\InesSyncLogRepository');

		$builder->createField('id', 'integer')
				->isPrimaryKey()
				->generatedValue()
				->build();

        $builder->addNamedField('action', 'string', 'action');
        $builder->addNamedField('leadId', 'integer', 'lead_id');
        $builder->addNamedField('leadEmail', 'string', 'lead_email');
        $builder->addNamedField('leadCompany', 'string', 'lead_company');
        $builder->addNamedField('dateAdded', 'datetime', 'date_added');
        $builder->addNamedField('dateLastUpdate', 'datetime', 'date_last_update');
		$builder->addNamedField('status', 'string', 'status');
		$builder->addNamedField('counter', 'integer', 'counter');
    }


	/**
	 * Constructeur : fixe les valeurs par défaut de l'entité
	 */
	public function __construct()
	{
		$this->action = 'UPDATE';
		$this->leadId = 0;
		$this->leadEmail = '';
		$this->leadCompany = '';
		$this->dateAdded = new \Datetime;
		$this->dateLastUpdate = new \Datetime;
		$this->status = 'PENDING';
		$this->counter = 0;
	}


	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string 'UPDATE' | 'DELETE'
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @return int
	 */
	public function getLeadId()
	{
		return $this->leadId;
	}

	/**
	 * @return string
	 */
	public function getLeadEmail()
	{
		return $this->leadEmail;
	}

	/**
	 * @return string
	 */
	public function getLeadCompany()
	{
		return $this->leadCompany;
	}

    /**
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

	/**
     * @return \DateTime
     */
    public function getDateLastUpdate()
    {
        return $this->dateLastUpdate;
    }

	/**
	 * @return string	'PENDING' | 'DONE' | 'FAILED'
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @return int
	 */
	public function getCounter()
	{
		return $this->counter;
	}

	/**
	 * @param string $action
	 *
	 * @return $this
	 */
	public function setAction($action)
	{
		if (in_array($action, ['UPDATE', 'DELETE'])) {
			$this->action = $action;
		}
		return $this;
	}

	/**
	 * @param int $leadId
	 *
	 * @return $this
	 */
	public function setLeadId($leadId)
	{
		$this->leadId = $leadId;
		return $this;
	}

	/**
	 * @param string $leadEmail
	 *
	 * @return $this
	 */
	public function setLeadEmail($leadEmail)
	{
		$this->leadEmail = $leadEmail;
		return $this;
	}

	/**
	 * @param string $leadCompany
	 *
	 * @return $this
	 */
	public function setLeadCompany($leadCompany)
	{
		$this->leadCompany = $leadCompany;
		return $this;
	}

	/**
     * @param \DateTime $dateAdded
     *
     * @return $this
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;
        return $this;
    }

	/**
     * @param \DateTime $dateLastUpdate
     *
     * @return $this
     */
    public function setDateLastUpdate($dateLastUpdate)
    {
		$this->dateLastUpdate = $dateLastUpdate;
        return $this;
    }

	/**
	 * @param string $status
	 *
	 * @return $this
	 */
	public function setStatus($status)
	{
		if (in_array($status, ['PENDING', 'DONE', 'FAILED'])) {
			$this->status = $status;
		}
		return $this;
	}

	/**
	 * @param int $counter
	 *
	 * @return $this
	 */
	public function setCounter($counter)
	{
		$this->counter = $counter;
		return $this;
	}
}
