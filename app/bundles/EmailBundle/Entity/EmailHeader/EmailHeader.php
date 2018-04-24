<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity\EmailHeader;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class EmailHeader.
 */
class EmailHeader
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $value;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('email_headers')
            ->setCustomRepositoryClass(EmailHeaderRepository::class)
            ->addUniqueConstraint(['email_id', 'name'], 'emailHeader');

        $builder->addId();

        $builder->createManyToOne('email', Email::class)
            ->inversedBy('headers')
            ->addJoinColumn('email_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createField('name', 'string')
            ->columnName('name')
            ->build();

        $builder->createField('value', 'string')
            ->columnName('value')
            ->build();
    }

    /**
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
     * @param Email $email
     *
     * @return EmailHeader
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return EmailHeader
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return EmailHeader
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
