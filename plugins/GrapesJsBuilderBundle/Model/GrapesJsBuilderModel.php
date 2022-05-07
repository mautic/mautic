<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class GrapesJsBuilderModel extends AbstractCommonModel
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EmailModel
     */
    private $emailModel;

    public function __construct(RequestStack $requestStack, EmailModel $emailModel)
    {
        $this->requestStack = $requestStack;
        $this->emailModel   = $emailModel;
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
    public function addOrEditEntity(Email $email)
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
