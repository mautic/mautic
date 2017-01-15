<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Helper;

use Mautic\AssetBundle\Model\AssetModel;

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
     * @param AssetModel $model
     */
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
                $tokens[$token] = ($asset !== null) ? $this->model->generateUrl($asset, true, $clickthrough) : '';
            }
        }

        return $tokens;
    }
}
