<?php

namespace Mautic\CoreBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

/**
 * Class BuilderTokenHelper.
 */
class BuilderTokenHelper
{
    private $isConfigured = false;

    private $security;
    private $modelFactory;
    private $connection;
    private $userHelper;

    protected $permissionSet;
    protected $modelName;
    protected $viewPermissionBase;
    protected $langVar;
    protected $bundleName;

    public function __construct(
        CorePermissions $security,
        ModelFactory $modelFactory,
        Connection $connection,
        UserHelper $userHelper
    ) {
        $this->security      = $security;
        $this->modelFactory  = $modelFactory;
        $this->connection    = $connection;
        $this->userHelper    = $userHelper;
    }

    /**
     * This method must be called before the BuilderTokenHelper can be used.
     */
    public function configure(
        string $modelName,
        ?string $viewPermissionBase = null,
        ?string $bundleName = null,
        ?string $langVar = null
    ): void {
        $this->modelName          = $modelName;
        $this->viewPermissionBase = (!empty($viewPermissionBase)) ? $viewPermissionBase : "$modelName:{$modelName}s";
        $this->bundleName         = (!empty($bundleName)) ? $bundleName : 'Mautic'.ucfirst($modelName).'Bundle';
        $this->langVar            = (!empty($langVar)) ? $langVar : $modelName;

        $this->permissionSet = [
            $this->viewPermissionBase.':viewown',
            $this->viewPermissionBase.':viewother',
        ];

        $this->isConfigured = true;
    }

    /**
     * @param string              $tokenRegex  Token regex without wrapping regex escape characters.  Use (value) or (.*?) where the ID of the
     *                                         entity should go. i.e. {pagelink=(value)}
     * @param string              $filter      String to filter results by
     * @param string              $labelColumn The column that houses the label
     * @param string              $valueColumn The column that houses the value
     * @param CompositeExpression $expr        Use $factory->getDatabase()->getExpressionBuilder()->andX()
     *
     * @throws \BadMethodCallException
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
        if (!$this->isConfigured) {
            throw new \BadMethodCallException('You must call the "'.get_class($this).'::configure()" method first.');
        }

        //set some permissions
        $permissions = $this->security->isGranted(
            $this->permissionSet,
            'RETURN_ARRAY'
        );

        if (1 == count(array_unique($permissions)) && false == end($permissions)) {
            return;
        }

        $repo   = $this->modelFactory->getModel($this->modelName)->getRepository();
        $prefix = $repo->getTableAlias();
        if (!empty($prefix)) {
            $prefix .= '.';
        }

        $exprBuilder = $this->connection->getExpressionBuilder();
        if (null == $expr) {
            $expr = $exprBuilder->andX();
        }

        if (isset($permissions[$this->viewPermissionBase.':viewother']) && !$permissions[$this->viewPermissionBase.':viewother']) {
            $expr->add(
                $exprBuilder->eq($prefix.'created_by', $this->userHelper->getUser()->getId())
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
     */
    public function setPermissionSet(array $permissions)
    {
        $this->permissionSet = $permissions;
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
