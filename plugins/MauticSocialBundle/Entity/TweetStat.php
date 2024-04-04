<?php

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead as TheLead;

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

    /**
     * @var Tweet|null
     */
    private $tweet;

    /**
     * @var TheLead|null
     */
    private $lead;

    /**
     * @var string
     */
    private $handle;

    /**
     * @var DateTime
     */
    private $dateSent;

    /**
     * @var bool|null
     */
    private $isFailed = false;

    /**
     * @var int|null
     */
    private $retryCount = 0;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var int|null
     */
    private $sourceId;

    /**
     * @var int|null
     */
    private $favoriteCount = 0;

    /**
     * @var int|null
     */
    private $retweetCount = 0;

    /**
     * @var array|null
     */
    private $responseDetails = [];

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('tweet_stats')
            ->setCustomRepositoryClass(TweetStatRepository::class)
            ->addIndex(['tweet_id', 'lead_id'], 'stat_tweet_search')
            ->addIndex(['lead_id', 'tweet_id'], 'stat_tweet_search2')
            ->addIndex(['is_failed'], 'stat_tweet_failed_search')
            ->addIndex(['source', 'source_id'], 'stat_tweet_source_search')
            ->addIndex(['favorite_count'], 'favorite_count_index')
            ->addIndex(['retweet_count'], 'retweet_count_index')
            ->addIndex(['date_sent'], 'tweet_date_sent')
            ->addIndex(['twitter_tweet_id'], 'twitter_tweet_id_index');

        $builder->addId();

        $builder->createManyToOne('tweet', 'Tweet')
            ->inversedBy('stats')
            ->addJoinColumn('tweet_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createField('twitterTweetId', 'string')
            ->columnName('twitter_tweet_id')
            ->nullable()
            ->build();

        $builder->addLead(true, 'SET NULL');

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
     *
     * @return $this
     */
    public function setTwitterTweetId($twitterTweetId)
    {
        $this->twitterTweetId = $twitterTweetId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param mixed $dateSent
     */
    public function setDateSent($dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return Tweet
     */
    public function getTweet()
    {
        return $this->tweet;
    }

    /**
     * @param mixed $tweet
     */
    public function setTweet(Tweet $tweet = null): void
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

    /**
     * @param mixed $lead
     */
    public function setLead(TheLead $lead = null): void
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getRetryCount()
    {
        return $this->retryCount;
    }

    /**
     * @param mixed $retryCount
     */
    public function setRetryCount($retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    public function retryCountUp(): void
    {
        $this->setRetryCount($this->getRetryCount() + 1);
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
     *
     * @return $this
     */
    public function setFavoriteCount($favoriteCount)
    {
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
     *
     * @return $this
     */
    public function setRetweetCount($retweetCount)
    {
        $this->retweetCount = $retweetCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsFailed()
    {
        return $this->isFailed;
    }

    /**
     * @param mixed $isFailed
     */
    public function setIsFailed($isFailed): void
    {
        $this->isFailed = $isFailed;
    }

    /**
     * @return mixed
     */
    public function isFailed()
    {
        return $this->getIsFailed();
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
     * @return mixed
     */
    public function getResponseDetails()
    {
        return $this->responseDetails;
    }

    /**
     * @param mixed $responseDetails
     *
     * @return Stat
     */
    public function setResponseDetails($responseDetails)
    {
        $this->responseDetails = $responseDetails;

        return $this;
    }
}
