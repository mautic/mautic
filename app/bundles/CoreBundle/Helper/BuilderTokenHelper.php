<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\ORM\Query\Expr;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class BuilderTokenHelper
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

        $this->permissionSet = array(
            $this->viewPermissionBase.':viewown',
            $this->viewPermissionBase.':viewother'
        );
    }

    /**
     * @param int   $page
     * @param array $entityArguments
     * @param array $viewParameters
     *
     * @return string
     */
    public function getTokenContent($page = 1, $entityArguments = array(), $viewParameters = array())
    {
        if (is_array($page)) {
            // Laziness
            $entityArguments = $page;
            $page            = 1;
        }

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(
            $this->permissionSet,
            "RETURN_ARRAY"
        );


        if (count(array_unique($permissions)) == 1 && end($permissions) == false) {
            return;
        }

        $session = $this->factory->getSession();

        //set limits
        $limit = 5;

        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $request = $this->factory->getRequest();
        $search  = $request->get('search', $session->get('mautic'.$this->langVar.'buildertoken.filter', ''));

        $session->set('mautic'.$this->langVar.'buildertoken.filter', $search);

        $model  = $this->factory->getModel($this->modelName);
        $repo   = $model->getRepository();
        $prefix = $repo->getTableAlias();
        if (!empty($prefix)) {
            $prefix .= '.';
        }

        $filter          = array('string' => $search);
        $filter['force'] = (isset($entityArguments['filter']['force'])) ? $entityArguments['filter']['force'] : array();

        if (isset($permissions[$this->viewPermissionBase.':viewother']) && !$permissions[$this->viewPermissionBase.':viewother']) {
            $filter['force'][] = array('column' => $prefix.'createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser()->getId());
        }

        $entityArguments['filter'] = $filter;

        $entityArguments = array_merge(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'orderByDir' => 'DESC'
            ),
            $entityArguments
        );

        $items = $model->getEntities($entityArguments);
        $count = count($items);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $page = 1;
            } else {
                $page = (ceil($count / $limit)) ?: 1;
            }
            $session->set('mautic'.$this->langVar.'buildertoken.page', $page);
        }

        return $this->factory->getTemplating()->render(
            $this->bundleName.':SubscribedEvents\BuilderToken:list.html.php',
            array_merge(
                $viewParameters,
                array(
                    'items'       => $items,
                    'page'        => $page,
                    'limit'       => $limit,
                    'totalCount'  => $count,
                    'tmpl'        => $request->get('tmpl', 'index'),
                    'searchValue' => $search
                )
            )
        );
    }

    /**
     * @param string              $tokenRegex     Token regex without wrapping regex escape characters.  Use (value) or (.*?) where the ID of the
     *                                            entity should go. i.e. {pagelink=(value)}
     * @param string              $filter         String to filter results by
     * @param string              $labelColumn    The column that houses the label
     * @param string              $valueColumn    The column that houses the value
     * @param CompositeExpression $expr           Use $factory->getDatabase()->getExpressionBuilder()->andX()
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
            "RETURN_ARRAY"
        );

        if (count(array_unique($permissions)) == 1 && end($permissions) == false) {
            return;
        }

        $repo   = $this->factory->getModel($this->modelName)->getRepository();
        $prefix = $repo->getTableAlias();
        if (!empty($prefix)) {
            $prefix .= '.';
        }

        $exprBuilder = $this->factory->getDatabase()->getExpressionBuilder();
        if ($expr == null) {
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

            $parameters = array(
                'label' => strtolower($filter).'%'
            );
        } else {
            $parameters = array();
        }

        $items = $repo->getSimpleList($expr, $parameters, $labelColumn, $valueColumn);

        $tokens = array();
        foreach ($items as $item) {
            $token          = str_replace(array('(value)', '(.*?)'), $item['value'], $tokenRegex);
            $tokens[$token] = $item['label'];
        }

        return $tokens;
    }

    /**
     * Override default permission set of viewown and viewother
     *
     * @param array $permissions
     */
    public function setPermissionSet(array $permissions)
    {
        $this->permissionSet = $permissions;
    }

    /**
     * @param $content
     * @param $encodeTokensInUrls
     */
    static public function replaceVisualPlaceholdersWithTokens(&$content, $encodeTokensInUrls = array('leadfields'))
    {
        if (is_array($content)) {
            foreach ($content as &$slot) {
                self::replaceVisualPlaceholdersWithTokens($slot);
                if ($encodeTokensInUrls) {
                    self::encodeUrlTokens($slot, $encodeTokensInUrls);
                }
            }
        } else {
            $content = preg_replace('/'.self::getVisualTokenHtml(null, null, true).'/smi', '$1', $content);
            if ($encodeTokensInUrls) {
                self::encodeUrlTokens($content, $encodeTokensInUrls);
            }
        }
    }

    /**
     * Prevent tokens in URLs from being converted to visual tokens by encoding the brackets
     *
     * @param string $content
     * @param array  $tokenKeys
     */
    static public function encodeUrlTokens(&$content, array $tokenKeys)
    {
        // Special handling for leadfield tokens in URLs
        $foundMatches = preg_match_all('/<a.*?href=["\'].*?=({['.implode('|', $tokenKeys).'].*?}).*?["\']/i', $content, $matches);
        if ($foundMatches) {
            foreach ($matches[0] as $link) {
                // There may be more than one leadfield token in the URL
                preg_match_all('/{['.implode('|', $tokenKeys).'].*?}/i', $link, $tokens);
                $newLink = $link;
                foreach ($tokens as $token) {
                    // Encode brackets
                    $encodedToken = str_replace(array('{', '}'), array('%7B', '%7D'), $token);
                    $newLink      = str_replace($token, $encodedToken, $newLink);
                }
                $content = str_replace($link, $newLink, $content);
            }
        }
    }

    /**
     * @param $tokens
     * @param $content
     */
    static public function replaceTokensWithVisualPlaceholders($tokens, &$content)
    {
        if (is_array($content)) {
            foreach ($content as &$slot) {
                self::replaceTokensWithVisualPlaceholders($tokens, $slot);
            }
        } else {
            if (isset($tokens['visualTokens'])) {
                // Get all the tokens in the content
                $replacedTokens = array();
                if (preg_match_all('/{(.*?)}/', $content, $matches)) {
                    $search = $replace = array();

                    foreach ($matches[0] as $tokenMatch) {
                        if (!in_array($tokenMatch, $replacedTokens)) {
                            $replacedTokens[] = $tokenMatch;

                            if (strstr($tokenMatch, '|')) {
                                // This token has been customized
                                $tokenParts = explode('|', $tokenMatch);
                                $token      = $tokenParts[0].'}';
                            } else {
                                $token = $tokenMatch;
                            }

                            if (in_array($token, $tokens['visualTokens'])) {
                                $search[]  = $tokenMatch;
                                $replace[] = self::getVisualTokenHtml($tokenMatch, $tokens['tokens'][$token]);
                            }
                        }
                    }

                    $content = str_ireplace($search, $replace, $content);
                }
            }
        }
    }

    /**
     * @param $token
     * @param $description
     * @param $forPregReplace
     *
     * @return string
     */
    static public function getVisualTokenHtml($token, $description, $forPregReplace = false)
    {
        if ($forPregReplace) {
            return preg_quote('<strong contenteditable="false" data-token="', '/').'(.*?)'.preg_quote('">**', '/')
            .'(.*?)'.preg_quote('**</strong>', '/');
        }

        return '<strong contenteditable="false" data-token="'.$token.'">**'.$description.'**</strong>';
    }
}
