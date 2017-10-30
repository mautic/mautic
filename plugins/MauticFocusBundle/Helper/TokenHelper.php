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

use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class TokenHelper.
 */
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
     * TokenHelper constructor.
     *
     * @param FormModel $model
     */
    public function __construct(FocusModel $model, RouterInterface $router)
    {
        $this->router = $router;
        $this->model  = $model;
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
            foreach ($matches[1] as $k => $id) {
                $token = '{focus='.$id.'}';
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
                    $tokens[$token] = $script;
                } else {
                    $tokens[$token] = '';
                }
            }
        }

        return $tokens;
    }
}
