<?php

namespace Mautic\EmailBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Form\Type\BatchCategoryType as BatchType;
use Mautic\EmailBundle\Model\EmailActionModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class BatchEmailController extends AbstractFormController
{
    public function __construct(
        private readonly EmailActionModel $actionModel,
        private readonly CategoryModel $categoryModel,
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security
    ) {
        parent::__construct($doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * Adds or removes categories to multiple emails defined by email ID.
     */
    public function execAction(Request $request): JsonResponse
    {
        $params = $request->get('email_batch');
        $ids    = empty($params['ids']) ? [] : json_decode($params['ids']);

        if ($ids && is_array($ids)) {
            $newCategory = json_decode($params['add']);
            $emailIds    = json_decode($params['ids']);

            $affected = $this->actionModel->setCategory($emailIds, $this->categoryModel->getEntity($newCategory));

            $this->addFlashMessage('mautic.email.batch_emails_affected', [
                '%count%' => count($affected),
            ]);
        } else {
            $this->addFlashMessage('mautic.core.error.ids.missing');
        }

        $title = current($affected)->getCategory()->getTitle();

        return new JsonResponse([
            'closeModal'  => true,
            'flashes'     => $this->getFlashContent(),
            'affected'    => $affected ? array_map(fn (Email $affected) => $affected->getId(), $affected) : [],
            'newCategory' => $affected ? $title : null,
        ]);
    }

    /**
     * View the modal form for adding contacts into categories in batches.
     *
     * @todo Inject the selected email ids into the view
     */
    public function indexAction(): Response
    {
        $route = $this->generateUrl('mautic_email_batch_categories_set');
        $rows  = $this->categoryModel->getLookupResults('email', '', 300);
        $items = [];

        foreach ($rows as $category) {
            $items[$category['title']] = $category['id'];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $this->createForm(
                        BatchType::class,
                        [],
                        [
                            'items'  => $items,
                            'action' => $route,
                        ]
                    )->createView(),
                ],
                'contentTemplate' => '@MauticEmail/Batch/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_email_index',
                    'mauticContent' => 'emailBatch',
                    'route'         => $route,
                ],
            ]
        );
    }
}
