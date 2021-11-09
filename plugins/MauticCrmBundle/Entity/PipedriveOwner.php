<?php

namespace  MauticPlugin\MauticCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class PipedriveOwner
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $email;

    /**
     * @var int
     */
    private $ownerId;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder
            ->setTable('plugin_crm_pipedrive_owners')
            ->addIndex(['email'], 'email')
            ->addIndex(['owner_id'], 'owner_id')
        ;

        $builder->addId();
        $builder->addNamedField('email', 'string', 'email');
        $builder->addNamedField('ownerId', 'integer', 'owner_id', true);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Widget
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @param string $ownerId
     *
     * @return PipedriveOwner
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;

        return $this;
    }
}
