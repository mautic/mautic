<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends AbstractCommonModel<GrapesJsBuilder>
 */
class GrapesJsBuilderModel extends AbstractCommonModel
{
    public function __construct(
        private RequestStack $requestStack,
        private EmailModel $emailModel,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return GrapesJsBuilderRepository
     */
    public function getRepository()
    {
        /** @var GrapesJsBuilderRepository $repository */
        $repository = $this->em->getRepository(GrapesJsBuilder::class);

        $repository->setTranslator($this->translator);

        return $repository;
    }

    /**
     * Add or edit email settings entity based on request.
     */
    public function addOrEditEntity(Email $email): void
    {
        if ($this->emailModel->isUpdatingTranslationChildren()) {
            return;
        }

        $grapesJsBuilder = $this->getRepository()->findOneBy(['email' => $email]);

        if (!$grapesJsBuilder) {
            $grapesJsBuilder = new GrapesJsBuilder();
            $grapesJsBuilder->setEmail($email);
        }

        if ($this->requestStack->getCurrentRequest()->request->has('grapesjsbuilder')) {
            $data = $this->requestStack->getCurrentRequest()->get('grapesjsbuilder', '');

            if (isset($data['customMjml'])) {
                $grapesJsBuilder->setCustomMjml($data['customMjml']);
            }

            $this->getRepository()->saveEntity($grapesJsBuilder);

            $customHtml = $this->requestStack->getCurrentRequest()->get('emailform')['customHtml'] ?? null;
            $email->setCustomHtml($customHtml);
            $this->emailModel->getRepository()->saveEntity($email);
        }
    }

    public function getGrapesJsFromEmailId(?int $emailId)
    {
        if ($email = $this->emailModel->getEntity($emailId)) {
            return $this->getRepository()->findOneBy(['email' => $email]);
        }
    }
}
