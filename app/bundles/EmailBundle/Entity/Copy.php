<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Helper\EmojiHelper;

/**
 * Class Copy.
 */
class Copy
{
    /**
     * MD5 hash of the content.
     *
     * @var string
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var string
     */
    private $body;

    /**
     * @var
     */
    private $subject;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('email_copies')
            ->setCustomRepositoryClass('Mautic\EmailBundle\Entity\CopyRepository');

        $builder->createField('id', 'string')
            ->isPrimaryKey()
            ->length(32)
            ->build();

        $builder->createField('dateCreated', 'datetime')
            ->columnName('date_created')
            ->build();

        $builder->addNullableField('body', 'text');

        $builder->addNullableField('subject', 'text');
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @param \DateTime $dateCreated
     *
     * @return Copy
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return Copy
     */
    public function setBody($body)
    {
        // Ensure it's clean of emoji
        $body = EmojiHelper::toShort($body);

        $this->body = $body;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     *
     * @return Copy
     */
    public function setSubject($subject)
    {
        // Ensure it's clean of emoji
        $subject = EmojiHelper::toShort($subject);

        $this->subject = $subject;

        return $this;
    }
}
