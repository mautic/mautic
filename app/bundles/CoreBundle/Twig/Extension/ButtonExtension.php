<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFunction;

final readonly class ButtonExtension
{
    public function __construct(
        private ButtonHelper $buttonHelper,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $router,
        private TranslatorInterface $translator,
    ) {
    }

    #[AsTwigFunction(name: 'buttonReset', isSafe: ['all'])]
    public function reset(string $location, string $groupType = ButtonHelper::TYPE_GROUP, $item = null): void
    {
        $this->buttonHelper->reset(
            $this->requestStack->getCurrentRequest(),
            $location,
            $groupType,
            $item
        );
    }

    /**
     * @param array<string,mixed> $button
     */
    #[AsTwigFunction(name: 'buttonAdd', isSafe: ['all'])]
    public function addButton(array $button): void
    {
        $this->buttonHelper->addButton($button);
    }

    #[AsTwigFunction(name: 'buttonSetMenuLink', isSafe: ['all'])]
    public function setMenuLink(?string $menuLink): void
    {
        $this->buttonHelper->setMenuLink($menuLink);
    }

    #[AsTwigFunction(name: 'buttonSetWrappingTags', isSafe: ['all'])]
    public function setWrappingTags(?string $wrapOpeningTag, ?string $wrapClosingTag): void
    {
        $this->buttonHelper->setWrappingTags($wrapOpeningTag, $wrapClosingTag);
    }

    #[AsTwigFunction(name: 'buttonSetGroupType', isSafe: ['all'])]
    public function setGroupType(string $groupType): void
    {
        $this->buttonHelper->setGroupType($groupType);
    }

    #[AsTwigFunction(name: 'buttonGetCount')]
    public function getButtonCount(): int
    {
        return $this->buttonHelper->getButtonCount();
    }

    /**
     * @param array<array<string,mixed>> $buttons
     */
    #[AsTwigFunction(name: 'buttonsAdd', isSafe: ['all'])]
    public function addButtons(array $buttons): void
    {
        $this->buttonHelper->addButtons($buttons);
    }

    #[AsTwigFunction(name: 'buttonsRender', isSafe: ['all'])]
    public function render(string $dropdownHtml = '', string $closingDropdownHtml = ''): string
    {
        return $this->buttonHelper->renderButtons($dropdownHtml, $closingDropdownHtml);
    }

    /**
     * @param array<string,bool>   $templateButtons
     * @param array<string,string> $query
     * @param array<string,string> $editAttr
     * @param array<string,string> $routeVars
     * @param mixed                $item
     */
    #[AsTwigFunction(name: 'buttonsAddFromTemplate', isSafe: ['all'])]
    public function addButtonsFromTemplate(
        array $templateButtons,
        array $query,
        string $actionRoute,
        string $indexRoute,
        string $langVar,
        string $nameGetter,
        array $editAttr = [],
        array $routeVars = [],
        $item = null,
        ?string $tooltip = null,
    ): void {
        foreach ($templateButtons as $action => $enabled) {
            if (!$enabled) {
                continue;
            }

            $path     = false;
            $primary  = false;
            $priority = 0;

            switch ($action) {
                case 'clone':
                case 'abtest':
                    $actionQuery = [
                        'objectId' => ('abtest' === $action && method_exists($item, 'getVariantParent') && $item->getVariantParent())
                            ? $item->getVariantParent()->getId() : $item->getId(),
                    ];
                    $icon = ('clone' === $action) ? 'file-copy-line' : 'a-b';
                    $path = $this->router->generate($actionRoute, array_merge(['objectAction' => $action], $actionQuery, $query));
                    break;
                case 'close':
                    $closeParameters = $routeVars['close'] ?? [];
                    $icon            = 'close-line';
                    $path            = $this->router->generate($indexRoute, $closeParameters);
                    $primary         = true;
                    $priority        = 200;
                    break;
                case 'new':
                case 'edit':
                    $actionQuery = ('edit' === $action) ? ['objectId' => $item->getId()] : [];
                    $icon        = ('edit' === $action) ? 'edit-line' : 'add-line';
                    $path        = $this->router->generate($actionRoute, array_merge(['objectAction' => $action], $actionQuery, $query));
                    $primary     = true;
                    break;
                case 'delete':
                    $this->buttonHelper->addButton(
                        [
                            'confirm' => [
                                'message' => $this->translator->trans(
                                    'mautic.'.$langVar.'.form.confirmdelete',
                                    ['%name%' => $item->{$nameGetter}().' ('.$item->getId().')']
                                ),
                                'confirmAction' => $this->router->generate(
                                    $actionRoute,
                                    array_merge(['objectAction' => 'delete', 'objectId' => $item->getId()], $query)
                                ),
                                'template' => 'delete',
                                'btnClass' => false,
                            ],
                            'priority' => -1,
                        ]
                    );
                    break;
            }

            if ($path) {
                $mergeAttr = (!in_array($action, ['edit', 'new'])) ? [] : $editAttr;
                $btnClass  = in_array($action, ['new', 'edit']) ? 'btn btn-primary' : 'btn btn-tertiary';

                $this->buttonHelper->addButton(
                    [
                        'attr' => array_merge(
                            [
                                'class'       => $btnClass,
                                'href'        => $path,
                                'data-toggle' => 'ajax',
                                'id'          => $action,
                            ],
                            $mergeAttr
                        ),
                        'iconClass' => 'ri-'.$icon,
                        'btnText'   => $this->translator->trans('mautic.core.form.'.$action),
                        'priority'  => $priority,
                        'primary'   => $primary,
                        'tooltip'   => $tooltip,
                    ]
                );
            }
        }
    }
}
