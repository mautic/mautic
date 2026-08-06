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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<Tweet>
 *
 * @implements AjaxLookupModelInterface<Tweet>
 */
final class TweetModel extends FormModel implements AjaxLookupModelInterface
{
    private TweetStatRepository $tweetStatRepository;

    private TweetRepository $tweetRepository;

    #[Required]
    public function autowireTweetModel(
        TweetRepository $tweetRepository,
        TweetStatRepository $tweetStatRepository,
    ): void {
        $this->tweetRepository = $tweetRepository;
        $this->tweetStatRepository = $tweetStatRepository;
    }

    /**
     * @param string|array<int, string> $filter
     * @param array<string, mixed>      $options
     */
    public function getLookupResults(string $type, string|array $filter = '', int $limit = 10, int $start = 0, array $options = []): array
    {
        $results = [];

        switch ($type) {
            case 'social.tweet':
            case 'tweet':
                if (isset($filter['tweet_text'])) {
                    // This tweet was created as the campaign action param and these params are not the filter. Clear the filter.
                    $filter = '';
                }

                $this->tweetRepository->setCurrentUser($this->userHelper->getUser());
                $entities = $this->tweetRepository->getTweetList(
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
     */
    public function registerSend(Tweet $tweet, Lead $lead, array $sendResponse, $source = null, $sourceId = null): static
    {
        // Update failed tweet
        $stat = $this->tweetStatRepository->findOneBy(
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

        $this->tweetStatRepository->saveEntity($stat);

        return $this;
    }

    /**
     * @param Tweet $entity
     */
    public function createForm($entity, mixed ...$args): FormInterface
    {
        [$action, $options] = $this->resolveCreateFormArgs($args);

        if (!$entity instanceof Tweet) {
            throw new MethodNotAllowedHttpException(['Tweet']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $this->formFactory->create(TweetType::class, $entity, $options);
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
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
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
            if (!$event instanceof Event) {
                $event = new Events\SocialEvent($entity, $isNew);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    public function getRepository(): TweetRepository
    {
        return $this->tweetRepository;
    }

    public function getStatRepository(): TweetStatRepository
    {
        return $this->tweetStatRepository;
    }

    public function getPermissionBase(): string
    {
        return 'mauticSocial:tweets';
    }
}
