<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PeriodicityCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:periodicity:update')
            ->setDescription('Run all periodicity event')
            ->addOption('--batch-limit', '-l', InputOption::VALUE_OPTIONAL, 'Set batch size of leads to process per round. Defaults to 300.', 300)
//             ->addOption(
//                 '--max-leads',
//                 '-m',
//                 InputOption::VALUE_OPTIONAL,
//                 'Send feeds',
//                 false
//             )
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $factory    = $container->get('mautic.factory');
        $translator = $factory->getTranslator();
        $em         = $factory->getEntityManager();
		$output->writeln('<info>Execute periodicity event </info>');
		$periodicityModel = $factory->getModel('periodicity');
// 		$periodicitys = $em->getRepository('\Periodicity');

        var_dump($periodicitys);
        die('');
		foreach($feeds as $f){
			$schduleDay = $f->getScheduleDay();
			$runCron = false;
			foreach($schduleDay as $day){
				switch($day){
					case "daily":
						$runCron = true;
					break;
					case "weekly":
						$day = date('w');
						//$week_start = date('m-d-Y', strtotime('-'.$day.' days'));
						$week_end = date('j', strtotime('+'.(6-$day).' days'));
						if($week_end ==  date('j')){
							$runCron = true;
						}
					break;
					case "monthly":
						if(gmdate('t') == gmdate('d')){
							$runCron = true;
						}
					break;
					case "sunday":
						if(date('D') === 'Sun') {
							$runCron = true;
						}

					break;
					case "monday":
						if(date('D') === 'Mon') {
							$runCron = true;
						}
					break;
					case "tuesday":
						if(date('D') === 'Tue') {
							$runCron = true;
						}
					break;
					case "wednesday":
						if(date('D') === 'Wed') {
							$runCron = true;

						}
					break;
					case "thursday":
						if(date('D') === 'Thu') {
							$runCron = true;
						}
					break;
					case "friday":
						if(date('D') === 'Fri') {
							$runCron = true;

						}
					break;
					case "saturday":
						if(date('D') === 'Sat') {
							$runCron = true;

						}
					break;
					case "ist_of_the_month":
						if(date('j') == 1){
							$runCron = true;
						}
					break;
					case "15th_of_the_month":
						if(date('j') == 15){
							$runCron = true;
						}
					break;
				}

			}
			if($f->getStatus() == 'active' && $runCron == true){
				$selectFeedType = $f->getSendFeedType();
				$lastXXNo =  $f->getSendFeed();

				$feedsData = $em->getRepository('FeedManBundle:FeedData')->findBy(
					array('feed_id' => $f->getId(),'status'=>0)
				);
				// get comma separated list ids
				$feedLeadList = $f->getLeadListId();
				$feedLeadListArray = explode(',',$feedLeadList);
				foreach($feedLeadListArray as $feedLeadListId){
					// get emails in lead list
					$leadSmartList = $listModel->getEntity($feedLeadListId);
					$leadListsO = $listModel->getLeadsByList($leadSmartList);
					$headers = "MIME-Version: 1.0" . "\r\n";
					$headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
					$headers .= 'From: ' . $from . "\r\n";
					$headers .= 'Reply-To: ' .$from . "\r\n";
					$headers .= 'X-Mailer: PHP/' . phpversion();
					$to = "contact@pelbox.com";

					$leadLists = array();
					foreach($leadListsO as $leadList){
						if(count($leadList) > 0){
							$leadLists[]=   $leadList[0];

						}
					}


					if(count($leadLists) > 0){
						$leadListEmails = array();
						foreach($leadLists as $leadL){
							$email = $leadL['email'];
							$leadListEmails[] =$email;
						}

						$emailFeeds = array();
						switch($selectFeedType){
							case 'all':
								$emailFeeds = $feedsData;
							break;
							case 'last_xx':
								for($i=0; $i < $lastXXNo; $i++){
									$emailFeeds[] = $feedsData[$i];
								}
							break;
							case 'manually_select':
								$selectedFeeds = explode(",",$lastXXNo);
								foreach($feedsData as $ff){
									if(in_array($ff->getId(),$selectedFeeds)){
										$emailFeeds[] = $ff;

									}
								}

							break;
							unset($emailF);
						}
					}
				//$em->detach($f);
					unset($f);
				}
				//change status of the sent feeds
				foreach($feedsData as $changeStatusFeedData){
					$changeStatusFeedData->setStatus(1);
					$em->flush();
				}
			}
			if(count($emailFeeds) > 0 ){
				// the message
				$from='gagandeep@pelbox.com';
				$msg = "Following are the feeds updates.";
				$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
				$headers .= 'From: ' . $from . "\r\n";
				$headers .= 'Reply-To: ' .$from . "\r\n";
				$headers .= 'X-Mailer: PHP/' . phpversion();
				$msg.= "<ul>";
				foreach($emailFeeds as $emF){
					$msg.='<li><div><a href="'.$emF->getLink().'">'.$emF->getTitle().'</a></div><div>'.$emF->getDescription().'</div></li>';
				}
				$msg.='</ul>';
				foreach($leadListEmails as $lEmail ){
					$to ='contact@pelbox.com';
					mail($to,"Feeds example",$msg,$headers);
				}
			}
		}
		unset($feeds);



        $this->completeRun();

        return 0;
    }
}