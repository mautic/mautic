<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\PageBundle\Entity\Page;

/**
 * @ORM\Entity
 * @ORM\Table(name="tweets")
 * @ORM\Entity(repositoryClass="MauticPlugin\MauticSocialBundle\Entity\TweetRepository")
 */
class Tweet extends FormEntity
{
    /**
     * Internal Mautic ID of the tweet.
     *
     * @var int
     */
    private $id;

    /**
     * ID of the tweet from Twitter.
     *
     * @var string
     */
    private $tweetId;

    /**
     * ID of the Twitter media object attached to the tweet.
     *
     * @var string
     */
    private $mediaId;

    /**
     * Path to the local media file.
     *
     * @var string
     */
    private $mediaPath;

    /**
     * Internal Mautic name of the tweet.
     *
     * @var string
     */
    private $name;

    /**
     * The actual messge of the tweet.
     *
     * @var string
     */
    private $text;

    /**
     * Internal Mautic description.
     *
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $language = 'en';

    /**
     * @var \DateTime
     */
    private $dateTweeted;

    /**
     * @var int
     */
    private $favoriteCount = 0;

    /**
     * @var int
     */
    private $retweetCount = 0;

    /**
     * @var Page
     */
    private $page;

    /**
     * @var Asset
     */
    private $asset;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    public function __clone()
    {
        $this->id            = null;
        $this->tweetId       = null;
        $this->favoriteCount = 0;
        $this->retweetCount  = 0;

        parent::__clone();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('tweets')
            ->setCustomRepositoryClass('MauticPlugin\MauticSocialBundle\Entity\TweetRepository')
            ->addIndex(['tweet_id'], 'tweet_id_index')
            ->addIndex(['text'], 'tweet_text_index')
            ->addIndex(['favorite_count'], 'favorite_count_index')
            ->addIndex(['retweet_count'], 'retweet_count_index')
            ->addIndex(['date_tweeted'], 'date_tweeted_index');

        $builder->addIdColumns();
        $builder->addCategory();

        $builder->createField('tweetId', 'string')
            ->columnName('tweet_id')
            ->nullable()
            ->build();

        $builder->createField('mediaId', 'string')
            ->columnName('media_id')
            ->nullable()
            ->build();

        $builder->createField('mediaPath', 'string')
            ->columnName('media_path')
            ->nullable()
            ->build();

        $builder->createField('text', 'string')
            ->build();

        $builder->createField('dateTweeted', 'datetime')
            ->columnName('date_tweeted')
            ->nullable()
            ->build();

        $builder->createField('favoriteCount', 'integer')
            ->columnName('favorite_count')
            ->build();

        $builder->createField('retweetCount', 'integer')
            ->columnName('retweet_count')
            ->build();

        $builder->createField('language', 'string')
            ->columnName('lang')
            ->build();

        $builder->createManyToOne('page', Page::class)
            ->addJoinColumn('page_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('asset', Asset::class)
            ->addJoinColumn('asset_id', 'id', true, false, 'SET NULL')
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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
                    'tweetId',
                    'mediaId',
                    'mediaPath',
                    'dateTweeted',
                    'favoriteCount',
                    'retweetCount',
                    'description',
                ]
            )
            ->build();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
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
     * @param int $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTweetId()
    {
        return $this->tweetId;
    }

    /**
     * @param string $tweetId
     *
     * @return $this
     */
    public function setTweetId($tweetId)
    {
        $this->isChanged('tweetId', $tweetId);
        $this->tweetId = $tweetId;

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
     *
     * @return $this
     */
    public function setMediaId($mediaId)
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
     *
     * @return $this
     */
    public function setMediaPath($mediaPath)
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
     *
     * @return $this
     */
    public function setText($text)
    {
        $this->isChanged('text', $text);
        $this->text = $text;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDateTweeted()
    {
        return $this->dateTweeted;
    }

    /**
     * @param DateTime $dateTweeted
     *
     * @return $this
     */
    public function setDateTweeted(\DateTime $dateTweeted)
    {
        $this->isChanged('dateTweeted', $dateTweeted);
        $this->dateTweeted = $dateTweeted;

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
     *
     * @return $this
     */
    public function setFavoriteCount($favoriteCount)
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
     *
     * @return $this
     */
    public function setRetweetCount($retweetCount)
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
     *
     * @return $this
     */
    public function setLanguage($language)
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

    /**
     * @param Asset $asset
     *
     * @return $this
     */
    public function setAsset(Asset $asset)
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

    /**
     * @param Page $page
     *
     * @return $this
     */
    public function setPage(Page $page)
    {
        $this->page = $page;

        return $this;
    }
}
