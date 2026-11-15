<?php

namespace Mautic\DashboardBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Type\ArrayType;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Entity(repositoryClass: WidgetRepository::class)]
#[ORM\Table(name: 'widgets')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Widget extends FormEntity
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var int
     */
    #[ORM\Column(type: Types::INTEGER)]
    private $width;

    /**
     * @var int
     */
    #[ORM\Column(type: Types::INTEGER)]
    private $height;

    /**
     * @var int|null
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private $ordering;

    /**
     * @var string
     */
    #[NotBlank(message: 'mautic.core.type.required')]
    #[ORM\Column(type: Types::STRING, length: 191)]
    private $type;

    /**
     * @var array
     */
    #[ORM\Column(type: ArrayType::ARRAY, nullable: true)]
    private $params = [];

    private ?string $template = null;

    private ?string $errorMessage = null;

    private ?bool $cached = false;

    /**
     * @var int
     */
    private $loadTime = 0;

    /**
     * @var int|null (minutes)
     */
    #[ORM\Column(name: 'cache_timeout', type: Types::INTEGER, nullable: true)]
    private $cacheTimeout;

    private array $templateData = [];

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
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

    public function setWidth(?int $width): static
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

    public function setHeight(?int $height): static
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

    public function setOrdering(?int $ordering): static
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

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): static
    {
        $this->isChanged('params', $params);
        $this->params = $params;

        return $this;
    }

    public function setTemplate(?string $template): static
    {
        $this->isChanged('template', $template);
        $this->template = $template;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    public function setTemplateData(array $templateData): static
    {
        $this->isChanged('templateData', $templateData);
        $this->templateData = $templateData;

        return $this;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Set cached flag.
     */
    public function setCached(?bool $cached): static
    {
        $this->cached = $cached;

        return $this;
    }

    /**
     * Get cached.
     */
    public function isCached(): ?bool
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

    /**
     * @return array<string, int|string|mixed[]|null>
     */
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
