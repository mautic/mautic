<?php

namespace MauticPlugin\MauticFocusBundle\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class TokenHelper
{
    private string $regex = '{focus=(.*?)}';

    public function __construct(
        protected FocusModel $model,
        protected RouterInterface $router,
        protected CorePermissions $security,
    ) {
    }

    public function findFocusTokens($content): array
    {
        $regex = '/'.$this->regex.'/i';

        preg_match_all($regex, $content, $matches);

        $tokens = [];

        if (count($matches[0])) {
            foreach ($matches[1] as $id) {
                $token = '{focus='.$id.'}';
                $focus = $this->model->getEntity((int) $id);
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
        }

        return $tokens;
    }
}
