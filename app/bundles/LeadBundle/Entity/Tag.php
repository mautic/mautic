<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Helper\InputHelper;

class Tag
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $tag;

    /**
     * @var string
     */
    private $description;

    /**
     * @param string $tag
     * @param bool   $clean
     */
    public function __construct($tag = null, $clean = true)
    {
        $this->tag = $clean ? $this->validateTag($tag) : $tag;
    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('lead_tags')
            ->setCustomRepositoryClass(TagRepository::class)
            ->addIndex(['tag'], 'lead_tag_search');

        $builder->addId();
        $builder->addField('tag', Type::STRING);
        $builder->addNamedField('description', Type::TEXT, 'description', true);
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('tag')
            ->addListProperties(
                [
                    'id',
                    'tag',
                    'description',
                ]
            )
            ->build();
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
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     *
     * @return Tag
     */
    public function setTag($tag)
    {
        $this->tag = $this->validateTag($tag);

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return Tag
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param string $tag
     *
     * @return Tag
     */
    protected function validateTag($tag)
    {
        return InputHelper::string(trim($tag));
    }
}
