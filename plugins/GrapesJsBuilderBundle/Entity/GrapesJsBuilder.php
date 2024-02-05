<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\EmailBundle\Entity\Email;

class GrapesJsBuilder
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Email|null
     */
    protected $email;

    /**
     * @var string|null
     */
    private $customMjml;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('bundle_grapesjsbuilder')
            ->setCustomRepositoryClass(GrapesJsBuilderRepository::class)
            ->addNamedField('customMjml', Types::TEXT, 'custom_mjml', true)
            ->addId();

        $builder->createManyToOne(
            'email',
            \Mautic\EmailBundle\Entity\Email::class
        )->addJoinColumn('email_id', 'id', true, false, 'CASCADE')->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return GrapesJsBuilder
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomMjml()
    {
        return $this->customMjml;
    }

    /**
     * @param string $customMjml
     *
     * @return GrapesJsBuilder
     */
    public function setCustomMjml($customMjml)
    {
        $this->customMjml = $customMjml;

        return $this;
    }
}
