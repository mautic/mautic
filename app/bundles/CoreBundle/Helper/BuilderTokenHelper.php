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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

/**
 * Class BuilderTokenHelper.
 */
class BuilderTokenHelper
{
    private $isConfigured = false;

    private $security;
    private $entityManager;
    private $connection;
    private $userHelper;

    protected $permissionSet;
    protected $modelName;
    protected $viewPermissionBase = null;
    protected $langVar            = null;
    protected $bundleName         = null;

    /**
     * @param CorePermissions $security
     * @param EntityManager   $entityManager
     * @param Connection      $connection
     * @param UserHelper      $userHelper
     */
    public function __construct(
        CorePermissions $security,
        EntityManager $entityManager,
        Connection $connection,
        UserHelper $userHelper
    ) {
        $this->security      = $security;
        $this->entityManager = $entityManager;
        $this->connection    = $connection;
        $this->userHelper    = $userHelper;
    }

    /**
     * This method must be called before the BuilderTokenHelper can be used.
     *
     * @param string      $modelName
     * @param string|null $viewPermissionBase
     * @param string|null $bundleName
     * @param string|null $langVar
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
        string $tokenRegex,
        string $filter = '',
        string $labelColumn = 'name',
        string $valueColumn = 'id',
        CompositeExpression $expr = null
    ): ?array {
        if (!$this->isConfigured) {
            throw new \BadMethodCallException('You must call the "'.get_class($this).'"::configure method of this class first.');
        }

        //set some permissions
        $permissions = $this->security->isGranted(
            $this->permissionSet,
            'RETURN_ARRAY'
        );

        if (count(array_unique($permissions)) == 1 && end($permissions) == false) {
            return;
        }

        $repo   = $this->entityManager->getRepository($this->modelName);
        $prefix = $repo->getTableAlias();
        if (!empty($prefix)) {
            $prefix .= '.';
        }

        $exprBuilder = $this->connection->getExpressionBuilder();
        if ($expr == null) {
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
     *
     * @param array $permissions
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
