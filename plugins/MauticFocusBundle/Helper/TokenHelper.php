<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class TokenHelper
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
     * @var CorePermissions
     */
    protected $security;

    public function __construct(FocusModel $model, RouterInterface $router, CorePermissions $security)
    {
        $this->router   = $router;
        $this->model    = $model;
        $this->security = $security;
    }

    /**
     * @param $content
     *
     * @return array
     */
    public function findFocusTokens($content)
    {
        $regex = '/'.$this->regex.'/i';

        preg_match_all($regex, $content, $matches);

        $tokens = [];

        if (count($matches[0])) {
            foreach ($matches[1] as $id) {
                $token = '{focus='.$id.'}';
                $focus = $this->model->getEntity($id);
                if (null !== $focus
                    && (
                        $focus->isPublished()
                        || $this->security->hasEntityAccess(
                            'focus:items:viewown',
                            'focus:items:viewother',
                            $focus->getCreatedBy()
                        )
                    )
                ) {
                    $script = '<script src="'.
                        $this->router->generate(
                        'mautic_focus_generate',
                        ['id' => $id],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ).
                    '" type="text/javascript" charset="utf-8" async="async"></script>';
                    $tokens[$token] = $script;
                } else {
                    $tokens[$token] = '';
                }
            }
        }

        return $tokens;
    }
}
