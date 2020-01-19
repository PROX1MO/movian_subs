<?php
include 'html_dom.php';
include 'subs.php';
include 'bgsubs.php';

$bgsubs = new bgsubs;

if (!isset($_SERVER['HTTP_USER_AGENT']) || ! preg_match('/Movian.*\d\.\d\.\d/', $_SERVER['HTTP_USER_AGENT']))
	die;


if (!empty($_GET['loadSubs']) && !empty($_GET['file']))
{
	$url = $_GET['loadSubs'];
	$filename = urldecode($_GET['file']);

	if (filter_var($url, FILTER_VALIDATE_URL))
	{
		echo $bgsubs->loadSubs($url, $filename);
	}
}
else
{
	$plugin = isset($_POST['plugin']) ? $_POST['plugin'] : '';
	$provider = isset($_POST['p']) ? $_POST['p'] : '';
	$title = isset($_POST['t']) ? $_POST['t'] : '';
	$season = isset($_POST['s']) ? $_POST['s'] : '';
	$episode = isset($_POST['e']) ? $_POST['e'] : '';

	if(empty($provider))
		die("Error 403\n");

	$bgsubs->setProvider($provider);
	//echo $title;
	//file_put_contents("/path/debug.log", "$provider -> $title -> $season -> $episonde\n", FILE_APPEND);
	//is series?
	if (preg_match('/(.*) .??(\d+).?(\d+)/', $title, $matches))
//	if (preg_match('/(.*).?S(\d+)E(\d+)/', $title, $matches))
	{
		$title = $matches[1];
		$season = $matches[2];
		$episode = $matches[3];	
	}

	if (!empty($season) && !empty($episode))
	{
		$series_variants = array("S%02dE%02d", "%02dx%02d", "%02d %02d"); //, "%02d%02d"
		foreach($series_variants as $format)
		{
			$series_suffix = sprintf($format, $season, $episode);
//			$subs = $bgsubs->searchSubs("$title $series_suffix");
			$subs = $bgsubs->searchSubs($title.' '.$series_suffix);
			if($subs != '[]')
				break;
		}

		$title = str_replace("'", '', $title);
		$title = str_replace("\"", '', $title);	
		if($subs == '[]' && str_word_count($title) > 1)//try to remove the first word
		{
			$title = urldecode($title);
			$title = substr(strstr($title," "), 1);
			$series_variants = array("S%02dE%02d", "%02dx%02d", "%02d %02d"); // "%02d%02d",
			foreach($series_variants as $format)
			{
				$series_suffix = sprintf($format, $season, $episode);
				#echo "DEBUG: SEARCH FOR SUBS $title$series_suffix";
//				$subs = $bgsubs->searchSubs("$title $series_suffix");
				$subs = $bgsubs->searchSubs($title.' '.$series_suffix);
				if($subs != '[]')
					break;
			}

		}

	}
	else
	{

		$subs = $bgsubs->searchSubs($title);
	}

	echo $subs;

	//DEBUG
	//file_put_contents('/path/requests.log', "$plugin,$provider,$title,$season,$episode\n", FILE_APPEND);
	//file_put_contents('/path/response.log', print_r(json_decode($subs), TRUE), FILE_APPEND);
}
?>
