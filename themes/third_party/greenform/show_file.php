<?php
/**
 * Copyright GreenImp Web - greenimp.co.uk
 * This page cannot be used without permission
 * 
 * Author: GreenImp Web
 * Date Created: 10/06/12 19:18
 */

// use __FILE__, rather than __DIR__, to ensure compatibility with older servers
define('UPLOAD_DIR', rtrim(dirname(__FILE__), '/') . '/files/');

/**
 * Takes a filename and size and outputs the
 * relevant headers to force the file to be
 * downloaded, rather than viewed in teh browser
 *
 * @param $strFilename
 * @param $intSize
 * @return void
 */
function getDownloadHeaders($strFilename, $intSize){
	$arrHeaders = array(
		'Content-Type: application/force-download',
		'Content-Disposition: attachment; filename="%1$s"',
		'Content-length: %2$d'
	);

	foreach($arrHeaders as $header){
		header(sprintf($header, $strFilename, $intSize));
	}
}


if(!isset($_GET['file']) || !is_string($_GET['file']) || empty($_GET['file'])){
	// no file has been specified - return a 403
	header('HTTP/1.1 403 Forbidden');
}else{
	// a file has been specified - ensure that it is just a file name
	$strFilename = trim(basename($_GET['file']));
	$strFullPath = UPLOAD_DIR . $strFilename;

	if(0 === strpos($strFilename, '.')){
		// the user is trying to access a hidden file (ie; .htaccess, .htpassword etc)
	}elseif(strtolower($strFilename) == 'index.html'){
		// the user is trying to access the index.html page
	}elseif(strtolower($strFilename) == 'tmp'){
		// the user is trying to access the temp directory
	}elseif(false !== strpos($strFilename, '..')){
		// the user is trying to go back a directory
	}elseif(preg_match('/((\.\.?)|~)\/', $strFilename)){
		// the user is trying to access below the current directory
	}elseif(!file_exists($strFullPath) || is_dir($strFullPath)){
		// the file doesn't exist or is a directory
	}else{
		// the file appears okay, let's output it

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$fileMime = $finfo->file($strFullPath);		// the Mime type of the file
		$arrMimeParts = explode('/', $fileMime);	// get the two mime parts

		switch($arrMimeParts[0]){
			case 'image':
				// the file is an image
				switch($arrMimeParts[1]){
					case 'jpg':
					case 'jpeg':
					case 'pjpeg':
						// file is a JPG
						$arrMimeParts[1] = 'jpeg';						// normalize the mime type
						$strContentType = 'image/jpeg';					// define the header content type
						$resImage = @imagecreatefromjpeg($strFullPath);	// create the image
					break;
					case 'gif':
						// file is a GIF
						$strContentType = 'image/gif';					// define the header content type
						$resImage = @imagecreatefromgif($strFullPath);	// create the image
					break;
					case 'png':
						// file is a PNG
						$strContentType = 'image/png';					// define the header content type
						$resImage = @imagecreatefrompng($strFullPath);	// create the image
					break;
					default:
						// image type is un-known
						$resImage = false;
					break;
				}

				if(false !== $resImage){
					// the image was successfully created
					// output the header
					header('Content-Type: ' . $strContentType);
					// output the image (calling the relevant image function)
					// add the quality for all calls, if the image type doesn't use a quality it will be ignored
					call_user_func('image' . $arrMimeParts[1], $resImage, null, ($arrMimeParts[1] == 'png') ? 9 : 100);
					// destroy the image resource
					imagedestroy($resImage);
				}else{
					// error creating image - it could be that the type wasn't recognised
					// this could happen for files such a PSDs - instead, we will force download
					getDownloadHeaders($strFilename, filesize($strFullPath));
					readfile($strFullPath);
				}
			break;
			case 'text':
				// the file is text - output all text types as plain
				// this forces it all to be displayed as-is.
				// Any files, such as html, will display the code, rather than render it
				header('Content-Type: text/plain');
				readfile($strFullPath);
			break;
			default:
				// the mime type is not recognised or does
				// not require specific editing - force download
				getDownloadHeaders($strFilename, filesize($strFullPath));
				readfile($strFullPath);
			break;
		}
		exit;
	}

	// we have reached the end - there must have been an error or file not found
	// return a 404
	header('HTTP/1.0 404 Not Found');
}
?>