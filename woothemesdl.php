<?php
/*
Script Name: WooThemes Downloader
Description: This script logs into the WooThemes site as it was configured for 2011 and downloads all themes you have access to.
Version: 0.1
Author: BrianLayman
Author URI: http://thecodecave.com

Notes: 
	To use this script you must provide valid credentials for YOUR subscription.
	Don't abuse this tool. You are downloading the themes under your own login and it is traceable to you.
	If you run this to often I would expect them to terminate your account...
	Note that this script requires a directory named themezips in the execution directory.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if ( file_exists( 'woothemesdl_config.php' ) ) include( 'woothemesdl_config.php' );

if ( !defined('WT_USER_NAME' ) ) define( 'WT_USER_NAME', 'YourUserName' );
if ( !defined('WT_USER_PASSWORD' ) ) define( 'WT_USER_PASSWORD', 'AndPassword' );

class wooThemesApi {
	// Hold an instance of the class
	private static $m_pInstance;
	private static $curl;
	private static $ckfile;

	// A private constructor; prevents direct creation of object
	private function __construct() {
		$this->ckfile = tempnam( "/tmp", "cookie_wooThemes_" );
		$this->curl = curl_init();	   
	}

	public static function getInstance() { 
		if ( !self::$m_pInstance ) { 
			self::$m_pInstance = new wooThemesApi(); 
		} 

		return self::$m_pInstance; 
	} 
	

	function _authorize( $login, $password ) {
		$url = "http://www.woothemes.com/wm-login.php";
		curl_setopt( $this->curl, CURLOPT_URL, $url );
		curl_setopt( $this->curl, CURLOPT_COOKIEJAR, $this->ckfile );
		curl_setopt( $this->curl, CURLOPT_POST, true );
		curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $this->curl, CURLOPT_FOLLOWLOCATION, true ); 
		curl_setopt( $this->curl, CURLOPT_POSTFIELDS, "submit=Login&username=" . urlencode( $login ) . "&password=" . urlencode( $password ) . "&remember=on&redirect_to=/products" );
		curl_setopt( $this->curl, CURLOPT_USERAGENT, "botd Mozilla/4.0 ( Compatible; WooThemes Auth API )" );
		$result = curl_exec( $this->curl );
		return $result;
	}

	function get_themes( $text ) {
		$themes = array();
		$end = 0;
		while ( $start = strpos( $text, "http://www.woothemes.com/woomember/download/theme/", $end ) ) {;
			$end = strpos( $text, '"', $start ); 
			$url = substr( $text, $start, $end - $start ); 
			$themes[] = $url;
		}
		return $themes;
	}
	
	function get_theme_file( $url ) {
		$theFileName =  basename( $url ); // goes boom if there are parameters
		$fp = fopen ( dirname( __FILE__ ) . '/themezips/' . $theFileName , 'w+' );//This is the file where we save the information
		curl_setopt( $this->curl, CURLOPT_URL, $url );
		curl_setopt( $this->curl, CURLOPT_COOKIEJAR, $this->ckfile );
		curl_setopt( $this->curl, CURLOPT_POST, false );
		curl_setopt( $this->curl, CURLOPT_POSTFIELDS, "" );
 		curl_setopt( $this->curl, CURLOPT_TIMEOUT, 50 );
		curl_setopt( $this->curl, CURLOPT_FILE, $fp );
		curl_setopt( $this->curl, CURLOPT_FOLLOWLOCATION, true );
		curl_exec( $this->curl );
		fclose( $fp );
		ob_get_clean();
		flush();
		echo $theFileName . " is done<br/>";
	}
	
}

$pwooThemesAPI = wooThemesApi::getinstance();
$pageInfo = $pwooThemesAPI->_authorize( WT_USER_NAME, WT_USER_PASSWORD );
$themes = $pwooThemesAPI->get_themes( $pageInfo );

foreach ( $themes as $themeurl ) 
	$pwooThemesAPI->get_theme_file( $themeurl );