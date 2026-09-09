<?php

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'tweets')]
#[ORM\Entity(repositoryClass: TweetRepository::class)]
#[ORM\Entity(repositoryClass: TweetRepository::class)]
#[ORM\Table(name: 'tweets')]
#[ORM\Index(columns: ['sent_count'], name: 'sent_count_index')]
#[ORM\Index(columns: ['favorite_count'], name: 'favorite_count_index')]
#[ORM\Index(columns: ['retweet_count'], name: 'retweet_count_index')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Tweet extends FormEntity
{
    /**
     * Internal Mautic ID of the tweet.
     *
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * ID of the Twitter media object attached to the tweet.
     *
     * @var string|null
     */
    #[ORM\Column(name: 'media_id', type: Types::STRING, length: 191, nullable: true)]
    private $mediaId;

    /**
     * Path to the local media file.
     *
     * @var string|null
     */
    #[ORM\Column(name: 'media_path', type: Types::STRING, length: 191, nullable: true)]
    private $mediaPath;

    /**
     * Internal Mautic name of the tweet.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * The actual messge of the tweet.
     *
     * @var string
     */
    #[Assert\Length(max: 280)]
    #[ORM\Column(type: Types::STRING, length: 280)]
    private $text;

    /**
     * Internal Mautic description.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'lang', type: Types::STRING, length: 191, nullable: true)]
    private $language = 'en';

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'sent_count', type: Types::INTEGER, nullable: true)]
    private $sentCount = 0;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'favorite_count', type: Types::INTEGER, nullable: true)]
    private $favoriteCount = 0;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'retweet_count', type: Types::INTEGER, nullable: true)]
    private $retweetCount = 0;

    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(name: 'page_id', onDelete: 'SET NULL')]
    private ?\Mautic\PageBundle\Entity\Page $page = null;

    #[ORM\ManyToOne(targetEntity: Asset::class)]
    #[ORM\JoinColumn(name: 'asset_id', onDelete: 'SET NULL')]
    private ?\Mautic\AssetBundle\Entity\Asset $asset = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['merge', 'detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
    private ?\Mautic\CategoryBundle\Entity\Category $category = null;

    /**
     * @var ArrayCollection<int, TweetStat>
     */
    #[ORM\OneToMany(mappedBy: 'tweet', targetEntity: 'TweetStat', cascade: ['persist'], fetch: 'EXTRA_LAZY', indexBy: 'id')]
    private \Doctrine\Common\Collections\ArrayCollection $stats;

    public function __construct()
    {
        $this->stats = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id            = null;
        $this->sentCount     = 0;
        $this->favoriteCount = 0;
        $this->retweetCount  = 0;
        $this->stats         = new ArrayCollection();

        parent::__clone();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('tweet')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'text',
                    'language',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'mediaId',
                    'mediaPath',
                    'sentCount',
                    'favoriteCount',
                    'retweetCount',
                    'description',
                ]
            )
            ->build();
    }

    /**
     * Constraints for required fields.
     */
    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): static
    {
        $this->id = $id;

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
     */
    public function setName($name): static
    {
        $this->isChanged('name', $name);
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
     * @param string|null $description
     */
    public function setDescription($description): static
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMediaId()
    {
        return $this->mediaId;
    }

    /**
     * @param string $mediaId
     */
    public function setMediaId($mediaId): static
    {
        $this->isChanged('mediaId', $mediaId);
        $this->mediaId = $mediaId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMediaPath()
    {
        return $this->mediaPath;
    }

    /**
     * @param string $mediaPath
     */
    public function setMediaPath($mediaPath): static
    {
        $this->isChanged('mediaPath', $mediaPath);
        $this->mediaPath = $mediaPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text): static
    {
        $this->isChanged('text', $text);
        $this->text = $text;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }

    public function setSentCount($sentCount): static
    {
        $this->isChanged('sentCount', $sentCount);
        $this->sentCount = $sentCount;

        return $this;
    }

    /**
     * Add 1 to sentCount.
     */
    public function sentCountUp(): static
    {
        $this->setSentCount($this->sentCount + 1);

        return $this;
    }

    /**
     * @return int
     */
    public function getFavoriteCount()
    {
        return $this->favoriteCount;
    }

    /**
     * @param int $favoriteCount
     */
    public function setFavoriteCount($favoriteCount): static
    {
        $this->isChanged('favoriteCount', $favoriteCount);
        $this->favoriteCount = $favoriteCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getRetweetCount()
    {
        return $this->retweetCount;
    }

    /**
     * @param int $retweetCount
     */
    public function setRetweetCount($retweetCount): static
    {
        $this->isChanged('retweetCount', $retweetCount);
        $this->retweetCount = $retweetCount;

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
     */
    public function setLanguage($language): static
    {
        $this->isChanged('language', $language);
        $this->language = $language;

        return $this;
    }

    /**
     * @return Asset|null
     */
    public function getAsset()
    {
        return $this->asset;
    }

    public function setAsset(Asset $asset): static
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * @return Page|null
     */
    public function getPage()
    {
        return $this->page;
    }

    public function setPage(Page $page): static
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory(Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStats()
    {
        return $this->stats;
    }
}
