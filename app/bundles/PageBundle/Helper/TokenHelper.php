<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Helper;

use Mautic\PageBundle\Model\PageModel;

/**
 * Class TokenHelper.
 */
class TokenHelper
{
    /**
     * @var
     */
    protected $model;

    /**
     * TokenHelper constructor.
     *
     * @param PageModel $model
     */
    public function __construct(PageModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param $content
     * @param $clickthrough
     *
     * @return array
     */
    public function findPageTokens($content, $clickthrough = [])
    {
        preg_match_all('/{pagelink=(.*?)}/', $content, $matches);

        $tokens = [];
        if (!empty($matches[1])) {
            foreach ($matches[1] as $key => $pageId) {
                $token = $matches[0][$key];
                if (!empty($tokens[$token])) {
                    continue;
                }

                $page = $this->model->getEntity($pageId);

                if (!$page) {
                    continue;
                }

                $tokens[$token] = $this->model->generateUrl($page, true, $clickthrough);
            }

            unset($matches);
        }

        return $tokens;
    }
}
