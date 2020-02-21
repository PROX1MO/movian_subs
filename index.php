<?php
include '7zArchive.php';
include 'html_dom.php';
include 'subs.php';
include 'bgsubs.php';

$bgsubs = new bgsubs;

if (!isset($_SERVER['HTTP_USER_AGENT']) || ! preg_match('/Movian.*\d\.\d\.\d/', $_SERVER['HTTP_USER_AGENT'])) {
		die('<html><head><title>404 Not Found</title></head><body bgcolor="white"><center><h1>404 Not Found</h1></center><hr><center>nginx/1.6.2</center></body></html>');
}

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
		die('Error 403\n');

	$bgsubs->setProvider($provider);

	$title = urldecode($title);
	if (preg_match('/(.*) .??(\d+)[ex](\d+)/i', $title, $matches))
	{
		$title = $matches[1];
		$season = $matches[2];
		$episode = $matches[3];
	}

	if (!empty($season) && !empty($episode))
	{
		$series_variants = array("S%02dE%02d", "%02dx%02d"); //, "%02d %02d", "%02d%02d"
		foreach($series_variants as $format)
		{
			$series_suffix = sprintf($format, $season, $episode);
//			$subs = $bgsubs->searchSubs("$title $series_suffix"); old
			$subs = $bgsubs->searchSubs($title.' '.$series_suffix);
			if($subs != '[]')
				break;
		}

		$title = str_replace("'", '', $title);
		$title = str_replace("\"", '', $title);
/*
		if($subs == '[]' && (preg_match('/(^the) (.*)$/i', $title) //test
		if($subs == '[]' && str_word_count($title) > 1)//try to remove the first word //better with (^the)/i
		{
			$title = urldecode($title);
			$title = substr(strstr($title," "), 1);
			$series_variants = array("S%02dE%02d", "%02dx%02d"); //, "%02d %02d", "%02d%02d",
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
*/
	}
	else
	{

		$subs = $bgsubs->searchSubs($title);
	}

	echo $subs;
}
?>
