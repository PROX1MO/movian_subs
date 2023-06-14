<?php

class bgsubs extends subs
{
	protected $hours = 2;

	public function searchSubs($title)
	{
		$title = $this->getTitle($title);
		switch($this->provider)
		{
			case 'subsabs':
				$subs = $this->searchSubsSabBz(@iconv('utf-8', 'cp1251//IGNORE', $title));	//site uses cp1251 @iconv to suppress notice warnings
			break;

			case 'unacs':
				$subs = $this->searchSubsUnacs(@iconv('utf-8', 'cp1251//IGNORE', $title));	//TRANSLIT other option
			break;

			case 'addicted':
				$subs = $this->searchSubsAddic7ed($title);
			break;

			case 'yavka':
				$subs = $this->searchSubsYavka(urlencode(preg_replace("/(.*)\s-\s(.*)/i", "$1 $2", $title)));	//site needs urlencode to validate request & remove "-" from series
			break;

			case 'podnapisi':
				$subs = $this->searchSubsPodnapisi($title);//temp disabled
			break;

			case 'subsland':
				$subs = $this->searchSubsSubsland(urlencode(@iconv('utf-8', 'cp1251//IGNORE', $title)));
			break;

			case 'espirit':
				$subs = $this->searchSubsESpirit($title);
			break;

			default:
				$subs = false;
		}
		return $subs;
	}

	public function searchSubsSabBz($title)//==================================================================================================
	{
		if (preg_match('/S\d{2}E\d{2}/i', $title))	//drops unwanted S01E01 requests
			return true;
		if (preg_match('/\d{2}(?:x\d{2})?/i', $title))	//remove year for series
			$_POST['year'] = '';
		if (empty($_POST['year']) && preg_match('/^\w{1,2}$/i', $title))	//drop requests with one word up to 2 symbols and no year
			return true;
		$subs = array();
		$searchUrl = 'http://subs.sab.bz/index.php?';
		$postData = array(
			'act' => 'search',
			'movie' => $title,
			'select-language' => '2',
			'yr' => @$_POST['year'],
		);
		$html = $this->httpRequest($searchUrl, $searchUrl, $postData);
		$html = str_get_html($html);
		if (empty($html))
			return false;

		if (!preg_match('/\d{2}x\d{2}|Season\s\d+|Complete\s(.*)Series/i', $title))	//checks if title not a serie, then it's a movie
		{
			foreach($html->find('td[class=c2field] a') as $element)
			{
				if (preg_match('/\d{2}x\d{2}|Season\s\d+|Complete\s(.*)Series|3D/i', $element))	//we search for movies, so we drop serie links & 3D subs
					continue;
//				$link = $element->href;
				$link = preg_replace('/s=.*&amp;/', '', $element->href);
				$aFilesInArchive = $this->getSubFilesFromArchive($link);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;	//adds random string to the key to be unique, later it's removed in subs.php/jsonForMovian
				}
			}

			if (empty($subs) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))	//old ($this->days * 24 * 60 * 60)
			{
				unlink($this->tmpfile);
				return $this->searchSubsSabBz($title);
			}
		}
		else	//if not a movie, then it is a serie
		{
			foreach($html->find('td[class=c2field] a') as $element)
			{
				if (preg_match('/Complete\s(.*)Series|3D/i', $element))	//drops complete all seasons bundle & 3D subs
					continue;
				$link = preg_replace('/s=.*&amp;/', '', $element->href);
				$aFilesInArchive = $this->getSubFilesFromArchive($link);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;
				}
			}
			
			if (empty($subs) && preg_match('/\d{2}x\d{2}/i', $title) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))	//if serie empty & old > delete cache & make new search
			{
				unlink($this->tmpfile);
				return $this->searchSubsSabBz($title);
			}
			if (empty($subs) && preg_match('/(.*)\ss?(\d{2})[ex]\d{2}/i', $title, $matches))	//if no serie found, try with a whole season and loop
			{
				return $this->searchSubsSabBz($matches[1] . ' Season ' . intval($matches[2]));
			}
			if (empty($subs) && preg_match('/Season\s\d+$/i', $title) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))	//if season empty & old > delete cache & make new search
			{
				unlink($this->tmpfile);
				return $this->searchSubsSabBz($title);
			}
		}
/*
		if (empty($subs) && filemtime($this->tmpfile) < (time() - ($this->days * 24 * 60 * 60)))	//if empty results, delete after 1 day and loop
		{
			unlink($this->tmpfile);
			$this->searchSubsSabBz($title);
		}*/
		return $this->jsonForMovian('subs.sab.bz', $subs);	//if all OK return results
	}

	public function searchSubsUnacs($title)//==================================================================================================
	{
		if (preg_match('/S\d{2}E\d{2}/i', $title))	//drops unwanted S01E01 requests
			return true;	//site blocks seach on words with 1 symbol
		if (preg_match('/\d{2}(?:x\d{2})?/i', $title))	//remove year for series
			$_POST['year'] = '';
		if (empty($_POST['year']) && preg_match('/^\w{1,2}$/i', $title))	//drop requests with one word up to 2 letters and no year
			return true;
		$subs = array();
		$searchUrl = 'https://subsunacs.net/search.php';
		$postData = array(
			'm' => $title,
			't' => 'Submit',
			'y' => @$_POST['year'],
		);

		$html = $this->httpRequest($searchUrl, $searchUrl, $postData);
		$html = str_get_html($html);
		if (empty($html))
			return false;

		if (!preg_match('/\d{2}x\d{2}|Complete$/i', $title))	//checks if title not a serie, then it's a movie
		{
			foreach($html->find('a[class=tooltip]') as $element)
			{
				if (preg_match('/\d{2}x\d{2}|Complete\sSe\w{4}|3D/i', $element))	//we search for movies, so we drop serie links & 3D subs
					continue;
				$link = 'https://subsunacs.net' . $element->href;
				$aFilesInArchive = $this->getSubFilesFromArchive($link);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;
				}
			}

			if (empty($subs) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))
			{
				unlink($this->tmpfile);
				return $this->searchSubsUnacs($title);
			}
		}
		else	//if not a movie, then it is a serie
		{
			foreach($html->find('a[class=tooltip]') as $element)
			{
				if (preg_match('/3D/i', $element))	//can't skip 3D is in other class, but it's not extracted
					continue;
				$link = 'https://subsunacs.net' . $element->href;
				$aFilesInArchive = $this->getSubFilesFromArchive($link);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;
				}
			}

			if (empty($subs) && preg_match('/\d{2}x\d{2}/i', $title) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))
			{
				unlink($this->tmpfile);
				return $this->searchSubsUnacs($title);
			}
			if (empty($subs) && preg_match('/(.*)\ss?(\d{2})[ex]\d{2}/i', $title, $matches))	//if no result in a serie search try with a whole season and loop
			{
				return $this->searchSubsUnacs(substr_replace($title, '', -3) . ' Complete');
			}
			if (empty($subs) && preg_match('/Complete\sSeason$/i', $title) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))
			{
				unlink($this->tmpfile);
				return $this->searchSubsUnacs($title);
			}
		}

		return $this->jsonForMovian('subsunacs.net', $subs);
	}
	
	public function searchSubsAddic7ed($title)//==================================================================================================
	{
//		if (!preg_match('/.*\sS?\d{2}[Ex]\d{2}/i', $title)) //drops movie requests
//			die;
		if (preg_match('/S\d{2}E\d{2}/i', $title))	//drops unwanted S01E01 requests
			return true;
		if (empty($_POST['year']) && preg_match('/^\w{1,2}$/i', $title))	//drop requests with one word up to 2 symbols and no Year
			return true;
		if (!empty($_POST['year']) && preg_match('/(.*)\s-\s(\d{2}x\d{2})/i', $title, $matches)) //if Year and serie, title = Name Year - 01x01
		{
			$title = $matches[1] . ' ' . @$_POST['year'] . ' - ' . $matches[2];
		}
		else	//if Year and movie, title = Name Year
		{
			$title .= ' ' . @$_POST['year'];
		}
		$subs = array();
		$searchUrl = "http://www.addic7ed.com/srch.php?search=$title&Submit=Search";
		$html = $this->httpRequest($searchUrl, $searchUrl);
		$html=str_get_html($html);
		if (empty($html))
			return false;

		if (!preg_match('/\d{2}x\d{2}/i', $title))
		{
			foreach($html->find('a') as $movieLink)
			{
				if (preg_match('/movie\/\d+$/i', $movieLink->href)) {
					$movieUrl = 'http://www.addic7ed.com/' . $movieLink->href;
					$html = $this->httpRequest($movieUrl, $movieUrl);
					$html = str_get_html($html);
				}
			}
		}
		else
		{
			foreach($html->find('a') as $serieLink)
			{
				if (preg_match('/serie\/.*$/i', $serieLink->href)) {
					$serieUrl = 'http://www.addic7ed.com/' . $serieLink->href;
					$html = $this->httpRequest($serieUrl, $serieUrl);
					$html = str_get_html($html);

				}
			}
		}

		foreach($html->find('table[class=tabel95]') as $element)
		{
			$title_prefix = trim(@$html->find('span[class=titulo]', 0)->plaintext);	//@ to suppress errors
			$title_prefix = str_replace(' Subtitle', '', $title_prefix);
			$title_version = trim(@$element->find('td[class=NewsTitle]', 0)->plaintext);
			$title_version = preg_replace('/Version (.*),.*/i', '$1', $title_version);
			if (empty($title_version))
				continue;

			$title = $title_prefix . '-' . $title_version;	//"${title_prefix}-${title_version}";
			$trs = $element->find('tr');
			foreach($trs as $tr)
			{
				$lang = trim(@$tr->find('td[class=language]', 0)->plaintext);
				$downloadButton = $tr->find('a[class=buttonDownload]', 0);
				if (!$downloadButton)
					continue;

				$link = 'https://www.addic7ed.com' . $downloadButton->href; //$link = "https://www.addic7ed.com"; $link .= $downloadButton->href;
				$completed = trim(@$tr->find('td[width=19%]', 0)->plaintext);

				if (strstr($completed, '%'))	//if 'X% completed' don't continue
					continue;

				if ($lang != 'Bulgarian')
					continue;
				$subs[$link] = $title;
/*				switch($lang) //old method
				{
					case 'Bulgarian':
						$subs[$link] = $title;
						break;
				}*/
			}
		}

		if (empty($subs) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))
		{
			unlink($this->tmpfile);
			return $this->searchSubsAddic7ed($title);
		}

		return $this->jsonForMovian('addic7ed.com', $subs);
	}

	public function searchSubsYavka($title)//==================================================================================================
	{
		if (preg_match('/\d{2}x\d{2}/i', $title))	//drops unwanted 01x01 requests
			return true;
		if (preg_match('/S\d{2}(?:E\d{2})?/i', $title))	//remove year for series
			$_POST['year'] = '';
		if (empty($_POST['year']) && preg_match('/^\w{1,2}$/i', $title))	//drop requests with one word up to 2 symbols and no Year
			return true;
		$subs = array();
		$searchUrl = @"https://yavka.net/subtitles.php?l=BG&y=&u=&s=$title&y=$_POST[year]";
		$html = $this->httpRequest($searchUrl, $searchUrl);
		$html = str_get_html($html);
		if (empty($html))
			return false;

		if (!preg_match('/S\d{2}(E\d{2})?$/i', $title))	//checks if the title is not a serie or a season, then it's a movie
		{
			foreach($html->find('[class=balon]') as $element)	//scans element
			{
				if (preg_match('/S\d{2}(E\d{2})?|3D/i', $element))	//we search for movies, so we drop serie & 3D links
					continue;
				$link = 'https://yavka.net' . $element->href;
				//$html = $this->httpRequest($link, $link);
				//$html = file_get_html($html);
				$html = file_get_html($link);
				$inner = $html->find('input[type=hidden]', 0);
				$inner = preg_replace('/.*value="(.{100,})".*/', '$1', $inner);
				$postData = array(
					'id' => $inner,
					'lng' => 'BG',
				);
				//$link .= '/';
				$aFilesInArchive = $this->getSubFilesFromArchive($link.'/', $postData);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;
				}
			}

			if (empty($subs) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))
			{
				unlink($this->tmpfile);
				return $this->searchSubsYavka($title);
			}
		}
		elseif (preg_match('/S\d{2}$/i', $title))	//checks if a season
		{
			foreach($html->find('[class=balon]') as $element)
			{
				if (preg_match('/S\d{2}E\d{2}|3D/i', $element))	//we search for a season, so we drop serie & 3D links
					continue;
				$link = 'https://yavka.net' . $element->href;
				//$html = $this->httpRequest($link, $link);
				//$html = str_get_html($html);
				$html = file_get_html($link);
				$inner = $html->find('input[type=hidden]', 0);
				$inner = preg_replace('/.*value="(.{100,})".*/', '$1', $inner);
				$postData = array(
					'id' => $inner,
					'lng' => 'BG',
				);
				$aFilesInArchive = $this->getSubFilesFromArchive($link.'/', $postData);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;
				}
			}

			if (empty($subs) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))
			{
				unlink($this->tmpfile);
				return $this->searchSubsYavka($title);
			}
		}
		else	//if not a movie, then it is a serie
		{
			foreach($html->find('[class=balon]') as $element)
			{
				if (preg_match('/3D/i', $element))
					continue;
				$link = 'https://yavka.net' . $element->href;
				//$html = $this->httpRequest($link, $link);
				//$html = str_get_html($html);
				$html = file_get_html($link);
				$inner = $html->find('input[type=hidden]', 0);
				$inner = preg_replace('/.*value="(.{100,})".*/', '$1', $inner);
				$postData = array(
					'id' => $inner,
					'lng' => 'BG',
				);
				$aFilesInArchive = $this->getSubFilesFromArchive($link.'/', $postData);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;
				}
			}

			if (empty($subs) && preg_match('/S\d{2}E\d{2}/i', $title) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))
			{
				unlink($this->tmpfile);
				return $this->searchSubsYavka($title);
			}
			if (empty($subs) && preg_match('/S\d{2}E\d{2}/i', $title))	//if no serie found, try with a whole season and loop
			{
				return $this->searchSubsYavka(substr_replace($title, '', -3));
			}
			if (empty($subs) && preg_match('/S\d{2}$/i', $title) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))
			{
				unlink($this->tmpfile);
				return $this->searchSubsYavka($title);
			}
		}

		return $this->jsonForMovian('yavka.net', $subs);
	}

	public function searchSubsPodnapisi($title)//==================================================================================================
	{
		if (preg_match('/S\d{2}E\d{2}/i', $title))	//this site can work with both S01E01 & 01x01, I use second because I want such log
			return true;							//alternatively I can replace $title with " . preg_replace('/S(\d{2})E(\d{2})/i', '$1x$2', $title) . "
		if (empty($_POST['year']) && preg_match('/^\w{1,2}$/i', $title))	//drop requests with one word up to 2 symbols if no Year
			return true;
		$subs = array();
		$searchUrl = @"https://www.podnapisi.net/bg/subtitles/search/advanced?keywords=$title&language=bg&year=$_POST[year]";
		$html = $this->httpRequest($searchUrl, $searchUrl);
		$html = str_get_html($html);
		if (empty($html))
			return false;

		if (!preg_match('/S?\d{2}[Ex]\d{2}/i', $title))
		{
			foreach($html->find('a[rel=nofollow]') as $element)
			{
				if (preg_match('/S?\d{2}[Ex]\d{2}/i', $element))
					continue;
				$ref = str_get_html($element)->getElementsByTagName('a[rel=nofollow]')->href;
				$link = 'https://www.podnapisi.net' . $ref;
				$aFilesInArchive = $this->getSubFilesFromArchive($link);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;
				}
			}
		}
		else
		{
			foreach($html->find('a[rel=nofollow]') as $element)
			{
				$ref = str_get_html($element)->getElementsByTagName('a[rel=nofollow]')->href;
				$link = 'https://www.podnapisi.net' . $ref;
				$aFilesInArchive = $this->getSubFilesFromArchive($link);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;
				}
			}
		}

		if (empty($subs) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))
		{
			unlink($this->tmpfile);
			return $this->searchSubsPodnapisi($title);
		}

		return $this->jsonForMovian('podnapisi.net', $subs);
	}

	public function searchSubsSubsland($title)//==================================================================================================
	{
		if (preg_match('/^\w{1,4}$/i', $title))	//drop requests with one word up to X letters
			return true;
		$subs = array();	//searches both S01E01 & 01x01
		$searchUrl = "https://subsland.com/index.php?w=name&category=1&s=$title";
		$html = $this->httpRequest($searchUrl, $searchUrl);
		$html = str_get_html($html);
		if (empty($html))
			return false;

		foreach($html->find('td[font color=Red]') as $error)
		{
			if (preg_match('/Не\sса\sоткрити/i', $error))	//if no match on criteria, drop results
				return true;
		}

		if (!preg_match('/S?\d+[Ex]\d{2}/i', $title))
		{
			foreach($html->find('td[align=center] a') as $element)	//this also works... \<td align="center"\>\<a href=
			{
				if (preg_match('/S?\d+[Ex]\d{2}|S\d+|Season\s\d+/i', $element))
					continue;
				$link = str_get_html($element)->getElementsByTagName('a')->href;
				if (!preg_match('/https:\/\/subsland.com\/downloadsubtitles\//i', $element->href))
					continue;
				$aFilesInArchive = $this->getSubFilesFromArchive($link);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;
				}
			}
		}
		else
		{
			foreach($html->find('td[align=center] a') as $element)
			{
				$link = str_get_html($element)->getElementsByTagName('a')->href;
				if (!preg_match('/https:\/\/subsland.com\/downloadsubtitles\//i', $element->href))
					continue;
				$aFilesInArchive = $this->getSubFilesFromArchive($link);
				foreach($aFilesInArchive as $title)
				{
					$subs[$link . '&' . mt_rand()] = $title;
				}
			}
		}

		if (empty($subs) && filemtime($this->tmpfile) < (time() - (1 * 24 * 60 * 60)))	//wait for 1 days
		{
			unlink($this->tmpfile);
			return $this->searchSubsSubsland($title);
		}

		return $this->jsonForMovian('subsland.com', $subs);
	}

	public function searchSubsESpirit($title)//==================================================================================================
	{
		if (preg_match('/\d{2}x\d{2}/i', $title))	//drops unwanted 01x01 requests
			return true;
		if (preg_match('/^\w{1,4}$/i', $title))	//drop requests with words from 1 to 4 letters
			return true;

		$ckfile = '/mnt/tmp/cookie.txt';
		if (empty($ckfile) || filemtime($ckfile) < (time() - (30 * 24 * 60 * 60)))	//renew every 30 days
		{
			exec("curl -vvvv 'http://www.easternspirit.org/forum/index.php?/login/' -H 'Origin: http://www.easternspirit.org' -H 'Content-Type: application/x-www-form-urlencoded' -H 'Cookie: ips4_guestTime=1634150322; ips4_IPSSessionFront=2f4334b316a3a01ae9a9d035ad226848' --data-raw 'csrfKey=d4ff2f30e68f41e18d5f8bdd9f65f41e&auth=user&password=password&remember_me=1&_processLogin=usernamepassword' -c $ckfile");
		}

		$subs = array();
		$title_match = str_replace(' ', '-', str_replace('’', '%E2%80%99', $title));
		$title = str_replace(' ', '%20', $title);
		$searchUrl = "http://www.easternspirit.org/forum/index.php?/search/&quick=1&type=downloads_file&q=$title";
		$html = $this->httpRequestCF($ckfile, $searchUrl, $searchUrl);
		$html = str_get_html($html);
		if (empty($html))
			return false;
//find key
		foreach($html->find('li[class=ipsMenu_item ipsMenu_itemChecked]') as $key)
		{
			if (!preg_match('/.*csrfKey=[\s\S]*?".*/i', $key))
				continue;
			$key = preg_replace('/.*csrfKey=([\s\S]*?)".*/', '$1', $key);

			if (!preg_match('/S\d{2}E\d{2}$/i', $title))	//checks if the title is not a serie, then it's a movie
			{
//find link
				foreach($html->find('a') as $element)
				{
					if (!preg_match('/(http:\/\/www\.easternspirit\.org\/forum\/index\.php\?\/files\/file\/.*\/)\'>/i', $element))
						continue;
					if (!preg_match("/$title_match/i", $element->href))
						continue;
					$html = file_get_html($element->href);
					$link = $element->href.'&do=download&csrfKey='.$key;

					$aFilesInArchive = $this->getSubFilesFromArchiveCF($ckfile, $link);
					foreach($aFilesInArchive as $title)
					{
						$subs[$link . '&' . mt_rand()] = $title;
					}
				}
			}
			else//serie
			{
				foreach($html->find('a') as $element)
				{
					if (!preg_match('/(http:\/\/www\.easternspirit\.org\/forum\/index\.php\?\/files\/file\/.*\/)\'>/i', $element))
						continue;
					$title_match1 = preg_replace('/(.*)---S\d+E\d+/', '$1', $title_match);
					if (!preg_match("/$title_match1/i", $element->href))
						continue;
					$html = file_get_html($element->href);
					$link = $element->href.'&do=download&csrfKey='.$key;
					$html = $this->httpRequestCF($ckfile, $link); //would be great to change it with file_get_html not to download the page and delete it, but probably fix in the html dom is needed. 
					if (substr($html, 0, 2) == '<!')	//if page, goto page
					{
						goto page;
					}
					elseif (substr($html, 0, 2) == 'PK' || 'Ra' || '7z')	//if subs load
					{
						$aFilesInArchive = $this->getSubFilesFromArchiveCF($ckfile, $link);
						foreach($aFilesInArchive as $title)
						{
							$subs[$link . '&' . mt_rand()] = $title;
							goto finish;	//go to end of function
						}
					}
					page:
					$html = str_get_html($html);	//if page, read and load
					
					foreach($html->find('li[class=ipsDataItem]') as $serie)
					{
						$name = preg_replace('/(?:.|_|\s|s\d+)(?:ep?|episode(?:\s?|.))(\d+)/i', ' Ep$1', $serie);	//rename str element to match only Ep\d+
						$title_match2 = preg_replace('/.*---S\d+E(\d+)/i', ' Ep$1', $title_match);	//rename title to match only Ep\d+
						
						if (!preg_match("/$title_match2/i", $name)) //match both
							continue;
						$link = preg_replace('/&amp;/', '&', preg_replace('/.*href=\'([\s\S]*?)\'.*/', '$1', $serie));	//find href link
						//$link = preg_replace('/&amp;/', '&', $link);
						//unlink($this->tmpfile);	//del temp page before archive
						
						$aFilesInArchive = $this->getSubFilesFromArchiveCF($ckfile, $link);
						foreach($aFilesInArchive as $title)
						{
							$subs[$link . '&' . mt_rand()] = $title;
						}
					}	
				}
			}
		}
		
		finish:
		if (empty($subs) && filemtime($this->tmpfile) < (time() - ($this->hours * 60 * 60)))
		{
			unlink($this->tmpfile);
			return $this->searchSubsESpirit($title);
		}

		return $this->jsonForMovian('easternspirit.org', $subs);
	}
}

?>
