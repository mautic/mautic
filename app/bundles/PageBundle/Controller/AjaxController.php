<?php

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\VariantAjaxControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\PageBundle\Form\Type\AbTestPropertiesType;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Environment;

final class AjaxController extends CommonAjaxController
{
    use VariantAjaxControllerTrait;

    private PageModel $pageModel;

    #[Required]
    public function autowirePageAjaxController(
        PageModel $pageModel,
    ): void {
        $this->pageModel = $pageModel;
    }

    public function getAbTestFormAction(Request $request, PageModel $pageModel, Environment $twig): JsonResponse
    {
        return $this->sendJsonResponse($this->getAbTestForm(
            $request,
            $pageModel,
            fn ($formType, $formOptions): FormInterface => $this->createForm(AbTestPropertiesType::class, [], ['formType' => $formType, 'formTypeOptions' => $formOptions]),
            fn (FormInterface $form): string => $this->renderView('@MauticPage/AbTest/form.html.twig', ['form' => $this->setFormTheme($form, $twig, ['@MauticPage/AbTest/form.html.twig', 'MauticPageBundle:FormTheme\Page'])]),
            'page_abtest_settings',
            'page'
        ));
    }

    public function pageListAction(Request $request): JsonResponse
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->pageModel->getLookupResults('page', $filter);
        $dataArray = [];

        foreach ($results as $r) {
            $dataArray[] = [
                'label' => $r['title']." ({$r['id']}:{$r['alias']})",
                'value' => $r['id'],
            ];
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Called by parent::getBuilderTokensAction().
     *
     * @return array
     */
    protected function getBuilderTokens($query)
    {
        return $this->pageModel->getBuilderComponents(null, ['tokens'], $query ?? '');
    }
}
