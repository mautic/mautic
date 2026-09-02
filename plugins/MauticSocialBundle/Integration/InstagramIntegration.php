<?php

namespace MauticPlugin\MauticSocialBundle\Integration;

final class InstagramIntegration extends SocialIntegration
{
    public function getName(): string
    {
        return 'Instagram';
    }

    public function getSupportedFeatures(): array
    {
        return [
            'public_profile',
            'public_activity',
        ];
    }

    public function getIdentifierFields(): array
    {
        return ['instagram'];
    }

    public function getAuthenticationUrl(): string
    {
        return 'https://api.instagram.com/oauth/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://api.instagram.com/oauth/access_token';
    }

    public function getApiUrl($endpoint): string
    {
        return "https://api.instagram.com/v1/{$endpoint}";
    }

    public function getUserData($identifier, &$socialCache): void
    {
        if ($id = $this->getContactUserId($identifier, $socialCache)) {
            $url  = $this->getApiUrl('users/'.$id);
            $data = $this->makeRequest($url);

            if (isset($data->data)) {
                $info = $this->matchUpData($data->data);

                $info['profileImage']   = $data->data->profile_picture;
                $info['profileHandle']  = $data->data->username;
                $socialCache['profile'] = $info;
            }
        }
    }

    /**
     * @param array<string, mixed> $socialCache
     */
    public function getPublicActivity($identifier, &$socialCache): void
    {
        $socialCache['has']['activity'] = false;
        if ($id = $this->getContactUserId($identifier, $socialCache)) {
            // get more than 10 so we can weed out videos
            $data = $this->makeRequest($this->getApiUrl("users/{$id}/media/recent"), ['count' => 20]);

            $socialCache['activity'] = [
                'photos' => [],
                'tags'   => [],
            ];

            if (!empty($data->data)) {
                $socialCache['has']['activity'] = true;
                $count                          = 1;
                foreach ($data->data as $m) {
                    if ($count > 10) {
                        break;
                    }

                    if ('image' == $m->type) {
                        $socialCache['activity']['photos'][] = [
                            'url' => $m->images->standard_resolution->url,
                        ];

                        if (!empty($m->caption->text)) {
                            preg_match_all("/#(\w+)/", $m->caption->text, $tags);
                            foreach ($tags[1] as $tag) {
                                if (isset($socialCache['activity']['tags'][$tag])) {
                                    ++$socialCache['activity']['tags'][$tag]['count'];
                                } else {
                                    $socialCache['activity']['tags'][$tag] = [
                                        'count' => 1,
                                        'url'   => 'http://searchinstagram.com/'.$tag,
                                    ];
                                }
                            }
                        }
                    }
                    ++$count;
                }
            }
        }
    }

    public function getAvailableLeadFields(array $settings = []): array
    {
        return [
            'full_name' => ['type' => 'string'],
            'bio'       => ['type' => 'string'],
            'website'   => ['type' => 'string'],
        ];
    }

    /**
     * @param array<string, mixed> $socialCache
     */
    private function getContactUserId(&$identifier, array &$socialCache): false|string
    {
        if (!empty($socialCache['id'])) {
            return $socialCache['id'];
        }
        if (empty($identifier)) {
            return false;
        }

        $data = $this->makeRequest($this->getApiUrl('users/search'), ['q' => $identifier]);

        if (!empty($data->data)) {
            foreach ($data->data as $user) {
                // its possible that instagram may return multiple users if the username is a base of another
                // for example, search for alan may return alanh, alanhartless, etc
                if (strtolower($user->username) === strtolower($identifier)) {
                    $socialCache['id'] = $user->id;
                    break;
                }
            }

            return (!empty($socialCache['id'])) ? $socialCache['id'] : false;
        }

        return false;
    }

    public function getFormType(): null
    {
        return null;
    }
}
