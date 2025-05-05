<?php

namespace Mautic\PointBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PointBundle\Entity\PointInsight;
use Mautic\PointBundle\Entity\PointInsightRepository;
use Mautic\PointBundle\Form\Type\PointInsightType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InsightModel extends FormModel
{
    public function __construct(
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        TranslatorInterface $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
        private readonly RequestStack $requestStack
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
        return 'point:points';
    }

    /**
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
} 