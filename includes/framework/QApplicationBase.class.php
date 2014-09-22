<?php
	/**
	 * This abstract class should never be instantiated.  It contains static methods,
	 * variables and constants to be used throughout the application.
	 *
	 * The static method "Initialize" should be called at the begin of the script by
	 * prepend.inc.
	 */
	abstract class QApplicationBase extends QBaseClass {
		//////////////////////////
		// Public Static Variables
		//////////////////////////

		/**
		 * The cache provider object used for caching ORM objects
		 * It is initialized below in Initialize(), based on the CACHE_PROVIDER and CACHE_PROVIDER_OPTIONS
		 * variables defined in configuration.inc.php
		 *
		 * @var QAbstractCacheProvider 
		 */
		public static $objCacheProvider = null;

		/**
		 * Internal bitmask signifying which BrowserType the user is using
		 * Use the QApplication::IsBrowser() method to do browser checking
		 *
		 * @var integer BrowserType
		 */
		protected static $BrowserType = QBrowserType::Unsupported;

		/**
		 * @var float Major version number of browser
		 */
		public static $BrowserVersion = null;

		/**
		 * Definition of CacheControl for the HTTP header.  In general, it is
		 * recommended to keep this as "private".  But this can/should be overriden
		 * for file/scripts that have special caching requirements (e.g. dynamically
		 * created images like QImageLabel).
		 *
		 * @var string CacheControl
		 */
		public static $CacheControl = 'private';

        /**
         * @var #P#C\QCrossScripting.Purify|?
         * Defines the default mode for controls that need protection against
         * cross-site scripting. Can be overridden at the individual control level,
         * or for all controls by overriding it in QApplication.
         *
         * Set to QCrossScripting::Legacy for backward compatibility reasons for legacy applications;
         * For new applications the recommended setting is QCrossScripting::Purify.
         */
        public static $DefaultCrossScriptingMode = QCrossScripting::Legacy;

		/**
		 * Path of the "web root" or "document root" of the web server
		 * Like "/home/www/htdocs" on Linux/Unix or "c:\inetpub\wwwroot" on Windows
		 *
		 * @var string DocumentRoot
		 */
		public static $DocumentRoot;

		/**
		 * Whether or not we are currently trying to Process the Output of the page.
		 * Used by the OutputPage PHP output_buffering handler.  As of PHP 5.2,
		 * this gets called whenever ob_get_contents() is called.  Because some
		 * classes like QFormBase utilizes ob_get_contents() to perform template
		 * evaluation without wanting to actually perform OutputPage, this flag
		 * can be set/modified by QFormBase::EvaluateTemplate accordingly to
		 * prevent OutputPage from executing.
		 *
		 * @var boolean ProcessOutput
		 */
		public static $ProcessOutput = true;

		/**
		 * Full path of the actual PHP script being run
		 * Like "/home/www/htdocs/folder/script.php" on Linux/Unix
		 * or "c:\inetpub\wwwroot" on Windows
		 *
		 * @var string ScriptFilename
		 */
		public static $ScriptFilename;

		/**
		 * Web-relative path of the actual PHP script being run
		 * So for "http://www.domain.com/folder/script.php",
		 * QApplication::$ScriptName would be "/folder/script.php"
		 *
		 * @var string ScriptName
		 */
		public static $ScriptName;

		/**
		 * Extended Path Information after the script URL (if applicable)
		 * So for "http://www.domain.com/folder/script.php/15/225"
		 * QApplication::$PathInfo would be "/15/255"
		 *
		 * @var string PathInfo
		 */
		public static $PathInfo;

		/**
		 * Query String after the script URL (if applicable)
		 * So for "http://www.domain.com/folder/script.php?item=15&value=22"
		 * QApplication::$QueryString would be "item=15&value=22"
		 *
		 * @var string QueryString
		 */
		public static $QueryString;

		/**
		 * The full Request URI that was requested
		 * So for "http://www.domain.com/folder/script.php/15/25/?item=15&value=22"
		 * QApplication::$RequestUri would be "/folder/script.php/15/25/?item=15&value=22"
		 *
		 * @var string RequestUri
		 */
		public static $RequestUri;

		/**
		 * The IP address of the server running the script/PHP application
		 * This is either the LOCAL_ADDR or the SERVER_ADDR server constant, depending
		 * on the server type, OS and configuration.
		 *
		 * @var string ServerAddress
		 */
		public static $ServerAddress;

		/**
		 * The encoding type for the application (e.g. UTF-8, ISO-8859-1, etc.)
		 *
		 * @var string EncodingType
		 */
		public static $EncodingType = "UTF-8";

		/**
		 * An array of Database objects, as initialized by QApplication::InitializeDatabaseConnections()
		 *
		 * @var QDatabaseBase[] Database
		 */
		public static $Database;

		/**
		 * A flag to indicate whether or not this script is run as a CLI (Command Line Interface)
		 *
		 * @var boolean CliMode
		 */
		public static $CliMode;

		/**
		 * Class File Array - used by QApplication::AutoLoad to more quickly load
		 * core class objects without making a file_exists call.
		 *
		 * @var array ClassFile
		 */
		public static $ClassFile;

		/**
		 * Preloaded Class File Array - used by QApplication::Initialize to load
		 * any core class objects during Initailize()
		 *
		 * @var array ClassFile
		 */
		public static $PreloadedClassFile;

		/**
		 * The QRequestMode enumerated value for the current request mode
		 *
		 * @var string RequestMode
		 */
		public static $RequestMode;

		/**
		 * 2-letter country code to set for internationalization and localization
		 * (e.g. us, uk, jp)
		 *
		 * @var string CountryCode
		 */
		public static $CountryCode;

		/**
		 * 2-letter language code to set for internationalization and localization
		 * (e.g. en, jp, etc.)
		 *
		 * @var string LanguageCode
		 */
		public static $LanguageCode;

		/**
		 * The instance of the active QI18n object (which contains translation strings), if any.
		 *
		 * @var QTranslationBase $LanguageObject
		 */
		public static $LanguageObject;

		////////////////////////
		// Public Overrides
		////////////////////////
		/**
		 * This faux constructor method throws a caller exception.
		 * The Application object should never be instantiated, and this constructor
		 * override simply guarantees it.
		 *
		 * @return \QApplicationBase
		 */
		public final function __construct() {
			throw new QCallerException('Application should never be instantiated.  All methods and variables are publically statically accessible.');
		}


		////////////////////////
		// Public Static Methods
		////////////////////////

		/**
		 * This should be the first call to initialize all the static variables
		 * The application object also has static methods that are miscellaneous web
		 * development utilities, etc.
		 *
		 * @return void
		 */
		public static function Initialize() {
			self::$EncodingType = defined('__QAPPLICATION_ENCODING_TYPE__') ? __QAPPLICATION_ENCODING_TYPE__ : self::$EncodingType;

			$strCacheProviderClass = 'QCacheProviderNoCache';
			if (defined('CACHE_PROVIDER_CLASS')) {
				$strCacheProviderClass = CACHE_PROVIDER_CLASS;
			}
			if ($strCacheProviderClass) {
				if (defined('CACHE_PROVIDER_OPTIONS')) {
					QApplicationBase::$objCacheProvider = new $strCacheProviderClass(unserialize(CACHE_PROVIDER_OPTIONS));
				} else {
					QApplicationBase::$objCacheProvider = new $strCacheProviderClass();
				}
			}

			// Are we running as CLI?
			if (PHP_SAPI == 'cli')
				QApplication::$CliMode = true;
			else
				QApplication::$CliMode = false;

			// Setup Server Address
			if (array_key_exists('LOCAL_ADDR', $_SERVER))
				QApplication::$ServerAddress = $_SERVER['LOCAL_ADDR'];
			else if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
				QApplication::$ServerAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
			else if (array_key_exists('SERVER_ADDR', $_SERVER))
				QApplication::$ServerAddress = $_SERVER['SERVER_ADDR'];

			// Setup ScriptFilename and ScriptName
			QApplication::$ScriptFilename = $_SERVER['SCRIPT_FILENAME'];
			QApplication::$ScriptName = $_SERVER['SCRIPT_NAME'];
			
			// Ensure both are set, or we'll have to abort
			if ((!QApplication::$ScriptFilename) || (!QApplication::$ScriptName)) {
				throw new Exception('Error on QApplication::Initialize() - ScriptFilename or ScriptName was not set');
			}

			// Setup PathInfo and QueryString (if applicable)
			QApplication::$PathInfo = null;
			if(array_key_exists('PATH_INFO', $_SERVER)) {
				QApplication::$PathInfo = urlencode(trim($_SERVER['PATH_INFO']));
				QApplication::$PathInfo = str_ireplace('%2f', '/', QApplication::$PathInfo);
			}
			QApplication::$QueryString = array_key_exists('QUERY_STRING', $_SERVER) ? $_SERVER['QUERY_STRING'] : null;

			// Setup RequestUri
			if (defined('__URL_REWRITE__')) {
				switch (strtolower(__URL_REWRITE__)) {
					case 'apache':
						QApplication::$RequestUri = $_SERVER['REQUEST_URI'];
						break;

					case 'none':
						QApplication::$RequestUri = sprintf('%s%s%s',
							QApplication::$ScriptName, QApplication::$PathInfo,
							(QApplication::$QueryString) ? sprintf('?%s', QApplication::$QueryString) : null);
						break;

					default:
						throw new Exception('Invalid URL Rewrite type: ' . __URL_REWRITE__);
				}
			} else {
				QApplication::$RequestUri = sprintf('%s%s%s',
					QApplication::$ScriptName, QApplication::$PathInfo,
					(QApplication::$QueryString) ? sprintf('?%s', QApplication::$QueryString) : null);
			}

			// Setup DocumentRoot
			QApplication::$DocumentRoot = trim(__DOCROOT__);

			// Setup Browser Type
			if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
				$strUserAgent = trim(strtolower($_SERVER['HTTP_USER_AGENT']));

				QApplication::$BrowserType = 0;

				// INTERNET EXPLORER (versions 6 through 10)
				if (strpos($strUserAgent, 'msie') !== false) {
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::InternetExplorer;

					// just major version number. Will not see IE 10.6.
					$matches = array();
					preg_match ('#msie\s(.\d)#', $strUserAgent, $matches);
					if ($matches) {
						QApplication::$BrowserVersion = (int)$matches[1];
					}
				}
				else if (strpos($strUserAgent, 'trident') !== false) {
					// IE 11 significantly changes the user agent, and no longer includes 'MSIE'
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::InternetExplorer;

					$matches = array();
					preg_match ('/rv:(.+)\)/', $strUserAgent, $matches);
					if ($matches) {
						QApplication::$BrowserVersion = (float)$matches[1];
					}
				// FIREFOX
				} else if ((strpos($strUserAgent, 'firefox') !== false) || (strpos($strUserAgent, 'iceweasel') !== false)) {
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::Firefox;
					$strUserAgent = str_replace('iceweasel/', 'firefox/', $strUserAgent);

					$matches = array();
					preg_match ('#firefox/(.+)#', $strUserAgent, $matches);
					if ($matches) {
						QApplication::$BrowserVersion = (float)$matches[1];
					}
				}
				// CHROME, must come before safari because it also includes a safari string
				elseif (strpos($strUserAgent, 'chrome') !== false) {
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::Chrome;

					// find major version number only
					$matches = array();
					preg_match ('#chrome/(\d+)#', $strUserAgent, $matches);
					if ($matches) {
						QApplication::$BrowserVersion = (int)$matches[1];
					}
				}
				// SAFARI
				elseif (strpos($strUserAgent, 'safari') !== false) {
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::Safari;

					$matches = array();
					preg_match ('#version/(.+)\s#', $strUserAgent, $matches);
					if ($matches) {
						QApplication::$BrowserVersion = (float)$matches[1];
					}
				}
				// KONQUEROR
				elseif (strpos($strUserAgent, 'konqueror') !== false) {
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::Konqueror;

					// only looking at major version number on this one
					$matches = array();
					preg_match ('#konqueror/(\d+)#', $strUserAgent, $matches);
					if ($matches) {
						QApplication::$BrowserVersion = (int)$matches[1];
					}
				}

				// OPERA
				elseif (strpos($strUserAgent, 'opera') !== false) {
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::Opera;

					// two different patterns;
					$matches = array();
					preg_match ('#version/(\d+)#', $strUserAgent, $matches);
					if ($matches) {
						QApplication::$BrowserVersion = (int)$matches[1];
					} else {
						preg_match ('#opera\s(.+)#', $strUserAgent, $matches);
						if ($matches) {
							QApplication::$BrowserVersion = (float)$matches[1];
						}
					}
				}

				// Unknown
				if (QApplication::$BrowserType == 0)
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::Unsupported;

				// OS (supporting Windows, Linux and Mac)
				if (strpos($strUserAgent, 'windows') !== false)
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::Windows;
				elseif (strpos($strUserAgent, 'linux') !== false)
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::Linux;
				elseif (strpos($strUserAgent, 'macintosh') !== false)
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::Macintosh;

				// Mobile version of one of the above browsers, or some other unknown browser
				if (strpos($strUserAgent, 'mobi') !== false) // opera is just 'mobi', everyone else uses 'mobile'
					QApplication::$BrowserType = QApplication::$BrowserType | QBrowserType::Mobile;
			}

			// Preload Class Files
			foreach (QApplication::$PreloadedClassFile as $strClassFile)
				require($strClassFile);
		}

		/**
		 * Checks for the type of browser in use by the client.
		 * @static
		 * @param int $intBrowserType
		 * @return int
		 */
		public static function IsBrowser($intBrowserType) {
			return ($intBrowserType & QApplication::$BrowserType);
		}

		/**
		 * This call will initialize the database connection(s) as defined by
		 * the constants DB_CONNECTION_X, where "X" is the index number of a
		 * particular database connection.
		 *
		 * @return void
		 */
		public static function InitializeDatabaseConnections() {
			// for backward compatibility, don't use MAX_DB_CONNECTION_INDEX directly,
			// but check if MAX_DB_CONNECTION_INDEX is defined
			$intMaxIndex = defined('MAX_DB_CONNECTION_INDEX') ? constant('MAX_DB_CONNECTION_INDEX') : 9;
			for ($intIndex = 0; $intIndex <= $intMaxIndex; $intIndex++) {
				$strConstantName = sprintf('DB_CONNECTION_%s', $intIndex);

				if (defined($strConstantName)) {
					// Expected Keys to be Set
					$strExpectedKeys = array(
						'adapter', 'server', 'port', 'database',
						'username', 'password', 'profiling', 'dateformat'
					);

					// Lookup the Serialized Array from the DB_CONFIG constants and unserialize it
					$strSerialArray = constant($strConstantName);
					$objConfigArray = unserialize($strSerialArray);

					// Set All Expected Keys
					foreach ($strExpectedKeys as $strExpectedKey)
						if (!array_key_exists($strExpectedKey, $objConfigArray))
							$objConfigArray[$strExpectedKey] = null;

					if (!$objConfigArray['adapter'])
						throw new Exception('No Adapter Defined for ' . $strConstantName . ': ' . var_export($objConfigArray, true));

					if (!$objConfigArray['server'])
						throw new Exception('No Server Defined for ' . $strConstantName . ': ' . constant($strConstantName));

					$strDatabaseType = 'Q' . $objConfigArray['adapter'] . 'Database';
					if (!class_exists($strDatabaseType)) {
						$strDatabaseAdapter = sprintf('%s/database/%s.class.php', __QCUBED_CORE__, $strDatabaseType);
						if (!file_exists($strDatabaseAdapter))
							throw new Exception('Database Type is not valid: ' . $objConfigArray['adapter']);
						require($strDatabaseAdapter);
					}

					QApplication::$Database[$intIndex] = new $strDatabaseType($intIndex, $objConfigArray);
				}
			}
		}

		public static function SessionOverride() {
			// Are we using QDbBackedSessionHandler?
			if (defined("DB_BACKED_SESSION_HANDLER_DB_INDEX") &&
					constant("DB_BACKED_SESSION_HANDLER_DB_INDEX") != 0 && defined("DB_BACKED_SESSION_HANDLER_TABLE_NAME")) {
				// Yes we are going to override PHP's default file based handlers.
				QDbBackedSessionHandler::Initialize(DB_BACKED_SESSION_HANDLER_DB_INDEX, DB_BACKED_SESSION_HANDLER_TABLE_NAME);
			}
		}

		/**
		 * This is called by the PHP5 Autoloader.  This static method can be overridden.
		 *
		 * @param $strClassName
		 * @return boolean whether or not a class was found / included
		 */
		public static function Autoload($strClassName) {
			if (isset(QApplication::$ClassFile[strtolower($strClassName)])) {
				require_once (QApplication::$ClassFile[strtolower($strClassName)]);
				return true;
			} else if (file_exists($strFilePath = sprintf('%s/%s.class.php', __INCLUDES__, $strClassName))) {
				require_once ($strFilePath);
				return true;
			} else if (file_exists($strFilePath = sprintf('%s/controls/%s.class.php', __INCLUDES__, $strClassName))) {
				require_once ($strFilePath);
				return true;
			} else if (file_exists($strFilePath = sprintf('%s/plugin/%s.php', __INCLUDES__, $strClassName))) {
				require_once ($strFilePath);
				return true;
			} else if (false !== ($intStart = strpos($strClassName, 'QCubed\\Plugin\\'))) {
                $strClassName = substr($strClassName, $intStart + 15);
                if (file_exists($strFilePath = sprintf('%s/plugin/%s.php', __INCLUDES__, $strClassName))) {
				    require_once ($strFilePath);
				    return true;
				}
			}

			return false;
		}

		/**
		 * Temprorarily overrides the default error handling mechanism.  Remember to call
		 * RestoreErrorHandler to restore the error handler back to the default.
		 *
		 * @param string $strName the name of the new error handler function, or NULL if none
		 * @param integer $intLevel if a error handler function is defined, then the new error reporting level (if any)
		 */
		public static function SetErrorHandler($strName, $intLevel = null) {
			if (!is_null(QApplicationBase::$intStoredErrorLevel))
				throw new QCallerException('Error handler is already currently overridden.  Cannot override twice.  Call RestoreErrorHandler before calling SetErrorHandler again.');
			if (!$strName) {
				// No Error Handling is wanted -- simulate a "On Error, Resume" type of functionality
				set_error_handler('QcodoHandleError', 0);
				QApplicationBase::$intStoredErrorLevel = error_reporting(0);
			} else {
				set_error_handler($strName, $intLevel);
				QApplicationBase::$intStoredErrorLevel = -1;
			}
		}

		/**
		 * Restores the temporarily overridden default error handling mechanism back to the default.
		 */
		public static function RestoreErrorHandler() {
			if (is_null(QApplicationBase::$intStoredErrorLevel))
				throw new QCallerException('Error handler is not currently overridden.  Cannot reset something that was never overridden.');
			if (QApplicationBase::$intStoredErrorLevel != -1)
				error_reporting(QApplicationBase::$intStoredErrorLevel);
			restore_error_handler();
			QApplicationBase::$intStoredErrorLevel = null;
		}
		private static $intStoredErrorLevel = null;

		public static function MakeDirectory($strPath, $intMode = null) {
			return QFolder::MakeDirectory($strPath, $intMode);
		}


		/**
		 * This will redirect the user to a new web location.  This can be a relative or absolute web path, or it
		 * can be an entire URL.
		 *
		 * @param string $strLocation target patch
		 * @return void
		 */
		public static function Redirect($strLocation) {
			// Clear the output buffer (if any)
			ob_clean();

			if ((QApplication::$RequestMode == QRequestMode::Ajax) ||
				(array_key_exists('Qform__FormCallType', $_POST) &&
				($_POST['Qform__FormCallType'] == QCallType::Ajax))) {
				// AJAX-based Response

				// Response is in XML Format
				header('Content-Type: text/xml');

				// Output it and update render state
				$strLocation = 'document.location="' . $strLocation . '"';
				$strLocation = QString::XmlEscape($strLocation);
				print('<?xml version="1.0"?><response><controls/><commands><command>' . $strLocation . '</command></commands></response>');

			} else {
				// Was "DOCUMENT_ROOT" set?
				if (array_key_exists('DOCUMENT_ROOT', $_SERVER) && ($_SERVER['DOCUMENT_ROOT'])) {
					// If so, we're likley using PHP as a Plugin/Module
					// Use 'header' to redirect
					header(sprintf('Location: %s', $strLocation));
				} else {
					// We're likely using this as a CGI
					// Use JavaScript to redirect
					printf('<script type="text/javascript">document.location = "%s";</script>', $strLocation);
				}
			}

			// End the Response Script
			exit();
		}


		/**
		 * This will close the window.  It will immediately end processing of the rest of the script.
		 *
		 * @return void
		 */
		public static function CloseWindow() {
			// Clear the output buffer (if any)
			ob_clean();

			if (QApplication::$RequestMode == QRequestMode::Ajax) {
				// AJAX-based Response

				// Response is in XML Format
				header('Content-Type: text/xml');

				// OUtput it and update render state
				_p('<?xml version="1.0"?><response><controls/><commands><command>window.close();</command></commands></response>', false);

			} else {
				// Use JavaScript to close
				_p('<script type="text/javascript">window.close();</script>', false);
			}

			// End the Response Script
			exit();
		}

		/**
		 * Gets the value of the QueryString item $strItem.  Will return NULL if it doesn't exist.
		 *
		 * @param string $strItem the parameter name
		 *
		 * @return string value of the parameter
		 */
		public static function QueryString($strItem) {
			if (array_key_exists($strItem, $_GET))
				return $_GET[$strItem];
			else
				return null;
		}

		/**
		 * Generates a valid URL Query String based on values in the provided array. If no array is provided, it uses the global $_GET
		 * @param array $arr
		 * @return string
		 */
		public static function GenerateQueryString($arr = null) {
			if(null === $arr)
				$arr = $_GET;
			if (count($arr)) {
				$strToReturn = '';
				foreach ($arr as $strKey => $mixValue)
					$strToReturn .= QApplication::GenerateQueryStringHelper(urlencode($strKey), $mixValue);
				return '?' . substr($strToReturn, 1);
			} else
				return '';
		}

		protected static function GenerateQueryStringHelper($strKey, $mixValue) {
			if (is_array($mixValue)) {
				$strToReturn = null;
				foreach ($mixValue as $strSubKey => $mixValue) {
					$strToReturn .= QApplication::GenerateQueryStringHelper($strKey . '[' . $strSubKey . ']', $mixValue);
				}
				return $strToReturn;
			} else
				return '&' . $strKey . '=' . urlencode($mixValue);
		}

		/**
		 * By default, this is used by the codegen and form drafts to do a quick check
		 * on the ALLOW_REMOTE_ADMIN constant (as defined in configuration.inc.php).  If enabled,
		 * then anyone can access the page.  If disabled, only "localhost" can access the page.
		 * If you want to run a script that should be accessible regardless of
		 * ALLOW_REMOTE_ADMIN, simply remove the CheckRemoteAdmin() method call from that script.
		 * @return
		 */
		public static function CheckRemoteAdmin() {
			if (!QApplication::IsRemoteAdminSession()) {
				return;
			}

			// If we're here -- then we're not allowed to access.  Present the Error/Issue.
			header($_SERVER['SERVER_PROTOCOL'] . ' 401 Access Denied');
			header('Status: 401 Access Denied', true);

			throw new QRemoteAdminDeniedException();
		}

		/**
		 * Checks whether the current request was made by an ADMIN
		 * This does not refer to your Database admin or an Admin user defined in your application but an IP address
		 * (or IP address range) defined in configuration.inc.php.
		 *
		 * The function can be used to restrict access to sensitive pages to a list of IPs (or IP ranges), such as the LAN to which
		 * the server hosting the QCubed application is connected.
		 * @static
		 * @return bool
		 */
		public static function IsRemoteAdminSession() {
			// Allow Remote?
			if (ALLOW_REMOTE_ADMIN === true)
				return false;

			// Are we localhost?
			if (substr($_SERVER['REMOTE_ADDR'],0,4) == '127.' || $_SERVER['REMOTE_ADDR'] == '::1')
				return false;

			// Are we the correct IP?
			if (is_string(ALLOW_REMOTE_ADMIN))
				foreach (explode(',', ALLOW_REMOTE_ADMIN) as $strIpAddress) {
					if (QApplication::IsIPInRange($_SERVER['REMOTE_ADDR'], $strIpAddress) ||
						(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && (QApplication::IsIPInRange($_SERVER['HTTP_X_FORWARDED_FOR'], $strIpAddress)))) {
						return false;
					}
				}
					
			return true;
		}

		/**
		 * Checks whether the given IP falls into the given IP range
		 * @static
		 * @param string $ip the IP number to check
		 * @param string $range the IP number range. The range could be in 'IP/mask' or 'IP - IP' format. mask could be a simple
		 * integer or a dotted netmask.
		 * @return bool
		 */
		public static function IsIPInRange($ip, $range) {
			$ip = trim($ip);
			if (strpos($range, '/') !== false) {
				// we are given a IP/mask
				list($net, $mask) = explode('/', $range);
				$net = ip2long(trim($net));
				$mask = trim($mask);
				$ip_net = ip2long($net);
				if (strpos($mask, '.') !== false) {
					// mask has the dotted notation
					$ip_mask = ip2long($mask);
				} else {
					// mask is an integer
					$ip_mask = ~((1 << (32 - $mask)) - 1);
				}
				$ip = ip2long($ip);
				return ($net & $ip_mask) == ($ip & $ip_mask);
			}
			if (strpos($range, '-') !== false) {
				// we are given an IP - IP range
				list($first, $last) = explode('-', $range);
				$first = ip2long(trim($first));
				$last = ip2long(trim($last));
				$ip = ip2long($ip);
				return $first <= $ip && $ip <= $last;
			}

			// $range is a simple IP
			return $ip == trim($range);
		}

		/**
		 * Gets the value of the PathInfo item at index $intIndex.  Will return NULL if it doesn't exist.
		 *
		 * The way PathInfo index is determined is, for example, given a URL '/folder/page.php/id/15/blue',
		 * QApplication::PathInfo(0) will return 'id'
		 * QApplication::PathInfo(1) will return '15'
		 * QApplication::PathInfo(2) will return 'blue'
		 *
		 * @param int $intIndex index
		 * @return string|null
		 */
		public static function PathInfo($intIndex) {
			// TODO: Cache PathInfo
			$strPathInfo = urldecode(QApplication::$PathInfo);
			
			// Remove Starting '/'
			if (QString::FirstCharacter($strPathInfo) == '/')			
				$strPathInfo = substr($strPathInfo, 1);
			
			$strPathInfoArray = explode('/', $strPathInfo);

			if (array_key_exists($intIndex, $strPathInfoArray))
				return $strPathInfoArray[$intIndex];
			else
				return null;
		}
		
		public static $AlertMessageArray = array();
		public static $JavaScriptArray = array();
		public static $JavaScriptArrayHighPriority = array();
		public static $JavaScriptArrayLowPriority = array();

		public static $ErrorFlag = false;
		
		public static function DisplayAlert($strMessage) {
			array_push(QApplication::$AlertMessageArray, $strMessage);
		}

		/**
		 * Thic class can be used to call a Javascript function sittin in the client browser from the server side.
		 * Can be used inside event handlers to do something after verification  on server side.
		 * @static
		 * @param string $strJavaScript the javascript to execute
		 * @param int $intPriority
		 */
		public static function ExecuteJavaScript($strJavaScript, $intPriority = QJsPriority::Standard) {
			if (is_bool($intPriority)) {
				//we keep this codepath for backward compatibility
				if ($intPriority == true)
					array_push(QApplication::$JavaScriptArrayHighPriority, $strJavaScript);
				else
					array_push(QApplication::$JavaScriptArray, $strJavaScript);
			} else {
				switch ($intPriority) {
					case QJsPriority::High:
						array_push(QApplication::$JavaScriptArrayHighPriority, $strJavaScript);
						break;
					case QJsPriority::Low:
						array_push(QApplication::$JavaScriptArrayLowPriority, $strJavaScript);
						break;
					default:
						array_push(QApplication::$JavaScriptArray, $strJavaScript);
						break;
				}
			}
		}

		public static function OutputPage($strBuffer) {
			// If the ProcessOutput flag is set to false, simply return the buffer
			// without processing anything.
			if (!QApplication::$ProcessOutput)
				return $strBuffer;

			if (QApplication::$ErrorFlag) {
				return $strBuffer;
			} else {
				if (QApplication::$RequestMode == QRequestMode::Ajax) {
					return trim($strBuffer);
				} else {
					// Update Cache-Control setting
					header('Cache-Control: ' . QApplication::$CacheControl);

					$strScript = QApplicationBase::RenderJavaScript(false);

					if ($strScript)
						return sprintf('%s<script type="text/javascript">%s</script>', $strBuffer, $strScript);

					return $strBuffer;
				}
			}
		}

		/**
		 * Function renders Javascript on the client browser.
		 * @static
		 * @param bool $blnOutput if true print the result, otherwise return it
		 * @return null|string
		 */
		public static function RenderJavaScript($blnOutput = true) {
			$strScript = '';
			foreach (QApplication::$AlertMessageArray as $strAlert) {
				$strAlert = addslashes($strAlert);
				$strScript .= sprintf('alert("%s"); ', $strAlert);
			}
			foreach (QApplication::$JavaScriptArrayHighPriority as $strJavaScript) {
				$strJavaScript = trim($strJavaScript);
				if (QString::LastCharacter($strJavaScript) != ';')
					$strScript .= sprintf('%s; ', $strJavaScript);
				else
					$strScript .= sprintf('%s ', $strJavaScript);
			}
			foreach (QApplication::$JavaScriptArray as $strJavaScript) {
				$strJavaScript = trim($strJavaScript);
				if (QString::LastCharacter($strJavaScript) != ';')
					$strScript .= sprintf('%s; ', $strJavaScript);
				else
					$strScript .= sprintf('%s ', $strJavaScript);
			}
			foreach (QApplication::$JavaScriptArrayLowPriority as $strJavaScript) {
				$strJavaScript = trim($strJavaScript);
				if (QString::LastCharacter($strJavaScript) != ';')
					$strScript .= sprintf('%s; ', $strJavaScript);
				else
					$strScript .= sprintf('%s ', $strJavaScript);
			}

			QApplication::$AlertMessageArray = array();
			QApplication::$JavaScriptArrayHighPriority = array();
			QApplication::$JavaScriptArray = array();
			QApplication::$JavaScriptArrayLowPriority = array();

			if ($strScript) {
				if ($blnOutput) {
					_p($strScript, false);
				} else {
					return $strScript;
				}
			}
			return null;
		}

  		/**
		 * If LanguageCode is specified and QI18n::Initialize() has been called, then this
		 * will perform a translation of the given token for the specified Language Code and optional
		 * Country code.
		 *
		 * Otherwise, this will simply return the token as is.
		 * This method is also used by the global print-translated "_t" function.
		 *
		 * @param string $strToken
		 * @return string the Translated token (if applicable)
		 */
		public static function Translate($strToken) {
			if (QApplication::$LanguageObject)
				return QApplication::$LanguageObject->TranslateToken($strToken);
			else
				return $strToken;
		}

		/**
		 * Global/Central HtmlEntities command to perform the PHP equivalent of htmlentities.
		 * Feel free to override to specify encoding/quoting specific preferences (e.g. ENT_QUOTES/ENT_NOQUOTES, etc.)
		 * 
		 * This method is also used by the global print "_p" function.
		 *
		 * @param string $strText text string to perform html escaping
		 * @return string the html escaped string
		 */
		public static function HtmlEntities($strText) {
			return htmlentities($strText, ENT_COMPAT, QApplication::$EncodingType);
		}

  		/**
		 * For development purposes, this static method outputs all the Application static variables
		 *
		 * @return void
		 */
		public static function VarDump() {
			_p('<div class="var-dump"><strong>QCubed Settings</strong><ul>', false);
			$arrValidationErrors = QInstallationValidator::Validate();
			foreach ($arrValidationErrors as $objResult) {
				printf('<li><strong class="warning">WARNING:</strong> %s</li>', $objResult->strMessage);
			}

			printf('<li>QCUBED_VERSION = "%s"</li>', QCUBED_VERSION);
			printf('<li>jQuery version = "%s"</li>', __JQUERY_CORE_VERSION__);
			printf('<li>jQuery UI version = "%s"</li>', __JQUERY_UI_VERSION__);
			printf('<li>__SUBDIRECTORY__ = "%s"</li>', __SUBDIRECTORY__);
			printf('<li>__VIRTUAL_DIRECTORY__ = "%s"</li>', __VIRTUAL_DIRECTORY__);
			printf('<li>__INCLUDES__ = "%s"</li>', __INCLUDES__);
			printf('<li>__QCUBED_CORE__ = "%s"</li>', __QCUBED_CORE__);
			printf('<li>ERROR_PAGE_PATH = "%s"</li>', ERROR_PAGE_PATH);
			printf('<li>PHP Include Path = "%s"</li>', get_include_path());
			printf('<li>QApplication::$DocumentRoot = "%s"</li>', QApplication::$DocumentRoot);
			printf('<li>QApplication::$EncodingType = "%s"</li>', QApplication::$EncodingType);
			printf('<li>QApplication::$PathInfo = "%s"</li>', QApplication::$PathInfo);
			printf('<li>QApplication::$QueryString = "%s"</li>', QApplication::$QueryString);
			printf('<li>QApplication::$RequestUri = "%s"</li>', QApplication::$RequestUri);
			printf('<li>QApplication::$ScriptFilename = "%s"</li>', QApplication::$ScriptFilename);
			printf('<li>QApplication::$ScriptName = "%s"</li>', QApplication::$ScriptName);
			printf('<li>QApplication::$ServerAddress = "%s"</li>', QApplication::$ServerAddress);

			if (QApplication::$Database) foreach (QApplication::$Database as $intKey => $objObject) {
				printf('<li>QApplication::$Database[%s] settings:</li>', $intKey);
				_p("<ul>", false);				
				foreach (unserialize(constant('DB_CONNECTION_' . $intKey)) as $key => $value) {
					if ($key == "password") {
						$value = "hidden for security purposes";
					}
					
					_p("<li>" . $key. " = " . var_export($value, true). "</li>", false);
				}
				_p("</ul>", false);
					
			}
			_p('</ul></div>', false);
		}		
	}

	/**
	 * Class for enumerating Javascript priority.
	 */
	class QJsPriority {
		const Standard = 0;
		const High = 1;
		const Low = -1;
	}

	/**
	 * This is an enumerator class for listing Request Modes
	 */
	class QRequestMode {
		const Standard = 'Standard';
		const Ajax = 'Ajax';
	}

	class QBrowserType {
		const InternetExplorer = 1;

		/* Deprecated. See QApplication::BrowserVersion **
		const InternetExplorer_6_0 = 2;
		const InternetExplorer_7_0 = 4;
		const InternetExplorer_8_0 = 8;*/

		const Firefox = 	0x10;

		/* Deprecated. See QApplication::BrowserVersion **
		const Firefox_1_0 = 0x20;
		const Firefox_1_5 = 0x40;
		const Firefox_2_0 = 0x80;
		const Firefox_3_0 = 0x100;*/

		const Safari = 		0x200;
		/* Deprecated. See QApplication::BrowserVersion **
		const Safari_2_0 = 	0x400;
		const Safari_3_0 = 	0x800;
		const Safari_4_0 = 	0x1000;*/

		const Opera = 		0x2000;
		/* Deprecated. See QApplication::BrowserVersion **
		const Opera_7 = 	0x4000;
		const Opera_8 = 	0x8000;
		const Opera_9 = 	0x10000;*/

		const Konqueror = 	0x20000;
		/* Deprecated. See QApplication::BrowserVersion **
		const Konqueror_3 = 0x40000;
		const Konqueror_4 = 0x80000;*/

		const Chrome = 		0x100000;
		/* Deprecated. See QApplication::BrowserVersion **
		const Chrome_0 = 	0x200000;
		const Chrome_1 = 	0x400000;*/

		const Windows = 	0x800000;
		const Linux = 		0x1000000;
		const Macintosh = 	0x2000000;
		const Mobile = 		0x4000000;	// some kind of mobile browser

		const Unsupported = 0x8000000;
	}
?>