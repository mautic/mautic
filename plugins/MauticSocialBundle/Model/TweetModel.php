<?php

namespace MauticPlugin\MauticSocialBundle\Model;

use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticSocialBundle\Entity\Tweet;
use MauticPlugin\MauticSocialBundle\Entity\TweetRepository;
use MauticPlugin\MauticSocialBundle\Entity\TweetStat;
use MauticPlugin\MauticSocialBundle\Entity\TweetStatRepository;
use MauticPlugin\MauticSocialBundle\Event as Events;
use MauticPlugin\MauticSocialBundle\Form\Type\TweetType;
use MauticPlugin\MauticSocialBundle\SocialEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Tweet>
 *
 * @implements AjaxLookupModelInterface<Tweet>
 */
class TweetModel extends FormModel implements AjaxLookupModelInterface
{
    /**
     * @param string $filter
     * @param int    $limit
     * @param int    $start
     * @param array  $options
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0, $options = []): array
    {
        $results = [];

        switch ($type) {
            case 'social.tweet':
            case 'tweet':
                if (isset($filter['tweet_text'])) {
                    // This tweet was created as the campaign action param and these params are not the filter. Clear the filter.
                    $filter = '';
                }

                $tweetRepo = $this->getRepository();
                $tweetRepo->setCurrentUser($this->userHelper->getUser());
                $entities = $tweetRepo->getTweetList(
                    $filter,
                    $limit,
                    $start,
                    $this->security->isGranted($this->getPermissionBase().':viewother')
                );

                foreach ($entities as $entity) {
                    $results[$entity['language']][$entity['id']] = $entity['name'];
                }

                // sort by language
                ksort($results);

                unset($entities);

                break;
        }

        return $results;
    }

    /**
     * Create/update Tweet Stat and update sent count for Tweet.
     *
     * @param string $source
     * @param int    $sourceId
     *
     * @return $this
     */
    public function registerSend(Tweet $tweet, Lead $lead, array $sendResponse, $source = null, $sourceId = null)
    {
        $statRepo = $this->getStatRepository();

        // Update failed tweet
        $stat = $statRepo->findOneBy(
            [
                'lead'     => $lead->getId(),
                'tweet'    => $tweet->getId(),
                'source'   => $source,
                'sourceId' => $sourceId,
                'isFailed' => true,
            ]
        );

        if (!$stat) {
            // Create new entity
            $stat = new TweetStat();
        } else {
            // Or add 1 to the retry count
            $stat->retryCountUp();
        }

        $stat->setTweet($tweet);
        $stat->setLead($lead);
        $stat->setResponseDetails($sendResponse);
        $stat->setSource($source);
        $stat->setSourceId($sourceId);

        $fields = $lead->getProfileFields();
        if (!empty($fields['twitter'])) {
            $stat->setHandle($fields['twitter']);
        }

        if (!empty($sendResponse['id_str'])) {
            $stat->setDateSent(new \DateTime());
            $stat->setTwitterTweetId($sendResponse['id_str']);

            $tweet->sentCountUp();
            $this->saveEntity($tweet);
        } else {
            $stat->setIsFailed(true);
        }

        $statRepo->saveEntity($stat);

        return $this;
    }

    /**
     * @param Tweet        $entity
     * @param array<mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Tweet) {
            throw new MethodNotAllowedHttpException(['Tweet']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(TweetType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param int $id
     */
    public function getEntity($id = null): ?Tweet
    {
        if (null === $id) {
            return new Tweet();
        }

        return parent::getEntity($id);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof Tweet) {
            throw new MethodNotAllowedHttpException(['Tweet']);
        }

        switch ($action) {
            case 'pre_save':
                $name = SocialEvents::TWEET_PRE_SAVE;
                break;
            case 'post_save':
                $name = SocialEvents::TWEET_POST_SAVE;
                break;
            case 'pre_delete':
                $name = SocialEvents::TWEET_PRE_DELETE;
                break;
            case 'post_delete':
                $name = SocialEvents::TWEET_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new Events\SocialEvent($entity, $isNew);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    public function getRepository(): TweetRepository
    {
        return $this->em->getRepository(Tweet::class);
    }

    public function getStatRepository(): TweetStatRepository
    {
        return $this->em->getRepository(TweetStat::class);
    }

    public function getPermissionBase(): string
    {
        return 'mauticSocial:tweets';
    }
}
