<?php

namespace Mautic\PageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\FormEntity;

#[ORM\Entity(repositoryClass: RedirectRepository::class)]
#[ORM\Table(name: 'page_redirects')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Redirect extends FormEntity
{
    /**
     * @var int|string
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
    private $url;

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
    #[ORM\OneToMany(targetEntity: Trackable::class, mappedBy: 'redirect', fetch: 'EXTRA_LAZY')]
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

    public function setRedirectId(?string $redirectId = null): void
    {
        $redirectId ??= substr(hash('sha1', uniqid(mt_rand())), 0, 25);
        $this->redirectId = $redirectId;
    }

    public function getUrl(): string
    {
        return trim($this->url);
    }

    public function setUrl(string $url): void
    {
        $this->url = trim($url);
    }

    public function setHits(int $hits): self
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

    public function setUniqueHits(int $uniqueHits): self
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
     * @return Collection<int, Trackable>
     */
    public function getTrackableList(): Collection
    {
        return $this->trackables;
    }

    /**
     * @param Collection<int, Trackable> $trackables
     */
    public function setTrackables(Collection $trackables): static
    {
        $this->trackables = $trackables;

        return $this;
    }
}
