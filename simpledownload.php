<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * SimpleDownload Plugin
 * 
 * This plugin is to be used in conjunction with com_simpledownload and is used for
 * creating specialized links that force certain files to be downloaded in a web
 * browser.
 */
class plgSystemSimpleDownload extends JPlugin
{
	/**
	 * Constructor
	 *
	 * For php4 compatibility we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @access	protected
	 * @param	object	$subject The object to observe
	 * @param 	array   $config  An array that holds the plugin configuration
	 * @since	0.9.7
	 */
	function plgSystemSimpleDownload( &$subject, $config )
	{
		parent::__construct( $subject, $config );

		// Do some extra initialisation in this constructor if required
	}

	/**
	 * This function is run right before sending the page back to the browser.
	 * By intercepting here, we can replace simpledownload tags if they exist
	 * anywhere on the website.
	 */
	function onAfterRender()
	{
		$app = JFactory::getApplication();
	
		if($app->isAdmin()) {
		    return;
		}
		
		$body = JResponse::getBody();
		
		$regexPattern = "/{simpledownload*.*?}(.*?){\/simpledownload}/i"; 
		
		if (preg_match_all($regexPattern, $body, $matches) > 0) {
			
			$componentParams	=& JComponentHelper::getParams( 'com_simpledownload' );
			$cipherenabled		= $componentParams->get('cipherenabled');
			$cipherfile			= $componentParams->get('cipherfile');
			$cipherfunction		= $componentParams->get('cipherfunction');
			
			$plugin =& JPluginHelper::getPlugin('content', 'simpledownload');
			$pluginParams = new JParameter( $plugin->params );
			$useCustomOutput = $pluginParams->get('useCustomOutput', '0');
			$customOutputPattern = $pluginParams->get('customOutputPattern', '');
			
			$cipherFileIncluded	= false;
			if ($cipherfile != "" && file_exists($cipherfile)) {
				// cipher file should be included
				include_once($cipherfile);
				$cipherFileIncluded	= true;
			}
			
			for ($i=0; $i<count($matches[0]); ++$i) {
				
				$body = JResponse::getBody();
				
				if (preg_match('/{simpledownload href=(.*?)}/i', $matches[0][$i], $pathMatch) > 0) {
					// get the path out of the plugin text
					$path = $pathMatch[1];
				} else {
					// invalid markup, so just remove it altogether so it doesn't show up on the page.
					JResponse::setBody(str_replace($matches[0][$i], "", $body ));
					continue;
				}
				
				if ($cipherFileIncluded == true && $cipherenabled == "1" && $cipherfunction != "") {
					$path = $cipherfunction($path);
				}
				
				$link = JRoute::_('index.php?option=com_simpledownload&task=download&fileid=' . $path);
				
				//<a href='$link'>$downloadTitle</a>
				$downloadTitle = $matches[1][$i];
				
				// in case there is no title provided, set it to the path (which is the clear path
				// or the encrypted path depending on if encryption is enabled.
				if ($downloadTitle == "") {
					$downloadTitle = $path;
				}
				
				$output = '<a href=\'' . $link . '\'>' . $downloadTitle . '</a>'; // default link format
				
				if ($useCustomOutput == "1" && $customOutputPattern != "") {
					$output = $customOutputPattern;
					$output = preg_replace('/\{filepath\}/i', $link, $output);
					$output = preg_replace('/\{title\}/i', $downloadTitle, $output);
					$output = preg_replace('/\{fileid}/i', $path, $output);
				}
				
				JResponse::setBody(str_replace($matches[0][$i], $output, $body ));
			}
		}

        return true;
	}

}