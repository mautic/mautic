<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    private $regex = '{focus=(.*?)}';

    /**
     * @var FocusModel
     */
    protected $model;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * PageSubscriber constructor.
     *
     * @param FocusModel      $model
     * @param RouterInterface $router
     */
    public function __construct(FocusModel $model, RouterInterface $router)
    {
        $this->router = $router;
        $this->model  = $model;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_DISPLAY => ['onPageDisplay', 0],
            PageEvents::PAGE_ON_BUILD   => ['onPageBuild', 0],
        ];
    }

    /**
     * Add forms to available page tokens.
     *
     * @param PageBuilderEvent $event
     */
    public function onPageBuild(PageBuilderEvent $event)
    {
        if ($event->tokensRequested($this->regex)) {
            $tokenHelper = new BuilderTokenHelper($this->factory, 'focus', $this->model->getPermissionBase(), 'MauticFocusBundle', 'mautic.focus');
            $event->addTokensFromHelper($tokenHelper, $this->regex, 'name', 'id', true);
        }
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay(PageDisplayEvent $event)
    {
        $content = $event->getContent();
        $regex   = '/'.$this->regex.'/i';

        preg_match_all($regex, $content, $matches);

        if (count($matches[0])) {
            foreach ($matches[1] as $k => $id) {
                $focus = $this->model->getEntity($id);
                if ($focus !== null
                    && (
                        $focus->isPublished()
                        || $this->security->hasEntityAccess(
                            'plugin:focus:items:viewown',
                            'plugin:focus:items:viewother',
                            $focus->getCreatedBy()
                        )
                    )
                ) {
                    $script = '<script src="'.$this->router->generate('mautic_focus_generate', ['id' => $id], true)
                        .'" type="text/javascript" charset="utf-8" async="async"></script>';
                    $content = preg_replace('#{focus='.$id.'}#', $script, $content);
                } else {
                    $content = preg_replace('#{focus='.$id.'}#', '', $content);
                }
            }
        }
        $event->setContent($content);
    }
}
