<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\EmailBundle\Entity\Email;
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
     * GrapesJsBuilderModel constructor.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
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
     *
     * @param Email $email
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
    }
}
