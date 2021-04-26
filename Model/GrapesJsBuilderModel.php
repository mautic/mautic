<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Model;

use Mautic\CoreBundle\Helper\ArrayHelper;
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

    /**
     * GrapesJsBuilderModel constructor.
     */
    public function __construct(RequestStack $requestStack, EmailModel $emailModel)
    {
        $this->requestStack = $requestStack;
        $this->emailModel   = $emailModel;
    }

    /**
     * {@inheritdoc}
     *
     * @return GrapesJsBuilderRepository
     */
    public function getRepository()
    {
        /** @var GrapesJsBuilderRepository $repository */
        $repository = $this->em->getRepository('GrapesJsBuilderBundle:GrapesJsBuilder');

        $repository->setTranslator($this->translator);

        return $repository;
    }

    /**
     * Add or edit email settings entity based on request.
     */
    public function addOrEditEntity(Email $email)
    {
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
        }

        $this->getRepository()->saveEntity($grapesJsBuilder);

        $customHtml = ArrayHelper::getValue('customHtml', $this->requestStack->getCurrentRequest()->get('emailform'));
        $email->setCustomHtml($customHtml);
        $this->emailModel->getRepository()->saveEntity($email);
    }

    public function getGrapesJsFromEmailId(?int $emailId)
    {
        if ($email = $this->emailModel->getEntity($emailId)) {
            return $this->getRepository()->findOneBy(['email' => $email]);
        }
    }
}
