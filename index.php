<?php
if (!isset($_SERVER['HTTP_USER_AGENT']) || !preg_match('/Movian.*\d\.\d\.\d/', $_SERVER['HTTP_USER_AGENT'])) {
	die('<html><head><title>404 Not Found</title></head><body bgcolor="white"><center><h1>404 Not Found</h1></center><hr><center>nginx/1.14.2</center></body></html>');
}

include '7z.php';
include 'html_dom.php';
include 'subs.php';
include 'bgsubs.php';

$bgsubs = new bgsubs;

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
	$plugin = isset($_POST['plugin']) ? $_POST['plugin'] : '';	//Not used. This is the plugin name 'bgsubs'
	$provider = isset($_POST['p']) ? $_POST['p'] : '';
	$title = isset($_POST['t']) ? $_POST['t'] : '';
//	$year = isset($_POST['y']) ? $_POST['y'] : '';	//test
	$season = isset($_POST['s']) ? $_POST['s'] : '';	//only if metadata is enabled for Series
	$episode = isset($_POST['e']) ? $_POST['e'] : '';

	if (!empty($_POST['s'])&&($_POST['e']))	//if metadata is enabled concatinate title season and episode
	{
		$title .= " ".$season."x".$episode;
	}


	if (empty($provider))
		die('<html><head><title>404 Not Found</title></head><body bgcolor="white"><center><h1>404 Not Found</h1></center><hr><center>nginx/1.14.2</center></body></html>');

	$bgsubs->setProvider($provider);

	$title = preg_replace(array('/^(?:18)?\+/'), '', $title);	//removes 18+ before the title
	$title = preg_replace('/\s+(?=\s)/', '$1', str_replace(
		array('.', ',', ':', '…', '+', '-', '=', '_', '(', ')', '?', '!', '"'), ' ',	//added +
		urldecode($title)));	//removes unnecessary and trim spaces
	$title = html_entity_decode(preg_replace('/\%u([0-9A-F]{4})/', '&#x\\1;', $title), ENT_NOQUOTES, 'utf-8');	//makes cyrllic readable
	$title = ucwords($title);	//Uppercase the first character of each word
	$title = preg_replace('/^(?:ep?|episode|еп?|епизод)?\s?(\d{1,2})\s(.*)\s(?:se?|season|се?|сезон)\s?(\d{1,2})(?=(?!\s?(?:x|ep?|episode|еп?|епизод|х)\s?\d{1,2}|\d))/ui', '$2 $3x$1', $title); //02 Name S01$ = Name - S01E02

	//$title = preg_replace('/(.*)\s[xh]26[45]/i', '$1', $title);	//not to mess with series
	//drops requests containing…
	$keywords = array('(?:BG|DUALoff)\s?(?:Audio|Subs?)', 'Bulgarian', '^(?:Sample|Scene\s\d+)$', '(?:^|\s)BG(?=\s|$)', 'UFC', 'WWE', '^F1', '^VA\s', 'BNT(?=\s|$)', '^(?:480|576|720|1080|2160|4k|SPF\s\d+)$', '^\d+x\d+$', '^BM\d{4}\s', '^(?:(?!Superintelligence|Houseofthedragon|Lotrringsofpower)\b\w{16,})', '(?!.*(?:^xXx$|^xXx\s?2|Xander))xxx', '(?!.*(?:^8mm$|^8mm\s?2))8mm', '(?!.*(?:^POV$|POV (?:1990|2015)))POV', '(?!.*(?:Kamasutra))Erotic', '(?<=^|\s)\d{2}\s\d{2}\s\d{2,}(?=\s|$)', '(?<!bad\s)(?<=^|\s)ASS(?=\s|$)', '[a-z]+\d{5,}|\d{5,}[a-z]+|(?<!s\d{2}e\d{2} )\d{6,}', '(?<=^|\s)(?:dp|bj|anal(?:ized)?|cocks?|dicks?)(?=\s|$)', 'bla?cke?d|go\sblack', 'Woodman\s?Casting', 'creampie', 'squirt', 'sodom', 'milf', 'pussy', 'cum', 'cunt', 'nipple', 'breast', 'tits', 'horny', 'hottie', 'fuck', 'sperma', '(?<=^|\s)lick', 'amateur', '(?:3|three)some', 'gang\s?bang', 'orgasm', 'porn', '(?:hand|blow)\s?job', 'suck(?:s|ing|ed)', 'slut', 'bbw', 'bdsm', 'deep\s?throat', 'ejacuation', 'fetish', 'gonzo', 'lesbian', 'org(?:y|ie)', 'Onlyfans', '(?:hard|soft)core', 'seduce', 'masturbat', 'shemale', '(?:bi|trans)\s?sexual', 'sex\s?tape', 'Brazzers', 'Bangbros', 'Public(?:Agent|PickUps)', 'MomsInControl', 'Clips4Sale', 'BigNaturals', 'BaDoinkVR', 'Puremature', 'Foreplay', 'Fornication', 'Foxy', 'Public\s?Sex'); //'(?:^|\s)Poke(?=\s|$)', 
	foreach($keywords as $keyword)
	{
		if (preg_match("/$keyword/i", $title))
			die;
	}

	if (preg_match_all('/(?!^)(?<!\d)((?:19[2-9]|20[0-2])\d)(?=\s|\]|\}|$)/', $title, $matches, PREG_SET_ORDER))	//set last match as year variable and passes it to providers for better results	//(?<=\s) also works //(?<!\d) is not to treat 12020 as a year
	{
		$_POST['year'] = end($matches)[0];
	}//year in movie title is removed in subs.php for series is removed below.

	if (empty($_POST['year']) && preg_match('/^Play$/i', $title))	//drop if title = "Play" without year
	{
		die;
	}

	if (preg_match('/(.*)\s(\d{1,2})\s?of\s?\d{1,2}(?=\s|$)/i', $title, $matches))	//if Title 03of10, then name = Title S01E03
	{	//old (.*)\s(\d{1,2})\s?of\s?\d{1,2}(?=\s|$)
		$title = $matches[1] . ' S01E' . $matches[2];
	}

	if (preg_match('/(.*\s(?:se?|season|се?|сезон)\s?\d{1,2})(?:\s|$)(?!(?:x|ep?|episode|еп?|епизод|х)\s?(\d{1,2})).*/ui', $title, $matches))	//if (Title S01) text, add E99 = Title S01E99 then search for entire season
	{
		$title = $matches[1] . 'E99';
	}

	//Some cases for series like "NCIS 610"
	if (preg_match("/(The Mentalist|NCIS|Sons Of Anarchy|The Big Bang Theory|Modern Family|Kuhnya|McLeod's Daughters|Houseofthedragon|Tlotr|Lotrringsofpower|Andor|Monk|Willow|TheLastOfUs|Malcolm In The Middle)\s*-*\s*(\d{1,2})\s*(\d{2})(?=\s|$)/i", $title, $matches))
	{
		$title = $matches[1] . ' ' . $matches[2] . 'x' . $matches[3];
	}

	if (preg_match('/(.*)\s(?<!s\d\s|s\d\d\s|se\d\s|se\d\d\s)(?:ep?|episode|еп?|епизод)(\d+)(?=\s|$)/ui', $title, $matches))	//if Title E03, then name = Title S01E03 & loop | skip if SE in front of EP
	{
		$title = $matches[1] . ' S01E' . $matches[2];
	}
	
//	serie: //'/([\s\S]*?)\s(?:Part|Video|Scene|us|au|za|(?:(?!^)(?<!\d)(?:19[2-9]|20[0-2])\d(?=\s|\]|\}|$)))?\s?(?:|se?|season|се?|сезон)\s?(\d{1,2})\s?(?:x|ep?|episode|еп?|епизод|х)\s?(\d{1,2})(?=\s|$)/ui'
	if (preg_match('/([\s\S]*?)\s(?:Part|Video|Scene|uk|au|za|nz)?\s?(?:(?!^)(?<!\d)(?:19[2-9]|20[0-2])\d(?=\s|\]|\}|$))?\s?(?:Part|Video|Scene|uk|au|za|nz)?\s?(?:|se?|season|се?|сезон)\s?(\d{1,2})\s?(?:x|ep?|episode|еп?|епизод|х)\s?(\d{1,2})(?=\s|$)/ui', $title, $matches))	//checks if it's a serie; if (Name S) 01 E 01...$1 - bug fixed below - not neeeded already fixed above
	//endings uk, au ... are removed; US is special ending removed in subs.php
	{
		$title = $matches[1];
		$season = $matches[2];
		$episode = $matches[3];
//		$title = preg_replace(array('/(.*)(?!^)(?<!\d)(?:19[2-9]|20[0-2])\d(?=\s|\]|\}|$)/', '/(.*)\s(?:se?|season|се?|сезон|Part|Video|Scene)$/ui', '/(.*)\sus$/i', '/(.*)\sza$/i'), '$1', $title);	//removes year & ednings on S & SE from serie title //old \s instead of (?<!\d) for year, fixed in subs.php too
		$title = trim($title);	//removes all spaces
	}
/*
	if (preg_match('/(.*)\s(?:ep?|episode|еп?|епизод)(\d+)(?=\s|$)/ui', $title, $matches))	//if Title E03, then name = Title S01E03 & loop
	{
		$title = $matches[1] . ' S01E' . $matches[2];
		goto serie;
	}
*/
	if (!empty($season) && !empty($episode))	//if a serie, then make a search pattern: S01E01 & 01x01
	{
		$series_variants = array('S%02dE%02d', '%02dx%02d');
		foreach($series_variants as $format)
		{
			$series_suffix = sprintf($format, $season, $episode);
			$subs = $bgsubs->searchSubs($title . ' - ' . $series_suffix);
			if($subs != '[]')
				break;
		}

//		$title = str_replace(
//			array('\'', '"'), '',
//			$title);	//only " is removed above, ' stays
	}
	else
	{
//		$title = preg_replace('/(.*)(?!^)\s[12]\d{3}\s*/i', '$1', $title);	//if a movie trim all after year (year *) //moved in subs RS
//		$title = preg_replace('/(.*)\sepisode\s?\d{1,2}\s.*/i', '$1', $title);	//if Title Episode XX text, returns title only
		if (preg_match('/(.*)\sA\s?K\s?A\s(.*)/i', $title, $matches))	//checks if the title contains A.K.A. and split the name (Only for Movies!)
		{
			$variants = array($matches[1], $matches[2]);
			foreach($variants as $title)
			{
				$subs = $bgsubs->searchSubs($title);
			}
		}
		else
		{
			$subs = $bgsubs->searchSubs($title);
		}
	}

	header('Content-Type: application/json');
	echo $subs;

}
?>
