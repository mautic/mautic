<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead as TheLead;

#[ORM\Entity(repositoryClass: TweetStatRepository::class)]
#[ORM\Table(name: 'tweet_stats')]
#[ORM\Index(name: 'stat_tweet_search', columns: ['tweet_id', 'lead_id'])]
#[ORM\Index(name: 'stat_tweet_search2', columns: ['lead_id', 'tweet_id'])]
#[ORM\Index(name: 'stat_tweet_failed_search', columns: ['is_failed'])]
#[ORM\Index(name: 'stat_tweet_source_search', columns: ['source', 'source_id'])]
#[ORM\Index(name: 'favorite_count_index', columns: ['favorite_count'])]
#[ORM\Index(name: 'retweet_count_index', columns: ['retweet_count'])]
#[ORM\Index(name: 'tweet_date_sent', columns: ['date_sent'])]
#[ORM\Index(name: 'twitter_tweet_id_index', columns: ['twitter_tweet_id'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class TweetStat
{
    /**
     * @var int
     */
    private $id;

    /**
     * ID of the tweet from Twitter.
     *
     * @var string|null
     */
    private $twitterTweetId;

    #[ORM\ManyToOne(targetEntity: Tweet::class, inversedBy: 'stats')]
    #[ORM\JoinColumn(name: 'tweet_id', onDelete: 'SET NULL')]
    private ?Tweet $tweet = null;

    /**
     * @var TheLead|null
     */
    #[ORM\ManyToOne(targetEntity: TheLead::class)]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'SET NULL')]
    private $lead;

    /**
     * @var string
     */
    private $handle;

    /**
     * @var \DateTime|null
     */
    private $dateSent;

    private ?bool $isFailed = false;

    private ?int $retryCount = 0;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var int|null
     */
    private $sourceId;

    private ?int $favoriteCount = 0;

    private ?int $retweetCount = 0;

    /**
     * @var ?mixed[]
     */
    private ?array $responseDetails = [];

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();

        $builder->createField('twitterTweetId', 'string')
            ->columnName('twitter_tweet_id')
            ->nullable()
            ->build();

        $builder->createField('handle', 'string')
            ->build();

        $builder->createField('dateSent', 'datetime')
            ->columnName('date_sent')
            ->nullable()
            ->build();

        $builder->createField('isFailed', 'boolean')
            ->columnName('is_failed')
            ->nullable()
            ->build();

        $builder->createField('retryCount', 'integer')
            ->columnName('retry_count')
            ->nullable()
            ->build();

        $builder->createField('source', 'string')
            ->nullable()
            ->build();

        $builder->createField('sourceId', 'integer')
            ->columnName('source_id')
            ->nullable()
            ->build();

        $builder->addNullableField('favoriteCount', 'integer', 'favorite_count');
        $builder->addNullableField('retweetCount', 'integer', 'retweet_count');
        $builder->addNullableField('responseDetails', Types::JSON, 'response_details');
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('stat')
            ->addProperties(
                [
                    'id',
                    'tweetId',
                    'handle',
                    'dateSent',
                    'isFailed',
                    'retryCount',
                    'favoriteCount',
                    'retweetCount',
                    'source',
                    'sourceId',
                    'lead',
                    'tweet',
                    'responseDetails',
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
     * @return string|null
     */
    public function getTwitterTweetId()
    {
        return $this->twitterTweetId;
    }

    /**
     * @param string $twitterTweetId
     */
    public function setTwitterTweetId($twitterTweetId): static
    {
        $this->twitterTweetId = $twitterTweetId;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    public function setDateSent(\DateTime $dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    public function getTweet(): ?Tweet
    {
        return $this->tweet;
    }

    public function setTweet(?Tweet $tweet = null): void
    {
        $this->tweet = $tweet;
    }

    /**
     * @return TheLead
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(?TheLead $lead = null): void
    {
        $this->lead = $lead;
    }

    public function getRetryCount(): ?int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    public function retryCountUp(): void
    {
        $this->setRetryCount($this->retryCount + 1);
    }

    public function getFavoriteCount(): ?int
    {
        return $this->favoriteCount;
    }

    public function setFavoriteCount(?int $favoriteCount): static
    {
        $this->favoriteCount = $favoriteCount;

        return $this;
    }

    public function getRetweetCount(): ?int
    {
        return $this->retweetCount;
    }

    public function setRetweetCount(?int $retweetCount): static
    {
        $this->retweetCount = $retweetCount;

        return $this;
    }

    public function getIsFailed(): ?bool
    {
        return $this->isFailed;
    }

    public function setIsFailed(bool $isFailed): void
    {
        $this->isFailed = $isFailed;
    }

    public function isFailed(): ?bool
    {
        return $this->isFailed;
    }

    /**
     * @return string|null
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @param mixed $handle
     */
    public function setHandle($handle): void
    {
        $this->handle = $handle;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source): void
    {
        $this->source = $source;
    }

    /**
     * @return mixed
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param mixed $sourceId
     */
    public function setSourceId($sourceId): void
    {
        $this->sourceId = (int) $sourceId;
    }

    /**
     * @return ?mixed[]
     */
    public function getResponseDetails(): ?array
    {
        return $this->responseDetails;
    }

    /**
     * @param mixed[] $responseDetails
     */
    public function setResponseDetails(array $responseDetails): static
    {
        $this->responseDetails = $responseDetails;

        return $this;
    }
}
