<?php

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

    public function executePointInsights(Lead $contact): void
    {
        // Hole alle aktiven Point Insights vom Typ "compare_point_groups"
        $insights = $this->getRepository()->findBy([
            'isPublished'   => true,
            'insightType'   => 'compare_point_groups',
            'insightAction' => 'set_custom_field',
        ]);

        foreach ($insights as $insight) {
            $this->executePointInsight($insight, $contact);
        }
    }

    private function executePointInsight(PointInsight $insight, Lead $contact): void
    {
        $pointGroupIds = $insight->getPointGroups();
        $customField   = $insight->getCustomField();

        if (empty($pointGroupIds) || empty($customField)) {
            return;
        }

        // Ein einziger Query für alle Group Scores - sortiert nach Score (DESC) und ID (ASC)
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
            ->addOrderBy('g.id', 'ASC')  // Bei gleicher Score: niedrigste ID gewinnt
            ->setParameter('contactId', $contact->getId())
            ->setParameter('groupIds', $pointGroupIds)
            ->getQuery()
            ->getArrayResult();

        if (empty($results)) {
            return;
        }

        $winner   = $results[0];
        $maxScore = (int) $winner['score'];

        if (0 === $maxScore) {
            return;
        }

        $hasMultipleWinners = isset($results[1]) && (int) $results[1]['score'] === $maxScore;
        $currentValue       = $contact->getFieldValue($customField);

        if ($hasMultipleWinners && !empty($currentValue)) {
            return;
        }

        $newValue = $winner['id'].' ('.$winner['name'].')';
        $this->updateCustomField($contact, $customField, $newValue);
    }

    private function updateCustomField(Lead $contact, string $fieldAlias, string $value): void
    {
        $contact->addUpdatedField($fieldAlias, $value);
        $this->leadModel->saveEntity($contact, false);
    }
}
