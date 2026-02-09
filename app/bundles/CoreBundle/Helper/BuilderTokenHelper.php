<?php

namespace Mautic\CoreBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\CoreBundle\DTO\TokenFormatOptions;
use Mautic\CoreBundle\DTO\TokenLabelFormat;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuilderTokenHelper
{
    private bool $isConfigured = false;

    protected $permissionSet;

    protected $modelName;

    protected $viewPermissionBase;

    protected $langVar;

    protected $bundleName;

    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        private CorePermissions $security,
        private ModelFactory $modelFactory,
        private Connection $connection,
        private UserHelper $userHelper,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * This method must be called before the BuilderTokenHelper can be used.
     */
    public function configure(
        string $modelName,
        ?string $viewPermissionBase = null,
        ?string $bundleName = null,
        ?string $langVar = null,
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
     * @return array|void
     *
     * @throws \BadMethodCallException
     */
    public function getTokens(
        $tokenRegex,
        $filter = '',
        $labelColumn = 'name',
        $valueColumn = 'id',
        ?CompositeExpression $expr = null,
    ) {
        if (!$this->isConfigured) {
            throw new \BadMethodCallException('You must call the "'.static::class.'::configure()" method first.');
        }

        // set some permissions
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

        $exprBuilder = $this->connection->createExpressionBuilder();

        if (isset($expr) && isset($permissions[$this->viewPermissionBase.':viewother']) && !$permissions[$this->viewPermissionBase.':viewother']) {
            $expr = $expr->with(
                $exprBuilder->eq($prefix.'created_by', $this->userHelper->getUser()->getId())
            );
        }

        if (!empty($filter)) {
            $expr = $expr->with(
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
     * Get tokens with formatted labels based on the provided format options.
     *
     * @param string              $tokenRegex    Token regex without wrapping regex escape characters
     * @param TokenFormatOptions  $formatOptions Formatting options for token labels
     * @param string              $filter        String to filter results by
     * @param string              $labelColumn   The column that houses the label
     * @param string              $valueColumn   The column that houses the value
     * @param CompositeExpression $expr          Optional expression for filtering
     *
     * @return array<string,string>
     */
    public function getFormattedTokens(
        string $tokenRegex,
        TokenFormatOptions $formatOptions,
        string $filter = '',
        string $labelColumn = 'name',
        string $valueColumn = 'id',
        ?CompositeExpression $expr = null,
    ): array {
        $tokens = $this->getTokens($tokenRegex, $filter, $labelColumn, $valueColumn, $expr);

        if (empty($tokens)) {
            return [];
        }

        $prefix    = $this->translator->trans($formatOptions->translationKey).': ';
        $formatted = [];

        foreach ($tokens as $token => $label) {
            $formatted[$token] = match ($formatOptions->format) {
                TokenLabelFormat::SIMPLE_PREFIX => $prefix.$label,
                TokenLabelFormat::LINK_WITH_ID  => $this->formatLinkWithId($token, $label, $prefix, $formatOptions->tokenIdPattern),
            };
        }

        return $formatted;
    }

    /**
     * Format a token label with link prefix and ID like "a:Page: my-alias (123)".
     */
    private function formatLinkWithId(string $token, string $label, string $prefix, ?string $tokenIdPattern): string
    {
        $id = '';
        if (null !== $tokenIdPattern && preg_match('/'.$tokenIdPattern.'/', $token, $matches)) {
            $id = ' ('.$matches[1].')';
        }

        return 'a:'.$prefix.$label.$id;
    }

    /**
     * Override default permission set of viewown and viewother.
     */
    public function setPermissionSet(array $permissions): void
    {
        $this->permissionSet = $permissions;
    }
}
