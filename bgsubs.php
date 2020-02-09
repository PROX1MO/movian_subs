<?php
class bgsubs extends subs
{
	public function searchSubs($title)
	{
		switch($this->provider)
		{
			case 'subsabs':
				$subs = $this->searchSubsSabBz($title);
			break;

			case 'unacs':
				$subs = $this->searchSubsUnacs($title);
			break;

			case 'addicted':
				$subs = $this->searchSubsAddic7ed($title);
			break;

			case 'yavka':
				$subs = $this->searchSubsYavka($title);
			break;

			case 'podnapisi':
				$subs = $this->searchSubsPodnapisi($title);
			break;

			case 'bukvi':
				$subs = $this->searchSubsBukvi($title);
			break;

			default:
				$subs = false;
		}
		return $subs;
	}

	public function searchSubsSabBz($title)
	{
		$title = $this->getTitle($title);
		$subs = array();
		$searchUrl = 'http://subs.sab.bz/index.php?';
		$postData = array(
			'act' => 'search',
			'movie' => $title,
			'select-language' => '2',
		);
		$html = $this->httpRequest($searchUrl, $searchUrl, $postData);
		$html = str_get_html($html);
		if (empty($html))
			return false;

		foreach($html->find('a') as $element)
		{
			$link = $element->href;
			$title = $element->plaintext;
			if (strstr($link, 'download'))
			{

				$link = preg_replace('/s=.*&amp;/', '', $link);
				$aFilesInArchive = $this->getSubFilesFromArchive($link);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link] = $title;
				}

				//$subs[$link] = $title;
			}
		}

		return $this->jsonForMovian('subs.sab.bz', $subs);
	}

	public function searchSubsUnacs($title)
	{
		$title = $this->getTitle($title);
		$subs = array();
		$searchUrl = 'https://subsunacs.net/search.php';
		$postData = array(
			'm' => $title,
			't' => 'Submit',
		);
		//TODO
		// memcached md5($searchUrl,$postdata)
		$html = $this->httpRequest($searchUrl, $searchUrl, $postData);
		$html = str_get_html($html);
		if (empty($html))
			return false;

		foreach($html->find('a[class="tooltip"]') as $element)
		{
			$link = 'https://subsunacs.net' . $element->href;
			$aFilesInArchive = $this->getSubFilesFromArchive($link);
			foreach($aFilesInArchive as $title)
			{
				$subs[$link] = $title;
			}
			//$title = $element->plaintext;
			//$subs[$link] = $title;
		}
		$time = date("H:i:s d-m-y");//, + strtotime("+2 Hours"));
		$ip = $_SERVER['REMOTE_ADDR'];
		file_put_contents('/mnt/tmp/req.txt', "$title @ $time > $ip\n", FILE_APPEND);
		return $this->jsonForMovian('subsunacs.net', $subs);
	}
	
	public function searchSubsAddic7ed($title)
	{
		$title = urlencode($this->getTitle($title));
		$subs = array();
		$searchUrl = "http://www.addic7ed.com/srch.php?search=$title&Submit=Search";
		$html = $this->httpRequest($searchUrl, $searchUrl);
		$html=str_get_html($html);
		if (empty($html))
			return false;

		foreach($html->find('a') as $movieLink) {
			if (! preg_match('/movie\/[0-9]*$/', $movieLink->href)) {
				continue;
			}

			$movieUrl = 'http://www.addic7ed.com/' . $movieLink->href;
			$html = $this->httpRequest($movieUrl, $movieUrl);
			$html = str_get_html($html);
			if (empty($html))
				continue;
			}

			$title_prefix = @$html->find('span[class=titulo]', 0)->plaintext;
			$title_prefix = str_replace('- Switch Subtitle', '', $title);
			foreach($html->find('table[class=tabel95]') as $element)
			{
				//get title
				$title_version = @$element->find("td[class=NewsTitle]", 0)->plaintext;
				$title_version = preg_replace('/Version (.*),.*/', '${1}', $title_version);
				if (empty($title_version))
				{
					continue;
				}
				$title = "${title_prefix}-${title_version}";
				$trs = $element->find("tr");
				foreach($trs as $tr)
				{
					$lang = @$tr->find("td[class=language]" ,0)->plaintext;
					$link = "https://www.addic7ed.com";
					$downloadButton = @$tr->find("a[class=buttonDownload]", 0);
					$completed = @$tr->find("td[width=\"19%\"]", 0)->plaintext;
					if(! $downloadButton) {
						continue;
					}
					$link .= $downloadButton->href;
					$completed = @$tr->find("td[width=\"19%\"]", 0)->plaintext;

					if (strstr($completed, '%'))
						continue;

					switch($lang)
					{
						case 'Bulgarian':
						case 'bulgarian':
							$subs[$link] = $title;
							break;
					}
				}
			}
//		print_r($subs);
		return $this->jsonForMovian('addic7ed.com', $subs);
	}

	public function searchSubsYavka($title)
	{
		$title = urlencode($this->getTitle($title));
		$subs = array();
		$searchUrl = "http://yavka.net/subtitles.php?l=BG&y=&u=&s=$title";

		$html = $this->httpRequest($searchUrl, $searchUrl);
		$html = str_get_html($html);
		if (empty($html))
			return false;

		foreach($html->find('div[class="middle_content"] a') as $element)
		{
			if (! preg_match('/\/subs\/[0-9]*\/.*/', $element->href)) {
				continue;
			}
			$link = 'http://yavka.net' . rtrim($element->href, '/') . '/';

			$parts = explode('/', $link);
			$post_data = array(
				'id' => $parts[count($parts, COUNT_NORMAL) - 2],
				'lng' => $parts[count($parts, COUNT_NORMAL) - 1],
			);

			$aFilesInArchive = $this->getSubFilesFromArchive($link, $post_data);
			foreach($aFilesInArchive as $title)
			{
				$subs[$link] = $title;
			}
			//$title = $element->plaintext;
			//$subs[$link] = $title;
		}

		return $this->jsonForMovian('yavka.net', $subs);
	}

	public function searchSubsPodnapisi($title)
	{
		$title = urlencode($this->getTitle($title));
		$subs = array();
		$searchUrl = "https://www.podnapisi.net/bg/subtitles/search/advanced?keywords=$title&language=bg";
		$html = $this->httpRequest($searchUrl, $searchUrl);
		$html = str_get_html($html);
		if (empty($html))
			return false;

		foreach($html->find('a[rel="nofollow"]') as $element)
		{
			$ref=str_get_html($element)->getElementsByTagName('a rel="nofollow" href=')->href;
			$link = 'https://www.podnapisi.net' . $ref;
			$aFilesInArchive = $this->getSubFilesFromArchive($link);
			foreach($aFilesInArchive as $title)
			{
				$subs[$link] = $title;
			}

		}

		return $this->jsonForMovian('podnapisi.net', $subs);
	}

	public function searchSubsBukvi($title)
	{
		$title = urlencode($this->getTitle($title));
		$subs = array();
		$searchUrl = "http://bukvi.bg/index.php?search=$title";
		$html = $this->httpRequest($searchUrl, $searchUrl);
		$html = str_get_html($html);
		if (empty($html))
			return false;

		foreach($html->find('div[class="tooltip"]') as $element)
		{
			$ref=str_get_html($element)->getElementsByTagName('a')->href;
			if (! preg_match('/\/load\/[0-9]{4}$/', $ref)) {
				continue;
			}
			$link = 'http://bukvi.mmcenter.bg' . preg_replace('/(.*)\/(.*)/', '$1/0-0-0-$2-20', $ref);
			$aFilesInArchive = $this->getSubFilesFromArchive($link);
			foreach($aFilesInArchive as $title)
			{
				$subs[$link] = $title;
			}

		}
	
		return $this->jsonForMovian('bukvi.bg', $subs);
	}
}

?>
