<?php
class subs
{
	protected $cache_dir = '/mnt/tmp/cache/';
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

	protected function getTitle($title)
	{
		$title = urldecode($title);
		$title = str_replace(
			array('.', ':', '-', '_', '(', ')'), ' ',
			$title);
		$title = preg_replace('/\%u([0-9A-F]{4})/', '&#x\\1;', $title);
		$title = html_entity_decode($title, ENT_NOQUOTES, 'utf-8');
		$remove_suffixes = array(' \d{3,4}[pi]', ' SD', 'TrueHD', '[FU]HD', ' HU?D', ' [xh]26[45]', ' [24]k', ' HC', ' NF', 'AMZN', ' BD', ' DC', 'DVB', 'DVD', 'DivX', 'XviD', ' Pk', ' TS', ' TC', ' WEB', 'BluRay', 'BDRip', 'BRRip', 'DVBRip', ' Rip ', 'SATRip', 'TVRip', 'VHSRip', 'iNTERNAL', 'PROPER', 'PEPACK', 'AC3', 'AAC', 'DTS', 'Atmos', 'HEVC', ' SCR', ' PAL', 'SECAM', 'NTSC', '\[');
		foreach($remove_suffixes as $rs)
		{
			$title = preg_replace("/(.*)$rs.*/i", '$1', $title);
		}

		$title = preg_replace('/(?!^)[12]\d{3}/', '$1', $title);
		$title = preg_replace('/(.*\ss?\d+[ex]\d+).*/i', '$1', $title);
		$title = preg_replace('/^s?\d+[ex]\d+\s?(.*)$/i', '$1', $title);
		$title = preg_replace('/^episode\s+\d+\s+(.*)$/i', '$1', $title);

		$title = preg_replace('/\s+ii$/i', ' 2', $title);
		$title = preg_replace('/\s+iii$/i', ' 3', $title);
		$title = preg_replace('/\s+iv$/i', ' 4', $title);
		$title = preg_replace('/\s+vi$/i', ' 6', $title);
		$title = preg_replace('/\s+vii$/i', ' 7', $title);
		$title = preg_replace('/\s+viii$/i', ' 8', $title);
		$title = preg_replace('/\s+ix$/i', ' 9', $title);
		$title = preg_replace('/magicians us/i', 'magicians', $title);
		$title = preg_replace('/기생충/i', 'Parasite', $title);
		$title = preg_replace('/葉問/i', 'Ip man', $title);
		$title = preg_replace('/감기/i', 'Cold', $title);
		$title = preg_replace('/غار/i', 'Cave', $title);

		$title = preg_replace('/^\s?download\s?$/i', '', $title);
		$title = preg_replace('/New\s?$/i', '', $title);
		$title = preg_replace('/Danish\s?$/i', '', $title);
		$title = preg_replace('/sir david attenborough/i', '', $title);
		
		$title = preg_replace('/\s+(?=\s)/', '$1', $title);
		$title = trim($title);
		if (empty($title))
			die;

		return $title;
	}

	public function jsonForMovian($site, $aSubs)
	{
		$aSubs = array_unique($aSubs);
		$allSubs = array();
		foreach($aSubs as $downloadUrl => $title)
		{
			$title = urlencode($title);
			$title = preg_replace('/(.*)%2B.*/', '$1', $title);
			$downloadUrl = urlencode($downloadUrl);
			$downloadUrl = preg_replace('/(.*)\%26\d+$/', '$1', $downloadUrl);
			$allSubs[] = array(
				"path" => "http://path_to_api/?loadSubs=$downloadUrl&file=$title",
				"file" => $title,
				"type" => "srt",
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

		$this->tmpfile = $this->cache_dir . md5($url.$referer.$content);
		//is cached
		if (file_exists($this->tmpfile) && filesize($this->tmpfile) > 10)
		{
			$result = file_get_contents($this->tmpfile);
		}
		// not cached yet
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
		//zip, rar or 7z?
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
				return 'unknown';
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
				$subs = $this->SevenZipArchive($filename);
				break;

			default:
				$subs = true;
				$this->subs = $result;
		}

		unlink($this->tmpfile);
		return $subs;
	}

	//if sub_filename is empty - returns a list of sub files, else return sub_filename contents
	protected function SevenZipArchive($sub_filename='')
	{
		$aSubFiles = array();
		$archive = new SevenZipArchive($this->tmpfile);
		foreach ($archive->entries() as $entry)
		{
			$filename = $entry['Name'];
			if ((strstr($filename, '.srt') || strstr($filename, '.sub')) && $entry['Size'] < 256*1024)
			{
				$aSubFiles[] = substr_replace($filename, '', -4);//remove file extensions, last 4 chars
				$file = $archive->extractTo('/mnt/tmp/tmp7z/', $filename);
				$fp = fopen('/mnt/tmp/tmp7z/' . $filename, 'r');
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
				unlink ('/mnt/tmp/tmp7z/' . $filename);
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
				$aSubFiles[] = substr_replace($filename, '', -4);//remove file extensions, last 4 chars
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
				if ((strstr($filename, '.srt') || strstr($filename, '.sub')) && $stat['size'] < 256*1024)
				{
					$aSubFiles[] = $filename;
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
	public function loadSubs($url, $filename, $referer='')
	{
		$url = urldecode($url);
		$this->subsExtractor($url, $filename, $referer);
		return $this->subs;
	}

}

?>
