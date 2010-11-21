<?php 
// ======================
// Author: Robert Banh
// 
// cron job: /usr/local/php5/bin/php -f /home/robertbanh/cron/cron_check_new_followers.php
// ================

set_time_limit(0);
$currTime = date("F j, Y, g:i:sa");
$currTime = date("F j, Y, g:i:sa", strtotime($currTime . " +2 hours"));
echo "========================\r\n";
echo "Cron: check new followers\r\n";
echo "Job started: $currTime\r\n";
echo "========================\r\n";

// =======================================
// load lib
// =======================================
$path = '/home/robertbanh/cron/';
if ($_SERVER['SERVER_NAME'] == 'localhost')
	$path = '';
require_once("$path"."Twitter.php");
require_once("$path"."db.php");
require_once("$path"."dbFacade.php");
require_once("$path"."definition.php");


// =======================================
// parm 
// =======================================
$tusername = 'MrMilestone';
$tpasswd = 'xxxxx';
$tid = '18845303'; // mr. milestone id

$omitArray = array('MrTweet', 'any_other_user');

$debug = false;
$total_twitter_request = 0;

// =======================================
// twitter time!
// =======================================
$twitter = new Arc90_Service_Twitter($tusername, $tpasswd);

try
{
	// =======================================
	// fetch mr.milestone's api request limit
	// =======================================
	$response = $twitter->checkRateLimit('xml');
	$xml = @simplexml_load_string($response->getData());
	$remaining = $xml->{'remaining-hits'};
	echo "Rate Limit (start): $remaining \r\n";
	
	// =======================================
	// fetch mr.milestone's stats
	// =======================================
	$response = $twitter->showUser($tusername, 'xml');
	$total_twitter_request++;
	$xml = @simplexml_load_string($response->getData());
	
	// =======================================
	// If Twitter returned an error (401, 503, etc), print status code
	// =======================================
	if($response->isError())
	{
		$ec = $response->http_code;
		$xml = @simplexml_load_string($response->getData());
		$err = $xml->error;
		if ($ec == '401')
			die("1 Error: Login failed. Incorrect username/password. http_code = $ec ($err)\r\n");
		else
			die("1 Error: Twitter fail whale!! http_code = $ec ($err)\r\n");
	}
	
	// =======================================
	// fetch number of friends and followers
	// =======================================
	$friends_count = $xml->friends_count;
	echo "friends_count = $friends_count \r\n";
	
	$followers_count = $xml->followers_count;
	//$totalPages = ceil($followers_count/100);
	echo "followers_count = $followers_count \r\n";
	
	if (intval($friends_count) != intval($followers_count))
		echo "Check 1.......... FAIL! RUN THE REMOVAL CRON JOB!! \r\n\r\n";
	else
		echo "Check 1.......... PASSED. \r\n\r\n";
	
	// =======================================
	// mysql events
	// =======================================
	$dbFacade = new dbFacade();
	
	// =======================================
	// fetch all followers
	// =======================================
	$listOfFollowers = array();
	$stopFetch = false;
	
	$totalProcessed = 0;
	
	// safety check
	$safe_stop = 0;
	
	$i = '-1';
	//for ($i=$startPage; $i<=$totalPages; $i++)
	while ($startPage != '0' || $startPage != 0)
	{
		$safe_stop++;
		if ($safe_stop > 500)
			die('\r\n SAFETY STOP ACTIVATED!! \r\n');
	
		$listOfFollowers = array();
		
		//if ($stopFetch === true)
		//	break;
		
		echo "\r\n## fetching page $safe_stop \r\n";
		
		$response = $twitter->getFollowers('xml', $i);
		$total_twitter_request++;
		$xml = @simplexml_load_string($response->getData()) or die ("Error: code 1 - fetching followers!\r\n");
		foreach ($xml->users as $user) 
        {
			foreach ($user->user as $u) 
            {
				$sn = $u->screen_name;
				$fc = $u->followers_count;
				$created = $u->status->created_at; // 'Sat Jan 10 22:51:16 +0000 2009'
				$listOfFollowers[] = array(
					'screen_name' => $sn,
					'followers_count' => $fc
					);
			}
		}
		
		$startPage = $xml->next_cursor;
		$i = $startPage;
		
		echo "## Total Users for this page (".count($listOfFollowers).") \r\n";
		$totalProcessed += count($listOfFollowers);
		echo "## Total processed (".$totalProcessed.") \r\n";
		
		// =======================================
		// add any new followers
		// =======================================
		foreach ($listOfFollowers as $follower)
		{
			$sn = $follower['screen_name'];
			$fc = $follower['followers_count'];
			
			$userId = $dbFacade->getUserId($sn);
			if ($userId === false && (!in_array($sn, $omitArray)))
			{
				// =======================================
				// follow the new user
				// =======================================
				if ($debug === false) 
                {
					$response = $twitter->createFriendship($sn, 'xml');
					$total_twitter_request++;
				}
				
				if(!$response->isError())
				{
					// create welcome msg
					$msg = "@$sn HellooOoo! Your acct is now active. Please spread the word! http://theTwitterTagProject.com/m";
					// just in case
					if (strlen($msg) > 140) 
						$msg = "@$sn HellooOoo! Your acct is now active. http://theTwitterTagProject.com/m";
					// =======================================
					// and send welcome msg
					// =======================================
					//if ($debug === false) {
					//	$response = $twitter->updateStatus($msg, 'xml');
					//	$total_twitter_request++;
					//}
						
					// =======================================
					// insert the new user
					// =======================================
					//if(!$response->isError())
					//{
						$dbFacade->addNewUser($sn, $fc);
						echo "New user $sn ($fc) - added into db!\r\n";
					//}
				}
				else
				{
					$ec = $response->http_code;
					//var_export($response);
					$xml = @simplexml_load_string($response->getData());
					$err = $xml->error;
					if ($ec == '401')
						echo ("2 Error: Login failed. Incorrect username/password. http_code = $ec ($err)\r\n");
					else if($err == "Could not follow user: $sn is already on your list.")
					{
						$dbFacade->addNewUser($sn, $fc);
						echo "New user $sn ($fc) - added into db!\r\n";
					}
					else
						echo ("2 Error: Twitter fail when creating friendship ($sn)!! http_code = $ec ($err)\r\n");
				}
			}
			else if (!in_array($sn, $omitArray))
			{
				// =======================================
				// else since we already got the information, let's handle the follower count
				// =======================================
				$result = $dbFacade->getFollowersInfo($userId);
				
				$db_total = $result[0]['total'];
				$db_target = $result[0]['target'];
				$db_lastTarget = $result[0]['lastTarget'];
				$db_reminder = $result[0]['reminder'];
				
				// sometimes it goes crazy
				if ($db_target == '')
					echo "**Problem with user ($sn) ** \r\n";
				
				// =======================================
				// *check in case the target value just hit
				// =======================================
				if (intval($fc) >= intval($db_target))
				{
					// =======================================
					// send twitter msg
					// =======================================
					$msg = "@$sn Congrats! You just reached $db_target followers!! Spread the word! http://bit.ly/roDP";
					if ($debug === false && $db_target != '') {
						$response = $twitter->updateStatus($msg, 'xml'); // this is reply
					//	$response = $twitter->sendMessage($sn, $msg, 'xml'); // this is DM
						$total_twitter_request++;
					}
					// =======================================
					// update the followers table
					// =======================================
					if(!$response->isError() && $db_target != '')
					{
						$dbFacade->updateFollowersCount($userId, $fc, 1, 1, $db_target);
						echo "Congrats $sn hit $db_target ($fc) - congrats sent and db updated. \r\n";
					}
				} 
				// =======================================
				// *check to see if reminder threshold just hit
				// =======================================
				else if ($db_reminder == '1' &&
						(intval($fc) > intval(intval($db_target)-10)))
				{
					// =======================================
					// send reminder
					// =======================================
					$msg = "@$sn Oooo, you are close to $db_target followers!! Spread the word! http://bit.ly/roDP";
					if ($debug === false && $db_target != '') {
						$response = $twitter->updateStatus($msg, 'xml');
						//$response = $twitter->sendMessage($sn, $msg, 'xml');
						$total_twitter_request++;
					}
					// =======================================
					// update the followers table
					// =======================================
					if(!$response->isError() && $db_target != '')
					{
						$dbFacade->updateFollowersCount($userId, $fc, 0, 1);
						echo "Reminder $sn near $db_target ($fc) - reminder sent and db updated. \r\n";
					}
				}
				// =======================================
				// else we just update the followers count and check the lastTarget just in case the user lost followers
				// =======================================
				else if ((intval($fc) <= intval($db_target)) && 
						 (intval($fc) > intval($db_lastTarget))) // in case the user lost followers, we don't want to congrats them twice
				{
					// =======================================
					$dbFacade->updateFollowersCount($userId, $fc);
					echo "Update $sn count $fc in db! \r\n";
				}
				else 
				{
					echo "No update to $sn since the (current count) $fc < (db_lastTarget) $db_lastTarget (regress)\r\n";
				}
				
			}
		}
		
	}
	
}
catch(Arc90_Service_Twitter_Exception $e)
{
	// Print the exception message (invalid parameter, etc)
	die("Error: " . $e->getMessage());
}


// =======================================
$currTime = date("F j, Y, g:i:sa");
$currTime = date("F j, Y, g:i:sa", strtotime($currTime . " +2 hours"));
echo "\r\n========================\r\n";
echo "Job finished: $currTime\r\n";

// =======================================
// fetch mr.milestone's api request limit
// =======================================
$response = $twitter->checkRateLimit('xml');
$xml = @simplexml_load_string($response->getData());
$remaining = $xml->{'remaining-hits'};
echo "Rate Limit (end): $remaining \r\n";
echo "total_twitter_request: $total_twitter_request\r\n";
echo "========================\r\n\r\n";
?>