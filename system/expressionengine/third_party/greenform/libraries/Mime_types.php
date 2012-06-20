<?php
/**
 * Author: Lee Langley
 * Date Created: 20/04/2012 11:55
 */
 
class Mime_types{
	private $DIR = '';
	private $mimeTypes = array();

	public function __construct(){
		$this->DIR = defined('__DIR__') ? __DIR__ . '/' : dirname(__FILE__);

		$this->getMimeTypes();
	}

	/**
	 * Returns a list of extension => Mime type pairs
	 *
	 * @return array
	 */
	public function getMimeTypes(){
		if(count($this->mimeTypes) == 0){
			// no mime types found - fetch them
			$this->readXML();
		}

		return $this->mimeTypes;
	}

	/**
	 * Takes a mime type and returns an array of
	 * extensions available for it.
	 * If no extensions are found an empty array is returned
	 *
	 * @param $strMime
	 * @return array
	 */
	public function mimeToExtension($strMime){
		// split the mime type to get the two parts
		list($mimePart1, $mimePart2) = explode('/', $strMime);

		$arrExtensions = array();

		// loop through the extension => mime type array and check if they match
		foreach($this->getMimeTypes() as $strExt => $arrMimes){
			if(in_array($strMime, $arrMimes)){
				// the mime type is in the array - return the given extension
				$arrExtensions[] = $strExt;
			}elseif($mimePart2 == '*'){
				// the second part of the mime type is a wildcard
				// loop through the mime types for the extension and
				// see if the first part matches
				foreach($arrMimes as $mimeType){
					list($part1) = explode('/', $mimeType);
					if($part1 == $mimePart1){
						// the first parts both match - return the extension
						$arrExtensions[] = $strExt;
						break;
					}
				}
			}
		}

		return $arrExtensions;
	}

	/**
	 * Takes a file extension and returns a list of all the
	 * mime types that could possibly have that file extension
	 *
	 * @param string $strExtension
	 * @return array
	 */
	public function extensionToMime($strExtension){
		$mimeTypes = $this->getMimeTypes();
		$strExtension = trim($strExtension, '.');

		return isset($mimeTypes[$strExtension]) ? $mimeTypes[$strExtension] : array();
	}



	/******
	 * Private helper methods for reading/writing the XML file
	 ******/
	/**
	 * Collects the data and writes it to the XML file for reading
	 *
	 * @return bool
	 */
	private function writeXML(){
		$mappings = $this->getDataFromFiles();

		if(count($mappings) > 0){
			$xml = new SimpleXMLElement('<mimetypes></mimetypes>');

			foreach($mappings as $strExt => $arrMimes){
				$objMap = $xml->addChild('file');
				$objExt = $objMap->addChild('ext', trim($strExt));
				foreach($arrMimes as $strMime){
					$objMime = $objMap->addChild('mimetype', trim($strMime));
				}
			}

			// format XML to save indented tree rather than one line
			$dom = new DOMDocument('1.0');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$dom->loadXML($xml->asXML());
			return $dom->save($this->DIR . '/mimetypes.xml');
		}

		return false;
	}

	/**
	 * Reads the mimetype XML file and returns a list of extension => Mime type mappings
	 *
	 * @return array
	 */
	private function readXML(){
		if(!file_exists($this->DIR . '/mimetypes.xml')){
			$this->writeXML();
		}

		$xml = simplexml_load_file($this->DIR . '/mimetypes.xml');
		foreach($xml->file as $file){
			$extension = trim(ltrim((string) $file->ext, '.'));

			// loop through each mime type for this extension and add it to the list
			foreach($file->mimetype as $mime){
				$mime = trim((string) $mime);
				if(!isset($this->mimeTypes[$extension]) || !in_array($mime, $this->mimeTypes[$extension])){
					// the mime type hasn't been added for this extension yet
					$this->mimeTypes[$extension][] = $mime;
				}
			}
		}

		return $this->mimeTypes;
	}

	/**
	 * Collects all of the extension => Mime type mappings from
	 * the files inside the mime_data folder
	 *
	 * @return array
	 */
	private function getDataFromFiles(){
		$strDataFolder = $this->DIR . '/mime_data/';

		// get contents from JSON file
		$strFile = file_get_contents($strDataFolder . 'mimetypes.txt');
		foreach(json_decode($strFile, true) as $mimeType){
			$extension = trim(ltrim(reset(array_keys($mimeType)), '.'));
			if(!isset($this->mimeTypes[$extension]) || !in_array(reset($mimeType), $this->mimeTypes[$extension])){
				$this->mimeTypes[$extension][] = trim(reset($mimeType));
			}
		}

		// get contents from XML
		$xml = simplexml_load_file($strDataFolder . 'mimetypes.xml');
		foreach($xml->file as $file){
			$extension = trim(ltrim((string) $file->ext, '.'));
			if(!isset($this->mimeTypes[$extension]) || !in_array((string) $file->mimetype, $this->mimeTypes[$extension])){
				$this->mimeTypes[$extension][] = trim((string) $file->mimetype);
			}
		}

		// get contents from table HTML
		$strFile = file_get_contents($strDataFolder . 'mimetypes_table.txt');
		if(preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $strFile, $matches)){
			foreach($matches[1] as $strHTML){
				if(preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $strHTML, $valueMatches)){
					$extension = trim(ltrim($valueMatches[1][0], '.'));
					if(!isset($this->mimeTypes[$extension]) || !in_array($valueMatches[1][1], $this->mimeTypes[$extension])){
						$this->mimeTypes[$extension][] = trim($valueMatches[1][1]);
					}
				}
			}
		}

		// get contents from table HTML
		$strFile = file_get_contents($strDataFolder . 'mimetypes_table2.txt');
		if(preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $strFile, $matches)){
			foreach($matches[1] as $strHTML){
				if(preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $strHTML, $valueMatches)){
					$extension = trim(ltrim($valueMatches[1][0], '.'));
					if(!isset($this->mimeTypes[$extension]) || !in_array($valueMatches[1][1], $this->mimeTypes[$extension])){
						$this->mimeTypes[$extension][] = trim($valueMatches[1][1]);
					}
				}
			}
		}

		// get contents from table HTML
		$strFile = file_get_contents($strDataFolder . 'mimetypes_table3.txt');
		if(preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $strFile, $matches)){
			foreach($matches[1] as $strHTML){
				if(preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $strHTML, $valueMatches)){
					$extension = trim(ltrim($valueMatches[1][0], '.'));
					if(!isset($this->mimeTypes[$extension]) || !in_array($valueMatches[1][1], $this->mimeTypes[$extension])){
						$this->mimeTypes[$extension][] = trim($valueMatches[1][1]);
					}
				}
			}
		}

		return $this->mimeTypes;
	}
}
?>