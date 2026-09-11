<?php

namespace MauticPlugin\MauticFocusBundle\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class TokenHelper
{
    public const REGEX = '/{focus=[^}]*}/i';

    public const MODE_DISPLAY = 'display';

    public const MODE_TRACKING = 'tracking';

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
        preg_match_all(self::REGEX, $content, $matches);

        $tokens = [];

        foreach (array_unique($matches[0]) as $token) {
            $parsedToken = $this->parseToken($token);
            if (null === $parsedToken) {
                $tokens[$token] = '';

                continue;
            }

            $focus = $this->model->getEntity($parsedToken['id']);
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
                        $this->resolveRoute($parsedToken['mode']),
                        ['id' => $parsedToken['id']],
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
     * @return array{id: int, mode: 'display'|'tracking'}|null
     */
    public function parseToken(string $token): ?array
    {
        if (1 !== preg_match('/^{focus=([1-9]\d*)(?:\s*\|\s*(display|tracking)\s*)?}$/i', $token, $matches)) {
            return null;
        }

        return [
            'id'   => (int) $matches[1],
            'mode' => isset($matches[2]) && self::MODE_DISPLAY === strtolower($matches[2]) ? self::MODE_DISPLAY : self::MODE_TRACKING,
        ];
    }

    public function formatToken(int $id, string $mode): string
    {
        return match ($mode) {
            self::MODE_DISPLAY => '{focus='.$id.'|display}',
            self::MODE_TRACKING => '{focus='.$id.'|tracking}',
            default            => throw new \InvalidArgumentException('Unknown Focus token mode.'),
        };
    }

    private function resolveRoute(string $mode): string
    {
        return match ($mode) {
            self::MODE_DISPLAY => 'mautic_focus_generate_display',
            self::MODE_TRACKING => 'mautic_focus_generate',
            default            => throw new \InvalidArgumentException('Unknown Focus token mode.'),
        };
    }
}
