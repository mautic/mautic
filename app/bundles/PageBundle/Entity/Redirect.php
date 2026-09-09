<?php

namespace Mautic\PageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\FormEntity;

#[ORM\Entity(repositoryClass: RedirectRepository::class)]
#[ORM\Table(name: 'page_redirects')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Redirect extends FormEntity
{
    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'redirect_id', type: 'string', length: 25)]
    private $redirectId;

    #[ORM\Column(type: 'text')]
    private ?string $url = null;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    private $hits = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'unique_hits', type: 'integer')]
    private $uniqueHits = 0;

    /**
     * @var ArrayCollection<int, Trackable>
     */
    #[ORM\OneToMany(mappedBy: 'redirect', targetEntity: Trackable::class, fetch: 'EXTRA_LAZY')]
    private $trackables;

    public function __construct()
    {
        $this->trackables = new ArrayCollection();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('redirect')
            ->addListProperties(
                [
                    'id',
                    'redirectId',
                    'url',
                ]
            )
            ->addProperties(
                [
                    'hits',
                    'uniqueHits',
                ]
            )
            ->build();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @return string
     */
    public function getRedirectId()
    {
        return $this->redirectId;
    }

    /**
     * @param string $redirectId
     */
    public function setRedirectId($redirectId = null): void
    {
        $redirectId ??= substr(hash('sha1', uniqid(mt_rand())), 0, 25);
        $this->redirectId = $redirectId;
    }

    public function getUrl(): string
    {
        return trim($this->url);
    }

    /**
     * @param string $url
     */
    public function setUrl($url): void
    {
        $this->url = trim($url);
    }

    /**
     * @param int $hits
     */
    public function setHits($hits): self
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * @return int
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * @param int $uniqueHits
     */
    public function setUniqueHits($uniqueHits): self
    {
        $this->uniqueHits = $uniqueHits;

        return $this;
    }

    /**
     * @return int
     */
    public function getUniqueHits()
    {
        return $this->uniqueHits;
    }

    /**
     * @return ArrayCollection
     */
    public function getTrackableList()
    {
        return $this->trackables;
    }

    /**
     * @param ArrayCollection $trackables
     */
    public function setTrackables($trackables): static
    {
        $this->trackables = $trackables;

        return $this;
    }
}
