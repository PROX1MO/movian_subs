<?php
include '7z.php';
include 'html_dom.php';
include 'subs.php';
include 'bgsubs.php';

$bgsubs = new bgsubs;

//file_put_contents("/path/kor", print_r($_SERVER, true), FILE_APPEND);

//if (!isset($_SERVER['HTTP_USER_AGENT']) || ! preg_match('/Movian.*\d\.\d\.\d/', $_SERVER['HTTP_USER_AGENT']))
//	die;

$_POST['p'] = 'unacs';
$_POST['t'] = "saving private ryan";
#$_GET['file'] = 'Venom+%282005%29';
#$_GET['loadSubs'] = 'http://subs.sab.bz/index.php?act=download&attach_id=25549&file=Veno';



if (!empty($_GET['loadSubs']) && !empty($_GET['file']))
{
	$url = $_GET['loadSubs'];
	$filename = urldecode($_GET['file']);

	if (filter_var($url, FILTER_VALIDATE_URL))
	{
		echo "OKOK\n";
		echo "loadSUbs\n";
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

	$title = urldecode($title);
	$title = str_replace(
		array('.', ':', '-', '_', '(', ')'), ' ',
		$title);
	if (preg_match('/(.*)\[.*\](.*)/', $title, $matches))
	{
		$title = $matches[1].$matches[2];
	}
	//is series?
	//echo $title;
	//Beecham House 01x06
	if (preg_match('/(.*)\ss?(\d+)[ex](\d+)/i', $title, $matches))
//	if (preg_match('/(.*).?S(\d+)E(\d+)/', $title, $matches))
	{
		$title = $matches[1];
		$season = $matches[2];
		$episode = $matches[3];	
	}

	if (!empty($season) && !empty($episode))
	{

		$series_variants = array("S%02dE%02d", "%02dx%02d", "%02d%02d", "%02d %02d");
		foreach($series_variants as $format)
		{
			$series_suffix = sprintf($format, $season, $episode);
			echo "Search '$title $series_suffix'\n";
			$subs = $bgsubs->searchSubs($title.' '.$series_suffix);
			if($subs != '[]')
				break;
		}

	}
	else
	{
//		echo "ELSE $title\n";
		$subs = $bgsubs->searchSubs($title);
	}

	//file_put_contents('/path/file.txt', "$provider - ".print_r($subs,true)."\n", FILE_APPEND);
	echo $subs;

	//DEBUG
	//file_put_contents('/path/temp', "$plugin,$provider,$title,$season,$episode\n", FILE_APPEND);
	//file_put_contents('/path/temp', print_r(json_decode($subs), TRUE), FILE_APPEND);
}
?>
