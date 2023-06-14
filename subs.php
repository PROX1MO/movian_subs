<?php
class subs
{
	protected $cache_dir = '/mnt/tmp/cache_files/';
	protected $useragent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36';
	protected $subs;
	protected $provider;

	public function setProvider($provider)
	{
		$this->provider = $provider;
	}

	public function searchSubs($title)
	{
		return false;
	}

	protected function getTitle($title)	//digests the title
	{
		$title = preg_replace(array('/^\[.*\]\s?(.*)/', '/^IMAX\s?(.*)/i'), '$1', $title);	//remove starting with PreFix, because they're used as Suffix too
//'\se(?:p(?:isode)?)?\s?\d{1,2}(?=\s|$)',  removed 
		$remove_suffixes = array('(?!^)(?<!\d)(?:19[2-9]|20[0-2])\d(?=\s|\]|\}|$)', '\s\d{3,4}[piр]', '\s(?:480|576|720|1080|2160|4K)$', '\d{4}[xх]\d{3,4}', 'TrueHD', '\s[xh]26[45](?=\s|$)', '\s(?:(?:F(?:ULL\s?)?|U)?[HS]U?D|B[DR]|T[CSV]|WEB|Cam|SAT)(?=\s|$|Rip|TV)', '\s(?:DC|Pk|HC|NF|[24]k|Rip|SCR|PAL)(?=\s|$)', 'AMZN', '3D', 'DV[BD]', 'DivX', 'XviD', 'Blue?\s?Ray', 'Li?MiTED', 'iNTERNAL', 'PROPER', 'PEPACK', 'Remastered', 'EXTENDED', 'UNRATED', 'RE(?:MUX|FLUX)', 'AC3', 'AAC', 'DTS', 'Atmos', 'HEVC', 'SECAM', 'NTSC', 'IMAX', 'Hindi', 'IsUsPM', 'DSNP', '\[|\{', '\s\w+\s?Audio\s?$', '\sMULTI\s?$', '\sNew\s?$', 'Bengali\s?$', 'Danish\s?$', 'Turkish\s?$', 'Spanish\s?$', 'French\s?$', 'Finnish\s?$', 'German\s?$', 'Greek\s?$', 'Italian\s?$', 'Norwegian\s?$', 'Korean\s?$', 'Canadian\s?$', 'Czech\s?$', 'Chinese\s?$', 'Persian\s?$', 'Lithuanian\s?$', 'Latino\s?$', '\s(?:Part|Scene|Video)\s?\d+\s?$', '\s\S{2}\/\S{4,}\s?$');	//(?!^)(?<=\s) can be replaced with \s; '\s\d+\s?of\s?\d+' is done in index.php
		foreach(range(1, 3) as $i) {	//for ($i=0; $i<3; $i++) {
			foreach($remove_suffixes as $rs)
			{
				$title = preg_replace("/(.*)$rs.*/i", '$1', $title);	//removes suffixes and loop just in case
			}
		}

		//exceptions - removes and replaces in a title -> (.*S100E100) sth; ^S01E01 (tittle); ^S01|^Ep01|^episode 01 (title); ^(title) 12 Ep etc
		$title = preg_replace(array('/(.*\s(?:(?:se?\s?\d+\s?|season\s?\d+\s?|се?\s?\d+\s?|сезон\s?\d+\s?)(?:ep?\s?\d+\s?|episode\s?\d+\s?|еп?\s?\d+\s?|епизод\s?\d+\s?)|(?:\d+\s?(?:x|х)\s?\d+\s?)))(?=\s|$)/ui', '/^(?:(?:|se?|season|се?|сезон)\s?\d{1,3}\s?(?:x|ep?|episode|еп?|епизод|х)\s?\d{1,3}|(?:se?|season|се?|сезон)\s?\d{1,3}|(?:x|ep?|episode|еп?|епизод|х)\s?\d{1,3})\s(.*)$/ui', '/^Rflx\s?(.*)/i', '/^(?:Part|Scene|Video)\s?\d+\s(.*)/i', '/(.*)\s\d+\s?(?:ep|episode|еп|епизод)$/ui'), '$1', $title);
		//old 2nd regex ^(?:(?:se?\s?\d+\s?|season\s?\d+\s?|се?\s?\d+\s?|сезон\s?\d+\s?)(?:ep?\s?\d+\s?|episode\s?\d+\s?|еп?\s?\d+\s?|епизод\s?\d+\s?)|(?:\d+\s?(?:x|х)\s?\d+\s?))\s(.*)$ -> S01x01 was not selected, now it does, but such case should not exist

		//deletes from resulting title
		$title = preg_replace(array('/^\s?download\s?$/i', '/5rFF/i', '/^tmsf\s/i', "/Frank Herbert's/i", '/~/', '/PBS/i', '/Cartoon Parody\s?/', '/^VTS\s?\d+(?:\s\d+)?/i', '/^VIDEO$/i', '/^Tv$/i', '/CD[\s\#]?\d+/i', '/p2p\s?(?:bg)?/i', '/Special\sEdition]?/i', '/^(?:Серия|Епизод|Serie|Episode|Se?|Ep?|Се?|Еп?|Scene|Vid|ST)\s?\d+$/ui'), '', $title);

		if (preg_match_all('/((?:19[2-9]|20[0-2])\d)(?=\s|$)/', $title, $matches, PREG_SET_ORDER))	//set last match as year variable and passes it to providers for better results
			{
				$_POST['year'] = end($matches)[0];
			}
		//other replaces in title
		$title = preg_replace("/%u0406/i", "I", $title);
//		$title = preg_replace("/(?!^)(?<!\d)((?:19[2-9]|20[0-2])\d)(?=\s|$)\s(.*)/i", "$2", $title);	//Year Name = Name
		$title = preg_replace("/(Frozen) II/i", "$1 2", $title);
		$title = preg_replace("/\s+ii$/i", " 2", $title);
		$title = preg_replace("/\s+iii$/i", " 3", $title);
		$title = preg_replace("/\s+iv$/i", " 4", $title);
		$title = preg_replace("/\s+vi$/i", " 6", $title);
		$title = preg_replace("/\s+vii$/i", " 7", $title);
		$title = preg_replace("/\s+viii$/i", " 8", $title);
		$title = preg_replace("/\s+ix$/i", " 9", $title);
		$title = preg_replace("/기생충/i", "Parasite", $title);
		$title = preg_replace("/葉問/i", "Ip man ", $title);
		$title = preg_replace("/黃金兄弟/i", "Golden job", $title);
		$title = preg_replace("/神探蒲松齡/i", "The Knight of Shadows", $title);
		$title = preg_replace("/犯罪现场/i", "A Witness Out of the Blue", $title);
		$title = preg_replace("/诛仙 Ⅰ/i", "The Legend of Chusen", $title);
		$title = preg_replace("/감기/i", "Cold", $title);
		$title = preg_replace("/غار/i", "Cave", $title);
		$title = preg_replace("/Æon Flux/i", "Aeon Flux", $title);
		$title = preg_replace("/Vtorzhienie|Wha2ion/i", "Vtorzhenie", $title);
		$title = preg_replace("/badbanks/i", "bad banks", $title);
		$title = preg_replace("/S W A T/i", "S.W.A.T.", $title);
		$title = preg_replace("/Marvels/i", "Marvel's", $title);
		$title = preg_replace("/La Casa De Papel(?: A\s?K\s?A Money Heist)?|CDP/i", "Money Heist", $title);
		$title = preg_replace("/El Hoyo/i", "The Platform", $title);
		$title = preg_replace("/Assassins Creed/i", "Assassin's Creed", $title);
		$title = preg_replace("/Wmulan/i", "Мulan", $title);
		$title = preg_replace("/A X L/i", "A-X-L", $title);
		$title = preg_replace("/^X2$/i", "X-Men 2", $title);
		$title = preg_replace("/(Borat).*(?:Cultural|(Subsequent Moviefilm)).*/i", "$1 $2", $title);
		$title = preg_replace("/^Whdrno/i", "James Bond Dr No", $title);
		$title = preg_replace("/^Jworld/i", "Jurassic World", $title);
		$title = preg_replace("/Stephen King (The Stand)/i", "$1", $title);
		$title = preg_replace("/Mаde In Frаnce/ui", "Made in France", $title);	//cyrillic in title
		$title = preg_replace("/\d+ (No Time To Die|Spectre|Skyfall|Quantum of Solace|Casino Royale|Die Another Day|The World Is Not Enough|Tomorrow Never Dies|Golden Eye|Licence to Kill|The Living Daylights|A View to a Kill|Octopussy|Never Say Never Again|For Your Eyes Only|Moonraker|The Spy Who Loved Me|The Man with the Golden Gun|Live and Let Die|Diamonds Are Forever|On Her Majesty'?s Secret Service|You Only Live Twice|Thunderball|Goldfinger|From Russia with Love|Dr\.? No)/i", "$1", $title);
		$title = preg_replace("/www\sCineVood\sSurf\s/i", "", $title);	//on some hindi movies
		$title = preg_replace("/Mayans M C/i", "Mayans M.C.", $title);
		$title = preg_replace("/Whhwwoman|WonderWoman|ww84/i", "Wonder Woman 1984", $title);
		$title = preg_replace("/Whzsjlue|Zack Snyders Justice League/i", "Zack Snyder's Justice League", $title);
		$title = preg_replace("/Queens Gambit/i", "Queen's Gambit", $title);
		$title = preg_replace("/Tom Clancys/i", "Tom Clancy's", $title);
		$title = preg_replace("/Une Vieille Maitresse/i", "The Last Mistress", $title);
		$title = preg_replace("/Friends the Reunion/i", "Friends Reunion Special", $title);
		$title = preg_replace("/(Chernobyl) Abyss/i", "$1", $title);
		$title = preg_replace("/^F9$|Whhfaf9/i", "F9 The Fast Saga", $title);
		$title = preg_replace("/Fast And Furious (F9 The Fast Saga)/i", "$1", $title);
		$title = preg_replace("/Yumi's/i", "Yumis", $title);
		$title = preg_replace("/What's Wrong with Secretary Kim/i", "What’s Wrong with Secretary Kim", $title);
		$title = preg_replace("/Blakes 7/i", "Blake's 7", $title);
		$title = preg_replace("/The Hitmans Wifes/i", "Hitman's Wife's", $title);
		$title = preg_replace("/The Matrix 4 .*/i", "The Matrix Resurrections", $title);
		$title = preg_replace("/Naruto\s?-?\s?(?:0[0-4]\d$|050$)/i", "Naruto - 001", $title);
		$title = preg_replace("/Naruto\s?-?\s?(?:05[1-9]$|0[6-9]\d$|100$)/i", "Naruto - 051", $title);
		$title = preg_replace("/Naruto\s?-?\s?(?:10[1-9]$|1[1-4]\d$|150$)/i", "Naruto - 101", $title);
		$title = preg_replace("/Naruto\s?-?\s?(?:15[1-9]$|1[6-9]\d$|200$)/i", "Naruto - 151", $title);
		$title = preg_replace("/(\w+)?(Don'?t)(\w+)?/i", "$1 $2 $3", $title);
		$title = preg_replace("/(?:^|\s)Dont(?=\s|$)/i", "Don't", $title);
		$title = preg_replace("/Dan Brown'?s\s?(.*)/i", "$1", $title);
		$title = preg_replace("/(?<!About|Among|And|&|Are|Around|Ask|\sAt|Becomes|Becoming|Beneath|Between|Blind|But|Catch|Change|Changed|Deliver|Destroy|Divide|Fool|For|Fortune|Found|Friend|Help|Hermano|\sIs|Just|Like|Lovely|Lucky|Made|Marry|\sMe|New|\sOf|\sOn|Over|Plus|Poor|\sR|\sR'|See|Than|\sTo|With)(\sus)(?=\s-|$)/i", "", $title);	//removes enginds on US unless these series
		$title = preg_replace("/^War\s?(?!ra|rior|ren|ship)(.*)/i", "$1", $title); //removes WAR if War.* but not Warra, Warrant etc
		$title = preg_replace("/(The)(\d+)/i", "$1 $2", $title);
		$title = preg_replace("/(Mazhor)\s(\d+)$/i", "$1 01x$2", $title);
		$title = preg_replace("/Obi Wan/i", "Obi-Wan", $title);
		$title = preg_replace("/Kukhnya/i", "Kuhnya", $title);
		$title = preg_replace("/(.*)ofthe(.*)/i", "$1 Of The $2", $title);
		$title = preg_replace("/Tlotr|Lotrringsofpower/i", "The Lord Of The Rings", $title);
		$title = preg_replace("/BMF (Black Mafia Family)/i", "$1", $title);
		$title = preg_replace("/(BMF)(?! Black)/i", "Black Mafia Family", $title);
		$title = preg_replace("/Valhmurders/i", "The Valhalla Murders", $title);
		$title = preg_replace("/GoT (House Of The Dragon)/i", "$1", $title);
		$title = preg_replace("/Houseofthedragon/i", "House Of The Dragon", $title);
		$title = preg_replace("/TheMoviesBoss (1899)/i", "$1", $title);
		$title = preg_replace("/İvedik/i", "Ivedik", $title);
		$title = preg_replace("/TheLastOfUs/i", "The Last Of Us", $title);
		$title = preg_replace("/(Project)(Gemini)/i", "$1 $2", $title);
		$title = preg_replace("/Pussinboots(.*)/i", "Puss in boots $1", $title);
		$title = preg_replace("/Knockatethecabin/i", "Knock At The Cabin", $title);
		$title = preg_replace("/(Home)(alone)(\d)?/i", "$1 $2 $3", $title);
		$title = preg_replace("/Cocainebear/i", "Cocaine bear", $title);
		$title = preg_replace("/Avatar\s?2/i", "Avatar The Way of Water", $title);
		$title = preg_replace("/Ripd2/i", "R I P D 2 Rise Of The Damned", $title);
		$title = preg_replace("/Guy Ritchies/i", "Guy Ritchie's", $title);

		$title = preg_replace('/\s+(?=\s)/', '$1', $title);	//removes more than 1 space
		$title = trim($title);	//removes only spaces in ^ and $
		if (preg_match('/.{69,}/', $title)) //dies if title longer than 69 symbols after trimming
			die;
		if (empty($title))
			die;

		return $title;
	}

	public function jsonForMovian($site, $subs)
	{
		$subs = array_unique($subs);
		$allSubs = array();
		foreach($subs as $link => $title)
		{
			$title = preg_replace('/(^[\s\S]*?)(?=%2B|\+)/i', '$1', urlencode($title));	//removes '+.*' in title //was (.*)(?:%2B).*
//			$title = preg_replace('/\+/i', '%20', $title); //new
//			$link = preg_replace('/(.*)&\d+$/', '$1', $link);
			$link = preg_replace('/(.*)(?:%26|&)\d+$/', '$1', urlencode($link));	//removes random string in key from bgsubs.php '&\d+'
			$allSubs[] = array(
				"path" => "http://url/?loadSubs=$link&file=$title",
				"file" => urldecode($title) . '.srt',
				"type" => "",
				"site" => $site,
			);
		}

		return json_encode($allSubs);
		
	}

	public function getSubFilesFromArchive($url, $data='')
	{
		return $this->subsExtractor($url, '', '', $data);
	}

	protected function httpRequest($url, $referer='', $data='')
	{
		if (!empty($data))
		{
			$method = 'POST';
			$content = http_build_query($data);
		}
		else
		{
			$method = 'GET';
			$content = '';
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

		if (!empty($referer))
		{
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
		if (!empty($data))
		{
			curl_setopt($ch,CURLOPT_POST, TRUE);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
		}
//This case is for the new Yavka site - may not be used if sub page with ID is saved
		if (preg_match('/(https:\/\/yavka.net\/subs\/\d+\/BG)(\/.*)/i', $url, $matches))
		{
			$url = $matches[1];
			$referer = $url;
			$content = '';
		}

		$this->tmpfile = $this->cache_dir . md5(strtolower($url.$referer.$content)); //makes all requests lowercase so they have the same md5 (less cache files)
//is cached
		if (file_exists($this->tmpfile) && filesize($this->tmpfile) > 10)
		{
			$result = file_get_contents($this->tmpfile);
		}
//not cached yet
		else
		{
			$result = curl_exec($ch);
			if (!$result)
				return false;

			file_put_contents($this->tmpfile, $result);
		}

		curl_close($ch);
		return $result;
	}

	protected function archiveType($data)
	{
		$firstBytes = substr($data, 0, 2);
		switch($firstBytes)
		{
			case 'PK':
				return 'zip';
				break;

			case 'Ra':
				return 'rar';
				break;

			case '7z':
				return '7z';
				break;

			default:
				return 'unknown format';
		}
		
	}

	protected function subsExtractor($url, $filename='', $referer='', $data='')
	{
		if (empty($referer))
			$referer = $url;

		$result = $this->httpRequest($url, $referer, $data);
		switch($this->archiveType($result))
		{
			case 'zip':
				$subs = $this->zipExtract($filename);
				break;

			case 'rar':
				$subs = $this->rarExtract($filename);
				break;

			case '7z':
				$subs = $this->a7zExtract($filename);
				break;

			default:
				$subs = true;
				$this->subs = $result;
		}

//		unlink($this->tmpfile);
		return $subs;
	}

//if sub_filename is empty - returns a list of sub files, else return sub_filename contents
	protected function a7zExtract($sub_filename='')
	{
		$aSubFiles = array();
		$archive = new a7zExtract($this->tmpfile);
		foreach ($archive->entries() as $entry)
		{
			$filename = $entry['Name'];
			$tmp7z = '/mnt/tmp/tmp7zip/';
			if (!file_exists($tmp7z . $filename) && (strstr($filename, '.srt') || strstr($filename, '.sub')) && $entry['Size'] < 256*1024)
			{
				$aSubFiles[] = substr_replace($filename, '', -4);	//remove file extensions, last 4 chars
				$archive->extractTo($tmp7z, $filename);
				touch($tmp7z . $filename);
				$fp = fopen($tmp7z . $filename, 'r');
					$subs = fread($fp, 1024*1024);
					fclose($fp);
					$enc = mb_detect_encoding($subs, mb_list_encodings());
					if ($enc == 'ISO-8859-1')
					{
						$this->subs = iconv('cp1251', 'utf-8', $subs);
					}
					else
					{
						$this->subs = iconv($enc, 'utf-8', $subs);
					}
//				unlink ($tmp7z . $filename);
			}
			elseif ((strstr($filename, '.srt') || strstr($filename, '.sub')) && $entry['Size'] < 256*1024)
			{
				$aSubFiles[] = substr_replace($filename, '', -4);
				$fp = fopen($tmp7z . $filename, 'r');
				$subs = fread($fp, 1024*1024);
				fclose($fp);
				$enc = mb_detect_encoding($subs, mb_list_encodings());
				if ($enc == 'ISO-8859-1')
				{
					$this->subs = iconv('cp1251', 'utf-8', $subs);
				}
				else
				{
					$this->subs = iconv($enc, 'utf-8', $subs);
				}
			}
		}
		return $aSubFiles;
	}

	protected function rarExtract($sub_filename='')
	{
		$aSubFiles = array();
		$rar_arch = RarArchive::open($this->tmpfile);
		if ($rar_arch === FALSE)
		{
			return false;
		}

		$list = $rar_arch->getEntries();
		foreach($list as $rar)
		{
			$filename = $rar->getName();
			if ((strstr($filename, '.srt') || strstr($filename, '.sub')) && $rar->getUnpackedSize() < 256*1024)
			{
				$aSubFiles[] = substr_replace($filename, '', -4);
				if (!empty($sub_filename) && strstr($filename, $sub_filename))
				{
					$fp = $rar->getStream();
					$subs = fread($fp, 1024*1024);
					fclose($fp);
					$enc = mb_detect_encoding($subs, mb_list_encodings());
					if ($enc == 'ISO-8859-1')
					{
						$this->subs = iconv('cp1251', 'utf-8', $subs);
					}
					else
					{
						$this->subs = iconv($enc, 'utf-8', $subs);
					}
					return true;
				}
			}
		}
		return $aSubFiles;
	}

	protected function zipExtract($sub_filename='')
	{
		$aSubFiles = array();
		$zip = new ZipArchive;
		if ($zip->open($this->tmpfile))
		{
			for($i=0; $i < $zip->numFiles; $i++)
			{
				$stat = $zip->statIndex($i);
				$filename = $stat['name'];
				if ((strstr($filename, '.srt') || strstr($filename, '.sub')) && $stat['size'] < 256*1024)
				{
					$aSubFiles[] = substr_replace($filename, '', -4);
					if (!empty($sub_filename) && strstr($filename, $sub_filename))
					{
						$enc = mb_detect_encoding($zip->getFromName($filename), mb_list_encodings());
						if ($enc == 'ISO-8859-1')
						{
							$this->subs = iconv('cp1251', 'utf-8', $zip->getFromName($filename));
						}
						else
						{
							$this->subs = iconv($enc, 'utf-8', $zip->getFromName($filename));
						}
						return true;
					}
				}
			}
			$zip->close();
		}
		unset($zip);
		return $aSubFiles;
	}

//==========Stast with Cookie file functions==========
	protected function httpRequestCF($ckfile, $url, $referer='', $data='')
	{
		if (!empty($data))
		{
			$method = 'POST';
			$content = http_build_query($data);
		}
		else
		{
			$method = 'GET';
			$content = '';
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);

		if (!empty($referer))
		{
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
		if (!empty($data))
		{
			curl_setopt($ch,CURLOPT_POST, TRUE);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
		}
/*		if (preg_match('/(http:\/\/www.easternspirit.org\/forum\/index.php\?\/files\/file\/[\s\S]*?\/)(&do=download&csrfKey=.*)/i', $url, $matches))
		{
			$url = $matches[1];
			$referer = $url;
			$content = '';
		}*/
		$this->tmpfile = $this->cache_dir . md5(strtolower($url.$referer.$content));

		//is cached
		if (file_exists($this->tmpfile) && filesize($this->tmpfile) > 10)
		{
			$result = file_get_contents($this->tmpfile);
		}
		//not cached yet
		else
		{
			$result = curl_exec($ch);
			if (!$result)
				return false;

			file_put_contents($this->tmpfile, $result);
		}

		curl_close($ch);
		return $result;
	}
	public function getSubFilesFromArchiveCF($ckfile, $url, $data='')
	{

		return $this->subsExtractorCF($ckfile, $url, '', '', $data);
	
	}
	
	
	protected function subsExtractorCF($ckfile, $url, $filename='', $referer='', $data='')
	{
		if (empty($referer))
			$referer = $url;

		$result = $this->httpRequestCF($ckfile, $url, $referer, $data);
		switch($this->archiveType($result))
		{
			case 'zip':
				$subs = $this->zipExtract($filename);
				break;

			case 'rar':
				$subs = $this->rarExtract($filename);
				break;

			case '7z':
				$subs = $this->a7zExtract($filename);
				break;

			default:
				$subs = true;
				$this->subs = $result;
		}
		return $subs;
	}
//==========End with Cookie file functions==========

	public function loadSubs($url, $filename, $referer='')
	{
//		$url = urldecode($url); //Test without encoding if issues occur
		$this->subsExtractor($url, $filename, $referer);
		return $this->subs;
	}

}

?>
