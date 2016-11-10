<?php
/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFullContactBundle\Controller;

use Mautic\FormBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends FormController
{

    /**
     *
     * @return Response
     */
    public function callbackAction()
    {
        if (!$this->request->request->has('result') || !$this->request->request->has('webhookId')) {
            return new Response('ERROR');
        }

        $data = $this->request->request->get('result', [], true);
        $id = $this->request->request->get('webhookId', [], true);
        $id = substr($id, strlen('fullcontact#'));
        $result = json_decode($data, true);

        $org = null;
        if (array_key_exists('organizations', $result)) {
            foreach ($result['organizations'] as $organization) {
                if ($organization['isPrimary']) {
                    $org = $organization;
                    break;
                }
            }

            if (null === $org && 0 !== count($result['organizations'])) {
                // primary not found, use the first one if exists
                $org = $result['organizations'][0];
            }
        }

        $loc = null;
        if (array_key_exists('demographics', $result) && array_key_exists('locationDeduced', $result['demographics'])) {
            $loc = $result['demographics']['locationDeduced'];
        }

        $social = [];
        $socialProfiles = [];
        if (array_key_exists('socialProfiles', $result)) {
            $socialProfiles = $result['socialProfiles'];
        }
        foreach (['facebook', 'foursquare', 'googleplus', 'instagram', 'linkedin', 'twitter'] as $p) {
            foreach ($socialProfiles as $socialProfile) {
                if (array_key_exists('type', $socialProfile) && $socialProfile['type'] === $p) {
                    $social[$p] = (array_key_exists('url', $socialProfile))?$socialProfile['url'] :'';
                    break;
                }
            }
        }

        $data = [];

        if (array_key_exists('contactInfo', $result)) {
            $data = [
                'lastname' => array_key_exists('familyName', $result['contactInfo'])? $result['contactInfo']['familyName'] : '',
                'firstname' => array_key_exists('givenName', $result['contactInfo'])?$result['contactInfo']['givenName'] : '',
                'website' => (array_key_exists('websites', $result['contactInfo']) && count(
                        $result['contactInfo']['websites']
                    )) ? $result['contactInfo']['websites'][0]['url'] : '',
                'skype' => (array_key_exists('chats', $result['contactInfo']) && array_key_exists(
                        'skype',
                        $result['contactInfo']['chats']
                    )) ? $result['contactInfo']['chats']['skype']['handle'] : '',
            ];
        }
        $data = array_merge($data, [
            'company' => (null !== $org) ? $org['name'] : '',
            'position' => (null !== $org) ? $org['title'] : '',
            'city' => (null !== $loc && array_key_exists('city', $loc) && array_key_exists(
                    'name',
                    $loc['city']
                )) ? $loc['city']['name'] : '',
            'state' => (null !== $loc && array_key_exists('state', $loc) && array_key_exists(
                    'name',
                    $loc['state']
                )) ? $loc['state']['name'] : '',
            'country' => (null !== $loc && array_key_exists('country', $loc) && array_key_exists(
                    'name',
                    $loc['country']
                )) ? $loc['country']['name'] : '',
        ]);

        $data = array_merge($data, $social);

        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model = $this->getModel('lead');
        /** @var Lead $lead */
        $lead = $model->getEntity($id);
        $model->setFieldValues($lead, $data);
        $model->saveEntity($lead);

        /*
{
    "status": 200,
    "likelihood": 0.9,
    "requestId": "773e6782-62bb-4fc6-9f38-28ea0b5db261",
    "photos": {
        "twitter": [
            {
                "url": "https://d2ojpxxtu63wzl.cloudfront.net/static/ecf57683e2c22abb296f822377597290_fe346265298c3d008a4af9c54483809f55508dd4c238789dc9a115ae8395c381",
                "typeName": "Twitter",
                "isPrimary": false
            }
        ],
        "quora": [
            {
                "url": "https://d2ojpxxtu63wzl.cloudfront.net/image/8aeb64288905cbc9e73678eab24032d4_260589322c246c2e8aef934f234b4fc0c33a437e247dc80f6f9b909d2a2ba990",
                "typeName": "Quora"
            }
        ],
        "foursquare": [
            {
                "url": "https://d2ojpxxtu63wzl.cloudfront.net/static/ac4cac11df61b43c503d4c3101604742_80a63ae50b5cc0e8f9dacb522547d923f1b3961ca666fd661fb2b3f5656a644d",
                "typeName": "Foursquare",
                "isPrimary": false
            }
        ],
        "googleplus": [
            {
                "url": "https://d2ojpxxtu63wzl.cloudfront.net/static/a508fc51b2d287175f36a44aead7438a_6be07253a0bbaf5929d148cc2fca7f266ffd41a1053862e2f3016594a134602d",
                "typeName": "Google Plus",
                "isPrimary": false
            }
        ]
    },
    "contactInfo": {
        "familyName": "Lorang",
        "givenName": "Bart",
        "fullName": "Bart Lorang",
        "websites": [
            {
                "url": "https://fullcontact.com"
            },
            {
                "url": "http://www.flickr.com/people/39267654@N00/"
            },
            {
                "url": "http://picasaweb.google.com/lorangb"
            }
        ],
        "chats": {
            "gtalk": [
                {
                    "handle": "lorangb@gmail.com"
                }
            ],
            "skype": [
                {
                    "handle": "bart.lorang"
                }
            ]
        }
    },
    "organizations": [
        {
            "isPrimary" : true,
            "name" : "FullContact",
            "startDate" : "2010-01",
            "title" : "Co-Founder & CEO",
            "current" : true
        }, {
            "isPrimary" : false,
            "name" : "Dimension Technology Solutions",
            "startDate" : "2009-06",
            "endDate" : "2009-12",
            "title" : "Owner",
            "current" : false
        }, {
            "isPrimary" : false,
            "name" : "Dimension Technology Solutions",
            "startDate" : "2002-06",
            "endDate" : "2006-06",
            "title" : "Chief Technology Officer",
            "current" : false
        }, {
            "isPrimary" : false,
            "name" : "Dimension Technology Solutions",
            "startDate" : "1996-06",
            "endDate" : "2002-06",
            "title" : "Partner / Development Manager",
            "current" : false
        }, {
            "isPrimary" : false,
            "name" : "Dimension Technology Solutions",
            "startDate" : "2006-06",
            "endDate" : "2009-06",
            "title" : "President",
            "current" : false
        }
    ],
    "demographics": {
        "locationGeneral": "Boulder, Colorado",
        "locationDeduced" : {
            "normalizedLocation" : "Boulder, Colorado",
            "deducedLocation" : "Boulder, Colorado, United States",
            "city" : {
                "deduced" : false,
                "name" : "Boulder"
            },
            "state" : {
                "deduced" : false,
                "name" : "Colorado",
                "code" : "CO"
            },
            "country" : {
                "deduced" : true,
                "name" : "United States",
                "code" : "US"
            },
            "continent" : {
                "deduced" : true,
                "name" : "North America"
            },
            "county" : {
                "deduced" : true,
                "name" : "Boulder",
                "code" : "Boulder"
            },
            "likelihood" : 1.0
        },
        "age": "33",
        "gender": "Male",
        "ageRange": "25-34"
    },
    "socialProfiles": {
        "aboutme": [
            {
                "typeName": "About.me",
                "username": "lorangb",
                "url": "http://about.me/lorangb"
            }
        ],
        "twitter": [
            {
                "typeName": "Twitter",
                "username": "bartlorang",
                "url": "http://twitter.com/bartlorang"
            }
        ],
        "quora": [
            {
                "typeName": "Quora",
                "username": "bart-lorang",
                "url": "http://quora.com/bart-lorang"
            }
        ],
        "linkedin": [
            {
                "typeName": "LinkedIn",
                "username": "bartlorang",
                "url": "http://linkedin.com/in/bartlorang"
            }
        ],
        "klout": [
            {
                "typeName": "Klout",
                "username": "lorangb",
                "url": "http://klout.com/#/lorangb"
            }
        ],
        "youtube": [
            {
                "typeName": "YouTube",
                "username": "lorangb",
                "url": "http://youtube.com/user/lorangb"
            }
        ],
        "myspace": [
            {
                "typeName": "MySpace",
                "userid": "137200880",
                "url": "http://myspace.com/137200880"
            }
        ],
        "foursquare": [
            {
                "typeName": "FourSquare",
                "username": "bartlorang",
                "url": "http://foursquare.com/bartlorang"
            }
        ],
        "googleprofile": [
            {
                "typeName": "Google Profile",
                "userid": "114426306375480734745",
                "url": "http://profiles.google.com/114426306375480734745"
            }
        ],
        "googleplus": [
            {
                "typeName": "Google Plus",
                "userid": "114426306375480734745",
                "url": "http://plus.google.com/114426306375480734745"
            }
        ]
    }
}
         */

        return new Response('OK');
    }
}
