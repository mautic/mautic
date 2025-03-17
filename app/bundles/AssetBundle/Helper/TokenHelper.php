<?php

namespace Mautic\AssetBundle\Helper;

use Mautic\AssetBundle\Model\AssetModel;

class TokenHelper
{
    public function __construct(
        protected AssetModel $model,
    ) {
    }

    public function findAssetTokens($content, $clickthrough = []): array
    {
        $tokens = [];

        preg_match_all('/{assetlink=(.*?)}/', $content, $matches);
        foreach ($matches[1] as $key => $assetId) {
            $token = $matches[0][$key];

            if (isset($tokens[$token])) {
                continue;
            }

            $asset          = $this->model->getEntity($assetId);
            $tokens[$token] = (null !== $asset) ? $this->model->generateUrl($asset, true, $clickthrough) : '';
        }

        return $tokens;
    }
}
