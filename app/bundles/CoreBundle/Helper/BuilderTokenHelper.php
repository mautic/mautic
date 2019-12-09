<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class BuilderTokenHelper.
 */
class BuilderTokenHelper
{
    protected $viewPermissionBase;
    protected $modelName;
    protected $langVar;
    protected $bundleName;
    protected $permissionSet;

    /**
     * @var MauticFactory
     *
     * @deprecated 2.12 To be removed in 3.0 Inject dependencies in your constructor instead
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     * @param string        $modelName          Model name such as page
     * @param string        $viewPermissionBase Permission base such as page:pages or null to generate from $modelName
     * @param string        $bundleName         Bundle name such as MauticPageBundle or null to generate from $modelName
     * @param null          $langVar            Language base for filter such as page.page or leave blank to use $modelName
     */
    public function __construct(MauticFactory $factory, $modelName, $viewPermissionBase = null, $bundleName = null, $langVar = null)
    {
        $this->factory            = $factory;
        $this->modelName          = $modelName;
        $this->viewPermissionBase = (!empty($viewPermissionBase)) ? $viewPermissionBase : "$modelName:{$modelName}s";
        $this->bundleName         = (!empty($bundleName)) ? $bundleName : 'Mautic'.ucfirst($modelName).'Bundle';
        $this->langVar            = (!empty($langVar)) ? $langVar : $modelName;

        $this->permissionSet = [
            $this->viewPermissionBase.':viewown',
            $this->viewPermissionBase.':viewother',
        ];
    }

    /**
     * @param string              $tokenRegex  Token regex without wrapping regex escape characters.  Use (value) or (.*?) where the ID of the
     *                                         entity should go. i.e. {pagelink=(value)}
     * @param string              $filter      String to filter results by
     * @param string              $labelColumn The column that houses the label
     * @param string              $valueColumn The column that houses the value
     * @param CompositeExpression $expr        Use $factory->getDatabase()->getExpressionBuilder()->andX()
     *
     * @return array|void
     */
    public function getTokens(
        $tokenRegex,
        $filter = '',
        $labelColumn = 'name',
        $valueColumn = 'id',
        CompositeExpression $expr = null
    ) {
        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(
            $this->permissionSet,
            'RETURN_ARRAY'
        );

        if (1 == count(array_unique($permissions)) && false == end($permissions)) {
            return;
        }

        $repo   = $this->factory->getModel($this->modelName)->getRepository();
        $prefix = $repo->getTableAlias();
        if (!empty($prefix)) {
            $prefix .= '.';
        }

        $exprBuilder = $this->factory->getDatabase()->getExpressionBuilder();
        if (null == $expr) {
            $expr = $exprBuilder->andX();
        }

        if (isset($permissions[$this->viewPermissionBase.':viewother']) && !$permissions[$this->viewPermissionBase.':viewother']) {
            $expr->add(
                $exprBuilder->eq($prefix.'created_by', $this->factory->getUser()->getId())
            );
        }

        if (!empty($filter)) {
            $expr->add(
                $exprBuilder->like('LOWER('.$labelColumn.')', ':label')
            );

            $parameters = [
                'label' => strtolower($filter).'%',
            ];
        } else {
            $parameters = [];
        }

        $items = $repo->getSimpleList($expr, $parameters, $labelColumn, $valueColumn);

        $tokens = [];
        foreach ($items as $item) {
            $token          = str_replace(['(value)', '(.*?)'], $item['value'], $tokenRegex);
            $tokens[$token] = $item['label'];
        }

        return $tokens;
    }

    /**
     * Override default permission set of viewown and viewother.
     *
     * @param array $permissions
     */
    public function setPermissionSet(array $permissions)
    {
        $this->permissionSet = $permissions;
    }

    /**
     * Prevent tokens in URLs from being converted to visual tokens by encoding the brackets.
     *
     * @deprecated 2.2.1 - to be removed in 3.0
     *
     * @param string $content
     * @param array  $tokenKeys
     */
    public static function encodeUrlTokens(&$content, array $tokenKeys)
    {
        $processMatches = function ($matches) use (&$content, $tokenKeys) {
            foreach ($matches as $link) {
                // There may be more than one leadfield token in the URL
                preg_match_all('/{['.implode('|', $tokenKeys).'].*?}/i', $link, $tokens);
                $newLink = $link;
                foreach ($tokens as $token) {
                    // Encode brackets
                    $encodedToken = str_replace(['{', '}'], ['%7B', '%7D'], $token);
                    $newLink      = str_replace($token, $encodedToken, $newLink);
                }
                $content = str_replace($link, $newLink, $content);
            }
        };

        // Special handling for leadfield tokens in URLs
        $foundMatches = preg_match_all('/<a.*?href=["\'].*?({['.implode('|', $tokenKeys).'].*?}).*?["\']/i', $content, $matches);
        if ($foundMatches) {
            $processMatches($matches[0]);
        }

        // Special handling for leadfield tokens in image src
        $foundMatches = preg_match_all('/<img.*?src=["\'].*?({['.implode('|', $tokenKeys).'].*?}).*?["\']/i', $content, $matches);
        if ($foundMatches) {
            $processMatches($matches[0]);
        }
    }

    /**
     * @deprecated 2.6.0 to be removed in 3.0
     *
     * @param $token
     * @param $description
     * @param $forPregReplace
     *
     * @return string
     */
    public static function getVisualTokenHtml($token, $description, $forPregReplace = false)
    {
        if ($forPregReplace) {
            return preg_quote('<strong contenteditable="false" data-token="', '/').'(.*?)'.preg_quote('">**', '/')
            .'(.*?)'.preg_quote('**</strong>', '/');
        }

        return '<strong contenteditable="false" data-token="'.$token.'">**'.$description.'**</strong>';
    }
}
