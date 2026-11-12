<?php

namespace MauticPlugin\MauticFocusBundle\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class TokenHelper
{
    public const REGEX = '/{focus=(.*?)}/i';

    // Stops at a line break or another brace, so an unclosed token can't swallow the content after it
    private const TOKEN_REGEX = '/{focus=[^{}\r\n]*}/i';

    public function __construct(
        protected FocusModel $model,
        protected RouterInterface $router,
        protected CorePermissions $security,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function findFocusTokens(string $content): array
    {
        preg_match_all(self::TOKEN_REGEX, $content, $matches);

        $tokens = [];

        foreach (array_unique($matches[0]) as $token) {
            $id = $this->parseToken($token);
            if (null === $id) {
                $tokens[$token] = '';

                continue;
            }

            $focus = $this->model->getEntity($id);
            if (null !== $focus
                && (
                    $focus->isPublished()
                    || $this->security->hasEntityAccess(
                        'focus:items:viewown',
                        'focus:items:viewother',
                        $focus->getCreatedBy()
                    )
                )
            ) {
                $script = '<script src="'.
                    $this->router->generate(
                        'mautic_focus_generate',
                        ['id' => $id],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ).
                '" type="text/javascript" charset="utf-8" async="async"></script>';
                $tokens[$token] = $script;
            } else {
                $tokens[$token] = '';
            }
        }

        return $tokens;
    }

    /**
     * The Focus Item ID of a {focus=id} token, or null when the token isn't valid.
     */
    private function parseToken(string $token): ?int
    {
        if (1 !== preg_match('/^{focus=([1-9]\d*)}$/i', $token, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
