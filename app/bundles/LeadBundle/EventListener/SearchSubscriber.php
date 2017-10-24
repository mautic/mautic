<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\LeadBuildSearchEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;

/**
 * Class SearchSubscriber.
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var LeadRepository
     */
    private $leadRepo;

    /**
     * SearchSubscriber constructor.
     *
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
        $this->leadRepo  = $leadModel->getRepository();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::GLOBAL_SEARCH              => ['onGlobalSearch', 0],
            CoreEvents::BUILD_COMMAND_LIST         => ['onBuildCommandList', 0],
            LeadEvents::LEAD_BUILD_SEARCH_COMMANDS => ['onBuildSearchCommands', 0],
        ];
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $anonymous = $this->translator->trans('mautic.lead.lead.searchcommand.isanonymous');
        $mine      = $this->translator->trans('mautic.core.searchcommand.ismine');
        $filter    = ['string' => $str, 'force' => ''];

        //only show results that are not anonymous so as to not clutter up things
        if (strpos($str, "$anonymous") === false) {
            $filter['force'] = " !$anonymous";
        }

        $permissions = $this->security->isGranted(
            ['lead:leads:viewown', 'lead:leads:viewother'],
            'RETURN_ARRAY'
        );

        if ($permissions['lead:leads:viewown'] || $permissions['lead:leads:viewother']) {
            //only show own leads if the user does not have permission to view others
            if (!$permissions['lead:leads:viewother']) {
                $filter['force'] .= " $mine";
            }

            $results = $this->leadModel->getEntities(
                [
                    'limit'          => 5,
                    'filter'         => $filter,
                    'withTotalCount' => true,
                ]);

            $count = $results['count'];

            if ($count > 0) {
                $leads       = $results['results'];
                $leadResults = [];

                foreach ($leads as $lead) {
                    $leadResults[] = $this->templating->renderResponse(
                        'MauticLeadBundle:SubscribedEvents\Search:global.html.php',
                        ['lead' => $lead]
                    )->getContent();
                }

                if ($results['count'] > 5) {
                    $leadResults[] = $this->templating->renderResponse(
                        'MauticLeadBundle:SubscribedEvents\Search:global.html.php',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($results['count'] - 5),
                        ]
                    )->getContent();
                }
                $leadResults['count'] = $results['count'];
                $event->addResults('mautic.lead.leads', $leadResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(['lead:leads:viewown', 'lead:leads:viewother'], 'MATCH_ONE')) {
            $event->addCommands(
                'mautic.lead.leads',
                $this->leadModel->getCommandList()
            );
        }
    }

    /**
     * @param LeadBuildSearchEvent $event
     *
     * @throws \InvalidArgumentException
     */
    public function onBuildSearchCommands(LeadBuildSearchEvent $event)
    {
        $details       = $event->getDetails();
        $searchCommand = $details['command'];

        // add email read search command
        $searchCommands = [
            $this->translator->trans('mautic.lead.lead.searchcommand.email_read'),
            $this->translator->trans('mautic.lead.lead.searchcommand.email_read', [], null, 'en_US'),
        ];
        if (in_array($searchCommand, $searchCommands, true)) {
            $event->setSubQuery($this->buildEmailReadQuery($event));

            return;
        }

        // add email sent search command
        $searchCommands = [
            $this->translator->trans('mautic.lead.lead.searchcommand.email_sent'),
            $this->translator->trans('mautic.lead.lead.searchcommand.email_sent', [], null, 'en_US'),
        ];
        if (in_array($searchCommand, $searchCommands, true)) {
            $event->setSubQuery($this->buildEmailSentQuery($event));

            return;
        }

        // add email queued search command
        $searchCommands = [
            $this->translator->trans('mautic.lead.lead.searchcommand.email_queued'),
            $this->translator->trans('mautic.lead.lead.searchcommand.email_queued', [], null, 'en_US'),
        ];
        if (in_array($searchCommand, $searchCommands, true)) {
            $event->setSubQuery($this->buildEmailQueuedQuery($event));

            return;
        }

        // add email pending search command
        $searchCommands = [
            $this->translator->trans('mautic.lead.lead.searchcommand.email_pending'),
            $this->translator->trans('mautic.lead.lead.searchcommand.email_pending', [], null, 'en_US'),
        ];
        if (in_array($searchCommand, $searchCommands, true)) {
            $event->setSubQuery($this->buildEmailPendingQuery($event));

            return;
        }

        // add sms sent search command
        $searchCommands = [
            $this->translator->trans('mautic.lead.lead.searchcommand.sms_sent'),
            $this->translator->trans('mautic.lead.lead.searchcommand.sms_sent', [], null, 'en_US'),
        ];
        if (in_array($searchCommand, $searchCommands, true)) {
            $event->setSubQuery($this->buildSmsSentQuery($event));

            return;
        }

        // add web sent search command
        $searchCommands = [
            $this->translator->trans('mautic.lead.lead.searchcommand.web_sent'),
            $this->translator->trans('mautic.lead.lead.searchcommand.web_sent', [], null, 'en_US'),
        ];
        if (in_array($searchCommand, $searchCommands, true)) {
            $event->setSubQuery($this->buildWebSentQuery($event));

            return;
        }

        // add mobile sent search command
        $searchCommands = [
            $this->translator->trans('mautic.lead.lead.searchcommand.mobile_sent'),
            $this->translator->trans('mautic.lead.lead.searchcommand.mobile_sent', [], null, 'en_US'),
        ];
        if (in_array($searchCommand, $searchCommands, true)) {
            $event->setSubQuery($this->buildMobileSentQuery($event));

            return;
        }
    }

    /**
     * @param LeadBuildSearchEvent $event
     *
     * @return string
     */
    private function buildEmailQueuedQuery(LeadBuildSearchEvent $event)
    {
        $alias   = $event->getAlias();
        $q       = $event->getQueryBuilder();
        $details = $event->getDetails();
        $expr    = $q->expr()->andX()->add(sprintf("mq.channel = 'email' and mq.status = '%s'", MessageQueue::STATUS_SENT));
        $this->leadRepo->applySearchQueryRelationship(
            $q,
            [
                [
                    'from_alias' => 'l',
                    'table'      => 'message_queue',
                    'alias'      => 'mq',
                    'condition'  => 'l.id = mq.lead_id',
                ],
            ],
            1,
            $this->leadRepo->generateFilterExpression($q, 'mq.channel_id', 'eq', $alias, null, $expr)
        );
        // return parameters so aliases are translated to values
        $details['returnParameter'] = true;
        $details['strict']          = 1;
        $event->setDetails($details);

        return null;
    }

    /**
     * @param LeadBuildSearchEvent $event
     *
     * @return string
     */
    private function buildEmailPendingQuery(LeadBuildSearchEvent $event)
    {
        $query   = '';
        $alias   = $event->getAlias();
        $q       = $event->getQueryBuilder();
        $details = $event->getDetails();
        $expr    = $q->expr()->andX()->add(sprintf("mq.channel = 'email' and mq.status = '%s'", MessageQueue::STATUS_PENDING));

        /** @var EmailRepository $emailRepo */
        $emailRepo = $event->getEntityManager()->getRepository('MauticEmailBundle:Email');
        $emailId   = (int) $details['string'];
        /** @var Email $email */
        $email = $emailRepo->getEntity($emailId);
        if ($email instanceof Email) {
            $variantIds = $email->getRelatedEntityIds();
            $nq         = $emailRepo->getEmailPendingQuery($emailId, $variantIds);
            if ($nq instanceof QueryBuilder) {
                $nq->select('l.id'); // select only id
                $nsql = $nq->getSQL();
                foreach ($nq->getParameters() as $pk => $pv) { // replace all parameters
                    $nsql = preg_replace('/:'.$pk.'/', is_bool($pv) ? (int) $pv : $pv, $nsql);
                }
                $query = $q->expr()
                           ->in('l.id', sprintf('(%s)', $nsql));
            }
        } else {
            $this->leadRepo->applySearchQueryRelationship(
                $q,
                [
                    [
                        'from_alias' => 'l',
                        'table'      => 'message_queue',
                        'alias'      => 'mq',
                        'condition'  => 'l.id = mq.lead_id',
                    ],
                ],
                1,
                $this->leadRepo->generateFilterExpression($q, 'mq.channel_id', 'eq', $alias, null, $expr)
            );
        }
        // return parameters so aliases are translated to values
        $details['returnParameter'] = true;
        $details['strict']          = 1;
        $event->setDetails($details);

        return $query;
    }

    /**
     * @param LeadBuildSearchEvent $event
     *
     * @return string
     */
    private function buildEmailSentQuery(LeadBuildSearchEvent $event)
    {
        return $this->buildEmailQuery($event);
    }

    /**
     * @param LeadBuildSearchEvent $event
     *
     * @return string
     */
    private function buildEmailReadQuery(LeadBuildSearchEvent $event)
    {
        $expr = $event->getQueryBuilder()
                      ->expr()
                      ->andX()
                      ->add('es.is_read = 1');

        return $this->buildEmailQuery($event, $expr);
    }

    /**
     * @param LeadBuildSearchEvent     $event
     * @param CompositeExpression|null $expr
     *
     * @return string
     */
    private function buildEmailQuery(LeadBuildSearchEvent $event, CompositeExpression $expr = null)
    {
        $alias   = $event->getAlias();
        $q       = $event->getQueryBuilder();
        $details = $event->getDetails();
        $this->leadRepo->applySearchQueryRelationship(
            $q,
            [
                [
                    'from_alias' => 'l',
                    'table'      => 'email_stats',
                    'alias'      => 'es',
                    'condition'  => 'l.id = es.lead_id',
                ],
            ],
            1,
            $this->leadRepo->generateFilterExpression($q, 'es.email_id', 'eq', $alias, null, $expr)
        );
        $details['returnParameter'] = true;
        $details['strict']          = 1;
        $event->setDetails($details);

        return null;
    }

    /**
     * @param LeadBuildSearchEvent $event
     *
     * @return string
     */
    private function buildSmsSentQuery(LeadBuildSearchEvent $event)
    {
        $alias   = $event->getAlias();
        $q       = $event->getQueryBuilder();
        $details = $event->getDetails();
        $this->leadRepo->applySearchQueryRelationship(
            $q,
            [
                [
                    'from_alias' => 'l',
                    'table'      => 'sms_message_stats',
                    'alias'      => 'es',
                    'condition'  => 'l.id = es.lead_id',
                ],
            ],
            1,
            $this->leadRepo->generateFilterExpression($q, 'es.sms_id', 'eq', $alias, null)
        );
        $details['returnParameter'] = true;
        $details['strict']          = 1;
        $event->setDetails($details);

        return null;
    }

    /**
     * @param LeadBuildSearchEvent $event
     *
     * @return string
     */
    private function buildWebSentQuery(LeadBuildSearchEvent $event)
    {
        return $this->buildNotificationSentQuery($event);
    }

    /**
     * @param LeadBuildSearchEvent $event
     *
     * @return string
     */
    private function buildMobileSentQuery(LeadBuildSearchEvent $event)
    {
        return $this->buildNotificationSentQuery($event, 1);
    }

    /**
     * @param LeadBuildSearchEvent $event
     * @param int                  $isMobile
     *
     * @return string
     */
    private function buildNotificationSentQuery(LeadBuildSearchEvent $event, $isMobile = 0)
    {
        $alias   = $event->getAlias();
        $q       = $event->getQueryBuilder();
        $details = $event->getDetails();
        $expr    = $event->getQueryBuilder()
                      ->expr()
                      ->andX()
                      ->add(sprintf('pn.mobile = %d', $isMobile));
        $this->leadRepo->applySearchQueryRelationship(
            $q,
            [
                [
                    'from_alias' => 'l',
                    'table'      => 'push_notification_stats',
                    'alias'      => 'es',
                    'condition'  => 'l.id = es.lead_id',
                ],
                [
                    'from_alias' => 'es',
                    'table'      => 'push_notifications',
                    'alias'      => 'pn',
                    'condition'  => 'pn.id = es.notification_id',
                ],
            ],
            1,
            $this->leadRepo->generateFilterExpression($q, 'es.notification_id', 'eq', $alias, null, $expr)
        );
        $details['returnParameter'] = true;
        $details['strict']          = 1;
        $event->setDetails($details);

        return null;
    }
}
