<?php
require_once dirname(__FILE__).'/../Base/ClassLoader.php';
require_once dirname(__FILE__).'/../OoweeConfig.php';
require_once $GLOBALS['ooweeConfig']['sitesPath'].'/SitesConfig.php';

class SiteEngine_SiteManager {

	public static function urlToSite($url) {
		global $Oowee_siteAliases;
		$matchingSite = false;
		$baseUrl = false;
		$urlParts = explode('/', $url);
		$numUrlParts = count($urlParts);
		if ($numUrlParts == 0) return $matchingSite;
		$urlPartsFirstIndex = 0;
		if (strcmp($urlParts[$urlPartsFirstIndex], 'http:') == 0 || strcmp($urlParts[$urlPartsFirstIndex], 'https:') == 0) $urlPartsFirstIndex++;
		if ($urlPartsFirstIndex >= $numUrlParts) return $matchingSite;
		if (strcmp($urlParts[$urlPartsFirstIndex], '') == 0) $urlPartsFirstIndex++;
		if ($urlPartsFirstIndex >= $numUrlParts) return $matchingSite;
		if (strcmp($urlParts[$numUrlParts - 1], '') == 0) $numUrlParts--;
		if ($numUrlParts <= 0) return $matchingSite;
		$numUrlParts -= $urlPartsFirstIndex;
		foreach($Oowee_siteAliases as $siteAlias => $siteName) {
			$aliasParts = explode('/', $siteAlias);
			$numAliasParts = count($aliasParts);
			$aliasPartsFirstIndex = 0;
			if (strcmp($aliasParts[$aliasPartsFirstIndex], 'http:') == 0 || strcmp($aliasParts[$aliasPartsFirstIndex], 'https:') == 0) $aliasPartsFirstIndex++;
			if ($aliasPartsFirstIndex >= $numAliasParts) continue;
			if (strcmp($aliasParts[$aliasPartsFirstIndex], '') == 0) $aliasPartsFirstIndex++;
			if ($aliasPartsFirstIndex >= $numAliasParts) continue;
			if (strcmp($aliasParts[$numAliasParts - 1], '') == 0) $numAliasParts--;
			if ($numAliasParts <= 0) continue;
			$numAliasParts -= $aliasPartsFirstIndex;
			if ($numAliasParts <= $numUrlParts) {
				for ($i = 0; $i < $numAliasParts; $i++) {
					if (strcmp($urlParts[$urlPartsFirstIndex + $i], $aliasParts[$aliasPartsFirstIndex + $i]) != 0) break;
				}
				if ($i == $numAliasParts) {
					$matchingSite = $siteName;
					$baseUrl = $siteAlias;
				}
			}
		}
		return $matchingSite === false ? false : array('siteName' => $matchingSite, 'baseUrl' => $baseUrl);
	}
	
	public static function getSiteFromUrl($url) {
		$siteName = self::urlToSite($url);
		if ($siteName === false) return false;
		$site = new SiteEngine_Site($siteName['siteName'], $siteName['baseUrl']);
		return $site->isLoaded() ? $site : false;
	}
	
	public static function getDefaultUnknownSiteDoc() {
		global $ooweeConfig;
		return $ooweeConfig['defaultSiteParams']['error404Page'];
	}
}

?>
