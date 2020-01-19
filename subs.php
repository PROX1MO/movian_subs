<?php
class subs
{
	protected $cache_dir = '/mnt/tmp/cache';
	protected $useragent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36';
	protected $subs;
	protected $provider;

	public function setProvider($provider)
	{
		$this->provider = $provider;
	}

	public function searchSubs($title)
	{
		//return $this->searchSubsUnacs($title);
		return false;
	}

	protected function getTitle($title)
	{
		$title = urldecode($title);
		$title = str_replace(
			array('.', ':', '-', '_', '(', ')'), ' ',
			$title);
        $title = preg_replace("/\%u([0-9A-F]{4})/", "&#x\\1;", $title);
        $title = html_entity_decode($title, ENT_NOQUOTES, 'UTF-8');

		$remove_suffixes = array('SD', 'HD', 'FHD', 'UHD', 'TrueHD', 'NF', 'AMZN', 'XviD', 'Pk', 'TS', '2K', '4K', '480p', '576p', '720p', '1080p', '1440p', '2160p', 'Web', 'BluRay', 'BRRip', 'iNTERNAL', 'x264', 'x265', 'AC3', 'DTS', 'Atmos', 'HUD', '\[', '');

		foreach($remove_suffixes as $rs)
		{
			$title = preg_replace("/(.*)$rs.*/i", '$1', $title);
		}
		$title = preg_replace('/20\d\d/', '', $title);
		$title = trim($title);
//		DEBUG - result for every provider
//		$time = date("H:i:s d-m-y");//, + strtotime("+2 Hours"));
//		$ip = $_SERVER['REMOTE_ADDR'];
//		file_put_contents('/path/~Requests.log', "$title @ $time > $ip\n", FILE_APPEND);
		return $title;
	}

	public function jsonForMovian($site, $aSubs)
	{
		$aSubs = array_unique($aSubs);
		$allSubs = array();

		foreach($aSubs as $downloadUrl => $title)
		{
			$title = urlencode($title);
			$downloadUrl = urlencode($downloadUrl);
			$allSubs[] = array(
				"path" => "http://path_to_url/?loadSubs=$downloadUrl&file=$title", //important to set
				"file" => $title,
				"type" => 'srt',
				"site" => $site,
			);
		}
		//file_put_contents("/path/json_encoded.txt", json_encode($allSubs) . "\n", FILE_APPEND);
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

		//file_put_contents("/path/cache_debug.log", "$url.$referer.$content\n", FILE_APPEND);
		$cache_file = $this->cache_dir . "/" . md5($url.$referer.$content);
		//is cached
		if (false && file_exists($cache_file) && filesize($cache_file) > 10)
		{
			$result = file_get_contents($cache_file);
		}
		// not cached yet
		else
		{
			$result = curl_exec($ch);
			if (!$result)
				return false;

			file_put_contents($cache_file, $result);
		}

		curl_close($ch);
		return $result;
	}

	protected function archiveType($data)
	{
		$firstBytes = substr($data, 0, 2);
		//zip or rar?
		switch($firstBytes)
		{
			case "PK":
				return 'zip';
				break;
		
			case 'Ra':
				return 'rar';
				break;
			default:
				return 'unknown';
		}
		
	}

	protected function subsExtractor($url, $filename='', $referer='', $data='')
	{
		if (empty($referer))
			$referer = $url;

		$result = $this->httpRequest($url, $referer, $data);
		//print($result);
		//file_put_contents("/path/result", $result);

		$this->tmpfile = tempnam("/tmp", "phpsub-");
		file_put_contents($this->tmpfile, $result);

		switch($this->archiveType($result))
		{
			case 'zip':
				$subs = $this->zipExtract($filename);
				break;

			case 'rar':
				$subs = $this->rarExtract($filename);
				break;
			default:
				$subs = true;
				$this->subs = $result;
		}

		// extract subtitles
		//unlink($this->tmpfile);
		return $subs;
	}

	//if sub_filename is empty - returns a list of sub files, else return sub_filename contents
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
			if (strstr($filename, '.srt') || strstr($filename, '.sub'))
			{
				$aSubFiles[] = substr_replace($filename ,"", -4);//remove file extensions, last 4 chars
				if (!empty($sub_filename) && strstr($filename, $sub_filename))
				{
					$fp = $rar->getStream();
					$subs = fread($fp ,1024*1024);
					fclose($fp);
					$this->subs = iconv('cp1251', 'utf8', $subs);
					return true;
				}
			}
		}
		return $aSubFiles;
	}
	//if sub_filename is empty - returns a list of sub files, else return sub_filename contents
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

				if (strstr($filename, '.srt') || strstr($filename, '.sub'))
				{
					$aSubFiles[] = $filename;
					if (!empty($sub_filename) && strstr($filename, $sub_filename))
					{
						$this->subs = iconv('cp1251', 'utf8', $zip->getFromName($filename));
						return true;
					}
				}
			}
			$zip->close();
		}
		unset($zip);
		return $aSubFiles;
	}
	public function loadSubs($url, $filename, $referer='')
	{
		$url = urldecode($url);
		$this->subsExtractor($url, $filename, $referer);
		return $this->subs;
	}
}

?>
