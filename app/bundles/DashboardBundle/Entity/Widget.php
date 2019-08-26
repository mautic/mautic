<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Widget.
 */
class Widget extends FormEntity
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
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @var int
     */
    private $ordering;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var string
     */
    private $template;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @var bool
     */
    private $cached = false;

    /**
     * @var int
     */
    private $loadTime = 0;

    /**
     * @var int (minutes)
     */
    private $cacheTimeout;

    /**
     * @var array
     */
    private $templateData = [];

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('widgets');
        $builder->setCustomRepositoryClass(WidgetRepository::class);
        $builder->addIdColumns('name', false);
        $builder->addField('type', Type::STRING);
        $builder->addField('width', Type::INTEGER);
        $builder->addField('height', Type::INTEGER);
        $builder->addNullableField('cacheTimeout', Type::INTEGER, 'cache_timeout');
        $builder->addNullableField('ordering', Type::INTEGER);
        $builder->addNullableField('params', Type::TARRAY);
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('type', new NotBlank([
            'message' => 'mautic.core.type.required',
        ]));
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
     * Set name.
     *
     * @param string $name
     *
     * @return Widget
     */
    public function setName($name)
    {
        $this->name = InputHelper::string($name);
        $this->isChanged('name', $this->name);

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Widget
     */
    public function setType($type)
    {
        $this->type = InputHelper::string($type);
        $this->isChanged('type', $this->type);

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set width.
     *
     * @param int $width
     *
     * @return Widget
     */
    public function setWidth($width)
    {
        $this->width = (int) $width;
        $this->isChanged('width', $this->width);

        return $this;
    }

    /**
     * Get width.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height.
     *
     * @param int $height
     *
     * @return Widget
     */
    public function setHeight($height)
    {
        $this->height = (int) $height;
        $this->isChanged('height', $this->height);

        return $this;
    }

    /**
     * Get cache timeout.
     *
     * @return int (minutes)
     */
    public function getCacheTimeout()
    {
        return $this->cacheTimeout;
    }

    /**
     * Set cache timeout.
     *
     * @param int $cacheTimeout (minutes)
     *
     * @return Widget
     */
    public function setCacheTimeout($cacheTimeout)
    {
        $this->isChanged('cacheTimeout', $cacheTimeout);
        $this->cacheTimeout = $cacheTimeout;

        return $this;
    }

    /**
     * Get height.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set ordering.
     *
     * @param int $ordering
     *
     * @return Widget
     */
    public function setOrdering($ordering)
    {
        $this->ordering = (int) $ordering;
        $this->isChanged('ordering', $this->ordering);

        return $this;
    }

    /**
     * Get ordering.
     *
     * @return int
     */
    public function getOrdering()
    {
        return $this->ordering;
    }

    /**
     * Get params.
     *
     * @return array $params
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set params.
     *
     * @param array $params
     *
     * @return Widget
     */
    public function setParams(array $params)
    {
        $this->isChanged('params', $params);
        $this->params = $params;

        return $this;
    }

    /**
     * Set template.
     *
     * @param string $template
     *
     * @return Widget
     */
    public function setTemplate($template)
    {
        $this->isChanged('template', $template);
        $this->template = $template;

        return $this;
    }

    /**
     * Get template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Get template data.
     *
     * @return array $templateData
     */
    public function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * Set template data.
     *
     * @param array $templateData
     *
     * @return Widget
     */
    public function setTemplateData(array $templateData)
    {
        $this->isChanged('templateData', $templateData);
        $this->templateData = $templateData;

        return $this;
    }

    /**
     * Set errorMessage.
     *
     * @param string $errorMessage
     *
     * @return Widget
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * Get errorMessage.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Set cached flag.
     *
     * @param string $cached
     *
     * @return Widget
     */
    public function setCached($cached)
    {
        $this->cached = $cached;

        return $this;
    }

    /**
     * Get cached.
     *
     * @return bool
     */
    public function isCached()
    {
        return $this->cached;
    }

    /**
     * Set loadTime.
     *
     * @param string $loadTime
     *
     * @return Widget
     */
    public function setLoadTime($loadTime)
    {
        $this->loadTime = $loadTime;

        return $this;
    }

    /**
     * Get loadTime.
     *
     * @return int
     */
    public function getLoadTime()
    {
        return $this->loadTime;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name'     => $this->getName(),
            'width'    => $this->getWidth(),
            'height'   => $this->getHeight(),
            'ordering' => $this->getOrdering(),
            'type'     => $this->getType(),
            'params'   => $this->getParams(),
            'template' => $this->getTemplate(),
        ];
    }
}
