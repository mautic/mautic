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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CategoryBundle\Entity\Category;
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
     * @var int
     */
    private $sentCount = 0;

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
     * @var Category
     **/
    private $category;

    /**
     * @var ArrayCollection
     */
    private $stats;

    /**
     * Tweet constructor.
     */
    public function __construct()
    {
        $this->stats = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id            = null;
        $this->tweetId       = null;
        $this->sentCount     = 0;
        $this->favoriteCount = 0;
        $this->retweetCount  = 0;
        $this->stats         = new ArrayCollection();

        parent::__clone();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('tweets')
            ->setCustomRepositoryClass(TweetRepository::class)
            ->addIndex(['text'], 'tweet_text_index')
            ->addIndex(['sent_count'], 'sent_count_index')
            ->addIndex(['favorite_count'], 'favorite_count_index')
            ->addIndex(['retweet_count'], 'retweet_count_index');

        $builder->addIdColumns();
        $builder->addCategory();
        $builder->addNullableField('mediaId', Type::STRING, 'media_id');
        $builder->addNullableField('mediaPath', Type::STRING, 'media_path');
        $builder->addField('text', Type::STRING);
        $builder->addNullableField('sentCount', Type::INTEGER, 'sent_count');
        $builder->addNullableField('favoriteCount', Type::INTEGER, 'favorite_count');
        $builder->addNullableField('retweetCount', Type::INTEGER, 'retweet_count');
        $builder->addNullableField('language', Type::STRING, 'lang');

        $builder->createManyToOne('page', Page::class)
            ->addJoinColumn('page_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('asset', Asset::class)
            ->addJoinColumn('asset_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createOneToMany('stats', 'TweetStat')
            ->setIndexBy('id')
            ->mappedBy('tweet')
            ->cascadePersist()
            ->fetchExtraLazy()
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
     * @param int $description
     *
     * @return $this
     */
    public function setDescription($description)
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
     * @return int|null
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }

    /**
     * @param DateTime $dateTweeted
     *
     * @return $this
     */
    public function setSentCount($sentCount)
    {
        $this->isChanged('sentCount', $sentCount);
        $this->sentCount = $sentCount;

        return $this;
    }

    /**
     * Add 1 to sentCount.
     *
     * @return $this
     */
    public function sentCountUp()
    {
        $this->setSentCount($this->getSentCount() + 1);

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

    /**
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $page
     *
     * @return $this
     */
    public function setCategory(Category $category)
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
