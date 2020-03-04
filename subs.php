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
		$remove_suffixes = array(' \d{3,4}[pi]', ' SD', 'TrueHD', '[FU]HD', ' HU?D', ' [xh]26[45]', ' [24]k', ' NF ', 'AMZN', ' DC ', 'DVD', 'DivX', 'XviD', 'Pk ', ' TS ', 'WEB ', 'BluRay', 'BDRip', 'BRRip', 'DVBRip', 'TVRip', 'VHSRip', 'iNTERNAL', 'PROPER', 'PEPACK', 'AC3', 'AAC', 'DTS', 'Atmos', 'HEVC', 'SCREENER', 'SCR ', ' PAL ', 'SECAM', 'NTSC', '\[', '');
		foreach($remove_suffixes as $rs)
		{
			$title = preg_replace("/(.*)$rs.*/i", '$1', $title);
		}
		$title = preg_replace('/(?!^)[12]\d{3}/', '$1', $title);
		$title = preg_replace('/^episode\s+\d+\s+(.*)$/i', '$1', $title);
		$title = preg_replace('/(s?(\d+)[ex](\d+))(.*)$/i', '$1', $title);
		$title = preg_replace('/\s+ii\s+/i', ' 2 ', $title);
		$title = preg_replace('/\s+iii\s+/i', ' 3 ', $title);
		$title = preg_replace('/\s+iv\s+/i', ' 4 ', $title);
		$title = preg_replace('/\s+vi\s+/i', ' 6 ', $title);
		$title = preg_replace('/\s+vii\s+/i', ' 7 ', $title);
		$title = preg_replace('/\s+viii\s+/i', ' 8 ', $title);
		$title = preg_replace('/\s+ix\s+/i', ' 9 ', $title);
		$title = preg_replace('/\s+x\s+/i', ' 10 ', $title);
		$title = preg_replace('/(Narcos)\s+?(Mexico)/i', '$1', $title);

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
			$downloadUrl = urlencode($downloadUrl);
			$allSubs[] = array(
				"path" => "http://path_to_api/?loadSubs=$downloadUrl&file=$title",
				"file" => $title,
				"type" => 'srt',
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
		//zip, rar or 7z?
		switch($firstBytes)
		{
			case "PK":
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

		$this->tmpfile = tempnam('/mnt/tmp/subs', 'phpsub-');
		file_put_contents($this->tmpfile, $result);

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

		// extract subtitles
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
			if ((strstr($filename, '.srt') || strstr($filename, '.sub')) && $entry['Size'] < 200*1024)
			{
				$aSubFiles[] = substr_replace($filename, '', -4);//remove file extensions, last 4 chars
				$file = $archive->extractTo('/mnt/tmp/subs', $filename);
					$fp = fopen('/mnt/tmp/subs/' . $filename, 'r');
					$subs = fread($fp, 1024*1024);
					fclose($fp);
					$this->subs = mb_convert_encoding($subs, 'utf-8', 'auto');
					unlink ('/mnt/tmp/subs/' . $filename);
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
			if ((strstr($filename, '.srt') || strstr($filename, '.sub')) && $rar->getUnpackedSize() < 200*1024)
			{
				$aSubFiles[] = substr_replace($filename, '', -4);//remove file extensions, last 4 chars
				if (!empty($sub_filename) && strstr($filename, $sub_filename))
				{
					$fp = $rar->getStream();
					$subs = fread($fp, 1024*1024);
					fclose($fp);
					$this->subs = mb_convert_encoding($subs, 'utf-8', 'auto');
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

				if ((strstr($filename, '.srt') || strstr($filename, '.sub')) && $stat['size'] < 200*1024)
				{
					$aSubFiles[] = $filename;
					if (!empty($sub_filename) && strstr($filename, $sub_filename))
					{
						$this->subs = mb_convert_encoding($zip->getFromName($filename), 'utf-8', 'auto');
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
