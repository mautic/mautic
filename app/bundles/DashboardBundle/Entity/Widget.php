<?php

namespace Mautic\DashboardBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

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
     * @var int|null
     */
    private $ordering;

    /**
     * @var string
     */
    #[\Symfony\Component\Validator\Constraints\NotBlank(message: 'mautic.core.type.required')]
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
     * @var int|null (minutes)
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

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('widgets');
        $builder->setCustomRepositoryClass(WidgetRepository::class);
        $builder->addIdColumns('name', false);
        $builder->addField('type', Types::STRING);
        $builder->addField('width', Types::INTEGER);
        $builder->addField('height', Types::INTEGER);
        $builder->addNullableField('cacheTimeout', Types::INTEGER, 'cache_timeout');
        $builder->addNullableField('ordering', Types::INTEGER);
        $builder->addNullableField('params', Types::ARRAY);
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function setName(string $name): static
    {
        $this->name = InputHelper::string($name);
        $this->isChanged('name', $this->name);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    public function setType(string $type): static
    {
        $this->type = InputHelper::string($type);
        $this->isChanged('type', $this->type);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $width
     */
    public function setWidth($width): static
    {
        $this->width = (int) $width;
        $this->isChanged('width', $this->width);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $height
     */
    public function setHeight($height): static
    {
        $this->height = (int) $height;
        $this->isChanged('height', $this->height);

        return $this;
    }

    /**
     * @return int|null (minutes)
     */
    public function getCacheTimeout()
    {
        return $this->cacheTimeout;
    }

    /**
     * @param int $cacheTimeout (minutes)
     */
    public function setCacheTimeout($cacheTimeout): static
    {
        $this->isChanged('cacheTimeout', $cacheTimeout);
        $this->cacheTimeout = $cacheTimeout;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $ordering
     */
    public function setOrdering($ordering): static
    {
        $this->ordering = (int) $ordering;
        $this->isChanged('ordering', $this->ordering);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOrdering()
    {
        return $this->ordering;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    public function setParams(array $params): static
    {
        $this->isChanged('params', $params);
        $this->params = $params;

        return $this;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template): static
    {
        $this->isChanged('template', $template);
        $this->template = $template;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return array
     */
    public function getTemplateData()
    {
        return $this->templateData;
    }

    public function setTemplateData(array $templateData): static
    {
        $this->isChanged('templateData', $templateData);
        $this->templateData = $templateData;

        return $this;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Set cached flag.
     *
     * @param bool $cached
     */
    public function setCached($cached): static
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
     * @param string|float|int $loadTime
     */
    public function setLoadTime($loadTime): static
    {
        $this->loadTime = $loadTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getLoadTime()
    {
        return $this->loadTime;
    }

    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'width'    => $this->width,
            'height'   => $this->height,
            'ordering' => $this->ordering,
            'type'     => $this->type,
            'params'   => $this->params,
            'template' => $this->template,
        ];
    }
}
