<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class DynamicContentItem extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $language = 'en';

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var DynamicContent
     */
    private $dwc;

    /**
     * Clone method
     */
    public function __clone()
    {
        $this->id = null;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('dynamic_content_items')
            ->setCustomRepositoryClass('Mautic\DynamicContentBundle\Entity\DynamicContentItemRepository');

        $builder->addIdColumns();

        $builder->createField('language', 'string')
            ->columnName('lang')
            ->build();

        $builder->addField('filters', 'array');

        $builder->createManyToOne('dwc', 'Mautic\DynamicContentBundle\Entity\DynamicContent')
            ->addJoinColumn('dwc_id', 'id', false, false, 'CASCADE')
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetaData(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new NotBlank(['message' => 'mautic.core.name.required']));
    }

    /**
     * @param ApiMetadataDriver $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('dwc')
            ->addListProperties([
                'id',
                'name',
                'language',
                'category'
            ])
            ->addProperties([
                'filters',
                'publishUp',
                'publishDown'
            ])
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param array $filters
     *
     * @return $this
     */
    public function setFilters(array $filters)
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return DynamicContent
     */
    public function getDwc()
    {
        return $this->dwc;
    }

    /**
     * @param DynamicContent $dwc
     */
    public function setDwc(DynamicContent $dwc)
    {
        $this->dwc = $dwc;
    }

}
