<?php

namespace Mautic\PageBundle\Helper;

use Mautic\PageBundle\Model\PageModel;

class TokenHelper
{
    public function __construct(
        protected PageModel $model,
    ) {
    }

    public function findPageTokens($content, $clickthrough = []): array
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
