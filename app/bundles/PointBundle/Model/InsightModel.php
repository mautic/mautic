<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupContactScore;
use Mautic\PointBundle\Entity\PointInsight;
use Mautic\PointBundle\Entity\PointInsightRepository;
use Mautic\PointBundle\Form\Type\PointInsightType;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends CommonFormModel<PointInsight>
 */
class InsightModel extends CommonFormModel
{
    public function __construct(
        protected LeadModel $leadModel,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return PointInsightRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(PointInsight::class);
    }

    public function getPermissionBase(): string
    {
        return 'point:insights';
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof PointInsight) {
            throw new \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException(['PointInsight']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(PointInsightType::class, $entity, $options);
    }

    public function getEntity($id = null): ?PointInsight
    {
        if (null === $id) {
            return new PointInsight();
        }

        return parent::getEntity($id);
    }

    public function executePointInsights(Lead $contact, Group $changedGroup): void
    {
        $insights = $this->getRepository()->findBy([
            'isPublished'   => true,
            'insightType'   => PointInsight::INSIGHT_TYPE_COMPARE_POINT_GROUPS,
            'insightAction' => PointInsight::INSIGHT_ACTION_SET_CUSTOM_FIELD,
        ]);

        $hasUpdates = false;
        foreach ($insights as $insight) {
            if (!in_array($changedGroup->getId(), $insight->getPointGroups(), true)) {
                continue;
            }
            if ($this->executePointInsight($insight, $contact)) {
                $hasUpdates = true;
            }
        }

        if ($hasUpdates) {
            $this->leadModel->saveEntity($contact, false);
        }
    }

    private function executePointInsight(PointInsight $insight, Lead $contact): bool
    {
        $pointGroupIds = $insight->getPointGroups();
        $customField   = $insight->getCustomField();

        if (empty($pointGroupIds) || empty($customField)) {
            return false;
        }

        $qb      = $this->em->createQueryBuilder();
        $results = $qb
            ->select('g.id', 'g.name', 'COALESCE(s.score, 0) as score')
            ->from(Group::class, 'g')
            ->leftJoin(
                GroupContactScore::class,
                's',
                'WITH',
                'g.id = s.group AND s.contact = :contactId'
            )
            ->where('g.id IN (:groupIds)')
            ->orderBy('score', 'DESC')
            ->addOrderBy('g.id', 'ASC')
            ->setParameter('contactId', $contact->getId())
            ->setParameter('groupIds', $pointGroupIds)
            ->getQuery()
            ->getArrayResult();

        if (empty($results)) {
            return false;
        }

        $winner   = $results[0];
        $maxScore = (int) $winner['score'];

        if (0 === $maxScore) {
            return false;
        }

        $hasMultipleWinners = isset($results[1]) && (int) $results[1]['score'] === $maxScore;
        $currentValue       = $contact->getFieldValue($customField);

        if ($hasMultipleWinners && !empty($currentValue)) {
            return false;
        }

        $newValue = $winner['id'].' ('.$winner['name'].')';
        $contact->addUpdatedField($customField, $newValue);

        return true;
    }
}
