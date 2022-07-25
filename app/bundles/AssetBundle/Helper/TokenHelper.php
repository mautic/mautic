<?php

namespace Mautic\AssetBundle\Helper;

use Mautic\AssetBundle\Model\AssetModel;

class TokenHelper
{
    /**
     * @var AssetModel
     */
    protected $model;

    public function __construct(AssetModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param $content
     * @param $clickthrough
     *
     * @return array
     */
    public function findAssetTokens($content, $clickthrough = [])
    {
        $tokens = [];

        preg_match_all('/{assetlink=(.*?)}/', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $key => $assetId) {
                $token = $matches[0][$key];

                if (isset($tokens[$token])) {
                    continue;
                }

                $asset          = $this->model->getEntity($assetId);
                $tokens[$token] = (null !== $asset) ? $this->model->generateUrl($asset, true, $clickthrough) : '';
            }
        }

        return $tokens;
    }
}
