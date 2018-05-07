<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\FCMNotificationBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Plokko\Firebase\FCM\Exceptions\FcmErrorException;
use Plokko\Firebase\FCM\Message;
use Plokko\Firebase\FCM\Request;
use Plokko\Firebase\FCM\Targets\Token;
use Plokko\Firebase\ServiceAccount;

class PopupController extends CommonController
{
    public function indexAction()
    {
        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->factory->getHelper('template.assets');
        $assetsHelper->addStylesheet('/plugins/FCMNotificationBundle/Assets/css/popup/popup.css');

        $this->integrationHelper = $this->get('mautic.helper.integration');       
        $integration = $this->integrationHelper->getIntegrationObject('FCM');

        $settings          = $integration->getIntegrationSettings();
        $features          = $settings->getSupportedFeatures();
        $featureSettings   = $settings->getFeatureSettings();        

        $response = $this->render(
            'FCMNotificationBundle:Popup:index.html.php',
            [
                'siteUrl' => $this->coreParametersHelper->getParameter('site_url'),
                'icon'  => $integration->getIcon(),
                'sampleNotificationTitle'  => $featureSettings['sample_notification_title'],
                'sampleNotificationText'  => $featureSettings['sample_notification_text']
            ]
        );

        $content = $response->getContent();

        $event = new PageDisplayEvent($content, new Page());
        $this->dispatcher->dispatch(PageEvents::PAGE_ON_DISPLAY, $event);
        $content = $event->getContent();

        return $response->setContent($content);
    }

    public function testAction(){
        //-- Init the service account --//
        $sa = new ServiceAccount('{
  "type": "service_account",
  "project_id": "kiazaki-193912",
  "private_key_id": "250118f179c3df868709073fb7f8d52f469b40d9",
  "private_key": "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCrC9Lojsa/vVcS\nw8r0DU1fY69rxpgj7MY6tzq94I3QtALo33DtpZf2Xtr2ywn3BylOdLmJ3QtYd/HA\nL0F/Emnoy4L3TpAbnErjdSXEYKiTEyfFQgcZmvRdRj0/bcUwVKbjfyF584Nl22GG\nD2zaGmQ4r/tesvi49Wi92Jp8g2WWrIXvFq++V/R1dC+R2Lr2dp1xpTTFsgCv8q18\nPkl+D6mAdQeiNDz8SDVvQAZbWop252hfzrWUTeb41Byl4ZNgn1hCDjYApybDTENQ\nhFZv7AMxdhqFxxj1sKlzAvXScB+1B3obGZYJjTQzRrQNjVT2Zv8BRvmw6+55gQ41\nnIi3FQL/AgMBAAECggEAGAXimtgGsQJSOuf11r4Ril6xUhVD4/PKyY9XeDiUImZN\n7FAJh88Unz0mSrgsj53ZXtQFBQhfIg9CjGf2rnazGkeO/tXxZndWi0i2Q7UBqBpu\nIQXLgnyhaoJFcuWjZszU3anK1Vpur30W3uHGu4EuZ0nMJ7pCNZP+IH/N/sPjnjwk\n1DXKN3oSo0wCuPucJ+z2qzdxArh+kXhovPwpk1obaB1Zsf6j8oAczbrizILofb6A\nnP4rOiXM7ObSIy2a/gn4MdsQzO+aixfQ5mxSrkDwFJyPT2a0Q0ymz+DsPRejdKyc\n1ePH4iaGrh4sOPVvCLZUAWBjf48JZbimhKF/cNCk8QKBgQDlE4Al+Ugib8BQO3i+\nC68+mcm/+Wlqp9qFk2eBTKg31zwTEX6IgWdCteJX+wGLRWaJwFOR6PqMxBFM/XLB\nCPliDV65tUU3vg9HDXRtODqudvD9wfneMfyZvgEbMz5ALgIgRW9BefqNmGj67tY9\nT2qsRHuq7pBhEiQ7RVzqEx6LDwKBgQC/Jk2PbBTkmsmNPDG0QYlYlJNm1OQwWaQk\n7DFy8oPVVbVWif9lUl4/sxM2YduP/q0fjSUNt2U719i2YF0vDSA0e5me3YaiAJHT\n25DRqIK/6qG0rX7kIOQ3L6A+KY0qkhoupEuCjPmTWs3cTJLaWXngaJhGdWnV1Y3o\nBsMklzHJEQKBgQDL9yYGKcR5rQkOJzXl+V9rHOGPRlL/fT9L6iih7xBk0AcUb2I7\nxwSbWHmVntAMIpofExkV9NyJ09YXuB+iEwyBqqfqMKXV8SuHN0qwKP3O8+a1+y4x\nEk740T4I6wKFgLGx7EEYirR1uPvYWip14q13f26hjtMNK2sJP0Rwwt9SLwKBgQCJ\nZHynNCaoUmHAtJ19VQXlt5Vjem4yGpyTNXothwcfViWreDEsoNHBs3OByUDJ9WJ2\npTsW6tVG+FR6cjVIVoTpdFXtDIly9DndL5qeOCMS0xE8DuCAFOw0hnUFeVRQXweW\nstzbj3zsX35MdHWxoFKr7EJXkplp67++IO4u5MYSIQKBgEQR6NQPjNO3aB2eFS2u\nYT+1Kf10KiAaaLuW4DYfHASlPo6tX82IB607QT3imgZu8BC5F3dtpWMKGZjmPgeV\nrUuaP4wjJnueRA5GpGKMjSYPfLBKiIwIwpQTGiEeLS/BUzh8Qo4XlRkN5xzLaiqg\nj6jeDOAKCv+z/wgJsiPI5Y9Z\n-----END PRIVATE KEY-----\n",
  "client_email": "408752069895-compute@developer.gserviceaccount.com",
  "client_id": "113851525409990770458",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://accounts.google.com/o/oauth2/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/408752069895-compute%40developer.gserviceaccount.com"
}');



        $message = new Message();

        $message->notification
        ->setTitle('My notification title')
        ->setBody('My notification body....');

        $message->data->fill([
            'a'=>1,
            'b'=>'2',
        ]);
        $message->data->set('x','value');
        $message->data->y='Same as above';

        $message->setTarget(new Token('daRUoffCzO8:APA91bHxxtT9rE2pmDhNzv8IDwEbcPH8qQ4P1ryNlmVKSntuyUWuEygbT3vwJBztuqiZZ823tFEauM1_YZKwO24SemNDG7zP0g3FDFfR0mqoS_BhM54UcxUn4F3d0F2Zp4b8Q1b1A6tQ'));

        $client = new Client(['debug'=>true]);
        //If true the validate_only is set to true the message will not be submitted but just checked with FCM
        $validate_only = true;
        //Create a request
        $rq = new Request($sa,$validate_only,$client);
        try{
            //Use the request to submit the message
            $message->send($rq);
            //You can force the validate_only flag via the validate method, the request will be left intact
            $message->validate($rq);
        }
        /** Catch all the exceptions @see https://firebase.google.com/docs/reference/fcm/rest/v1/ErrorCode **/
        //Like this
        catch(FcmErrorException $e){
            switch($e->getErrorCode()){
                default:
                case 'UNSPECIFIED_ERROR':
                case 'INVALID_ARGUMENT':
                case 'UNREGISTERED':
                case 'SENDER_ID_MISMATCH':
                case 'QUOTA_EXCEEDED':
                case 'APNS_AUTH_ERROR':
                case 'UNAVAILABLE':
                case 'INTERNAL':
            }
            echo 'FCM error ['.$e->getErrorCode().']: ',$e->getMessage();
        }
        catch(RequestException $e){
            //HTTP response error
            $response = $e->getResponse();
            echo 'Got an http response error:',$response->getStatusCode(),':',$response->getReasonPhrase();
        }
        catch(GuzzleException $e){
            //GuzzleHttp generic error
            echo 'Got an http error:',$e->getMessage();
        }

    }
}
