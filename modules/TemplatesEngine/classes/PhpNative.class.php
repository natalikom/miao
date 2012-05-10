<?php
// TOOD: added log

/**
 * UniPg
 *
 * @package TemplatesEngine
 */

/**
 * Simple PHP-Native Templates-Engine.
 * Parses tree-structured ( by php-includes ) PHP-Native-templates.
 * May parse internal includes separately.
 *
 * Пример использования класса.
 *
 * Файл - <b>test1.php</b>
 * <code>
 * <?php
 * error_reporting( 2047 );
 * require_once( '/www/spb.vpak.dv.rbc.ru/Uniora/core/classes/Core.class.php' );
 * $tmpl_dir = '/www/spb.vpak.dv.rbc.ru/releases/branches/Dev_1.0.0/htdocs/temp/templates/test1';
 * $tplObj = new Miao_TemplatesEngine_PhpNative( $tmpl_dir, false );
 *
 * for ( $i=0; $i<3; $i++)
 * {
 * echo '<div align="center">Cicle iteration - ' . ( $i+1 ) . '</div>';
 *
 * $tplObj->setValueOf( 'x', $i );
 *
 * $str = $tplObj->fetch( '1.tpl' );
 * $str .= $tplObj->fetch( '2.tpl' );
 *
 * echo $str;
 *
 * echo '<br /><br /><hr />';
 * }
 * </code>
 *
 * Файл - <b>templates/1.tpl.php</b>
 * <code>
 * Block 1
 * <?=$this->_includeTemplate( '1_1.tpl' )?>
 * <br />
 * continue block 1
 * </code>
 *
 * Файл - <b>templates/1_1.tpl.php</b>
 * <code>
 * Block 1, subblock 1
 * <br />
 * x=<?=$this->_getValueOf( 'x' )?>
 * <br />
 * <?php/**
 * Simple PHP-Native Templates-Engine.
 * Parses tree-structured ( by php-includes ) PHP-Native-templates.
 * May parse internal includes separately.
 *
 * Пример использования класса.
 *
 * Файл - <b>test1.php</b>
 * <code>
 * <?php
 * error_reporting( 2047 );
 * require_once( '/www/spb.vpak.dv.rbc.ru/Uniora/core/classes/Core.class.php' );
 * $tmpl_dir =
 * '/www/spb.vpak.dv.rbc.ru/releases/branches/Dev_1.0.0/htdocs/temp/templates/test1';
 * $tplObj = new Miao_TemplatesEngine_PhpNative( $tmpl_dir, false );
 *
 * for ( $i=0; $i<3; $i++)
 * {
 * echo '<div align="center">Cicle iteration - ' . ( $i+1 ) . '</div>';
 *
 * $tplObj->setValueOf( 'x', $i );
 *
 * $str = $tplObj->fetch( '1.tpl' );
 * $str .= $tplObj->fetch( '2.tpl' );
 *
 * echo $str;
 *
 * echo '<br /><br /><hr />';
 * }
 * </code>
 *
 * Файл - <b>templates/1.tpl.php</b>
 * <code>
 * Block 1
 * <?=$this->_includeTemplate( '1_1.tpl' )?>
 * <br />
 * continue block 1
 * </code>
 *
 * Файл - <b>templates/1_1.tpl.php</b>
 * <code>
 * Block 1, subblock 1
 * <br />
 * x=<?=$this->_getValueOf( 'x' )?>
 * <br />
 * <?php
 * try
 * {
 * throw new Miao_Core_Exception( 'Проверка' );
 * }
 * catch ( Exception $e )
 * {
 * echo $e;
 * }
 * ?>
 *
 * <b>templates/2.tpl.php</b>
 * <br />
 * Block 2
 * </code>
 *
 * @author S.Vyazovetskov <svyazovetskov@rbc.ru>
 * @copyright RBC 2007
 * @package TemplatesEngine
 */
class Miao_TemplatesEngine_PhpNative implements Miao_TemplatesEngine_Interface
{
	protected $_templatesDir;
	protected $_templateVars = array();
	protected $_debugMode;
	protected $_forceExceptionsOnInclude;
	protected $_logFilename = '';

	/**
	 *
	 * @var Miao_Log
	 */
	protected $_log = null;

	/**
	 *
	 * @param Miao_Log $_logObj
	 */
	public function setLogObj( $log )
	{
		$this->_log = $log;
	}

	/**
	 *
	 * @return the $_logObj
	 */
	public function getLogObj()
	{
		if ( !$this->_log )
		{
			$debugMode = $this->getDebugMode();
			$logFilename = $this->_logFilename;
			$log = Miao_Log::easyFactory( $logFilename, false, $debugMode ? Miao_Log::DEBUG : Miao_Log::ERR );
			$this->_log = $log;
		}

		return $this->_log;
	}

	static public function getDefaultInstance()
	{
		$configData = include realpath( dirname( __FILE__ ) . '/../data/config.php' );
		$config = new Miao_Config( $configData );

		$templatesDir = $config->get( '/TemplatesEngine/templatesDir' );
		$debugMode = $config->get( '/TemplatesEngine/debugMode' );
		$result = new self( $templatesDir, $debugMode );
		$this->_logFilename = $config->get( '/TemplatesEngine/log/filename' );

		return $result;
	}

	/**
	 * Class constructor.
	 * Setter for templates root directory and debug mode switcher.
	 *
	 * @param string $templatesDir
	 * @param bool $debugMode
	 */
	public function __construct( $templatesDir = '', $debugMode = true )
	{
		$this->setTemplatesDir( $templatesDir );
		$this->setDebugMode( $debugMode );
	}

	/**
	 * Public setter for templates root directory.
	 *
	 * @param string $templatesDir
	 */
	public function setTemplatesDir( $templatesDir )
	{
		$this->_templatesDir = rtrim( $templatesDir, '\\/' ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Get templates root directory
	 *
	 * @return string
	 */
	public function getTemplatesDir()
	{
		return $this->_templatesDir;
	}

	/**
	 * Public setter for debug mode switcher.
	 *
	 * @param bool $debugMode
	 */
	public function setDebugMode( $debugMode = true )
	{
		$this->_debugMode = $debugMode;
	}

	public function getDebugMode()
	{
		return $this->_debugMode;
	}

	/**
	 * Setter for template variables.
	 *
	 * @param string $templateVarName
	 * @param mixed $templateVarValue
	 */
	public function setValueOf( $templateVarName, $templateVarValue = null )
	{
		$this->_templateVars[ $templateVarName ] = $templateVarValue;
	}

	/**
	 * Assigns value by reference to template-variable
	 *
	 * @param string $templateVarName
	 * @param mixed $templateVarValue
	 */
	public function setValueOfByRef( $templateVarName, & $templateVarValue )
	{
		$this->_templateVars[ $templateVarName ] = $templateVarValue;
	}

	/**
	 * Assigns value by array
	 *
	 * @param array $data
	 */
	public function setValueOfByArray( array $data )
	{
		foreach ( $data as $templateVarName => $templateVarValue )
		{
			$this->_templateVars[ $templateVarName ] = $templateVarValue;
		}
	}

	/**
	 * Parses template with given filename, relative from template root
	 * directory,
	 * and returns result of parsing.
	 * May save result into file with given filename ( no saves by default ).
	 *
	 * @param string $templateName
	 * @param string $fileToSave
	 * @param string $mode
	 * @return string
	 */
	public function fetch( $templateName, $fileToSave = null, $mode = null, $display = false )
	{
		$absoluteFilename = $this->_templatesDir . $templateName;
		$templateContents = $this->_returnParsedTemplate( $absoluteFilename );
		if ( null !== $fileToSave )
		{
			$mode = ( null === $mode ) ? 'w' : $mode;
			$this->_saveFile( $templateContents, $fileToSave, $mode );
		}
		return $templateContents;
	}

	/**
	 * Unsets all template variables.
	 */
	public function resetTemplateVariables()
	{
		unset( $this->_templateVars );
		$this->_templateVars = array();
	}

	/**
	 * Prints fetched template to the STD Out ( browser in common case ).
	 *
	 * @param string $templateName
	 */
	public function display( $templateName )
	{
		echo $this->fetch( $templateName );
	}

	/**
	 * Saves given $contents into file with given $fileName, $mode and $mask.
	 * Can be extended in child classes for cache realization.
	 *
	 * @param string $contents
	 * @param string $fileName
	 * @param string $mode
	 * @param string $mask
	 */
	protected function _saveFile( $contents, $fileName, $mode )
	{
		$fileHandler = fopen( $fileName, $mode );
		if ( false === $fileHandler )
		{
			throw new Miao_TemplatesEngine_Exception_OnFailFileOpen( $fileName, $mode );
		}
		fwrite( $fileHandler, $contents );
		fclose( $fileHandler );
	}

	/**
	 * Getter for template variables.
	 * MUST be used inside templates in protected scope of TemplatesEngine
	 * class.
	 *
	 * @param string $templateVarName
	 * @param mixed $defaulValue
	 * @return mixed
	 */
	protected function _getValueOf( $varName, $defaultValue = null, $useNullAsDefault = false )
	{
		if ( !array_key_exists( $varName, $this->_templateVars ) )
		{
			if ( ( null === $defaultValue ) && ( false === $useNullAsDefault ) )
			{
				throw new Miao_TemplatesEngine_Exception_OnVariableNotFound( $varName );
			}
			$this->_templateVars[ $varName ] = $defaultValue;
		}
		else if ( empty( $this->_templateVars[ $varName ] ) )
		{
			$this->_templateVars[ $varName ] = $defaultValue;
		}
		return $this->_templateVars[ $varName ];
	}

	/**
	 *
	 * @see _getValueOf
	 *
	 * @param unknown_type $templateVarName
	 * @param unknown_type $defaulValue
	 */
	protected function getValueOf( $templateVarName, $defaulValue = '' )
	{
		return $this->_getValueOf( $templateVarName, $defaulValue );
	}

	/**
	 * Includes template with given filename, relative from DocumentRoot
	 * directory,
	 * and returns parsed content.
	 * MUST be used inside templates in protected scope of TemplatesEngine
	 * class.
	 *
	 * @param string $relativePath
	 * @return string
	 */
	protected function _ssiVirtualInternal( $relativePath )
	{
		$project_root = Miao_Core_Config::Main()->paths->htdocs;
		$absolutePath = $project_root . $relativePath;

		return $this->_returnParsedTemplate( $absolutePath );
	}

	/**
	 * Включение без обработки файла по абсолютному пути
	 *
	 * @param string $fullPath
	 *        	полный путь к файлу с шаблоном
	 * @return strin
	 */
	protected function _ssiVirtualExternal( $fullPath )
	{
		try
		{
			return file_get_contents( $fullPath );
		}
		catch ( Exception $e )
		{
			$this->getLogObj()->log( $this->_exceptionToString( $e ), Miao_Log::ERR );
			return ( $this->_debugMode ? $this->_exceptionToString( $e ) : '' );
		}
	}

	/**
	 * Includes template with given filename, relative from templates root
	 * directory,
	 * and returns parsed content.
	 * MUST be used inside templates in protected scope of TemplatesEngine
	 * class.
	 *
	 * @param string $templateFilename
	 * @return string
	 */
	protected function _includeTemplate( $templateFilename, $useSelfBaseDir = false )
	{
		if ( true === $useSelfBaseDir )
		{
			$templateFilename = $this->_templatesDir . $templateFilename;
		}
		return $this->_returnParsedTemplate( $templateFilename );
	}

	/**
	 * Includes template with given absolute filename and returns parsed
	 * content.
	 * MUST NOT be used in any place - only for in_class usage.
	 * If exception is thrown inside template - exception text will be returned
	 * in debug mode.
	 *
	 * @param string $absoluteFilename
	 * @return string
	 */
	protected function _returnParsedTemplate( $absoluteFilename )
	{
		$resultUnbelievableNameForVar = '';
		try
		{
			$this->_checkFile( $absoluteFilename );
			$resultUnbelievableNameForVar .= $this->_startBlock();

			include ( $absoluteFilename );

			$resultUnbelievableNameForVar .= $this->_endBlock();
		}
		catch ( Miao_TemplatesEngine_Exception_OnFileNotFoundCritical $e )
		{
			throw $e; // re-throw exception to the outer catch block
		}
		catch ( Exception $e )
		{
			$resultUnbelievableNameForVar .= $this->_endBlock();

			$this->getLogObj()->log( $this->_exceptionToString( $e ), Miao_Log::ERR );

			if ( $this->_debugMode )
			{
				$resultUnbelievableNameForVar .= $this->_exceptionToString( $e );
			}
		}
		return $resultUnbelievableNameForVar;
	}

	/**
	 * Starts any parsed block ( see beore ).
	 * May be extended in child classes for additional functionality.
	 *
	 * @return string
	 */
	protected function _startBlock()
	{
		$result = '';
		if ( ob_get_level() == 0 )
		{
			ob_start();
		}
		ob_start();
		return $result;
	}

	/**
	 * Ends any parsed block ( see beore ).
	 * May be extended in child classes for additional functionality.
	 *
	 * @return string
	 */
	protected function _endBlock()
	{
		$buffer = ob_get_contents();
		ob_end_clean();

		$result = '';
		if ( false !== $buffer )
		{
			$result = $buffer;
		}
		return $result;
	}

	/**
	 * Do some checks ( on existence and readability ) on file with given
	 * absolute filename.
	 * May be extended in child classes for additional functionality.
	 *
	 * @param string $absoluteFilename
	 *        	@exception Miao_TemplatesEngine_Exception_OnFileNotFound
	 * @throws Miao_TemplatesEngine_Exception_OnFileNotFound
	 */
	protected function _checkFile( $absoluteFilename )
	{
		if ( ( !file_exists( $absoluteFilename ) ) || ( !is_readable( $absoluteFilename ) ) )
		{
			if ( $this->_debugMode )
			{
				throw new Miao_TemplatesEngine_Exception_OnFileNotFound( $absoluteFilename );
			}
			return false;
		}
		return true;
	}

	/**
	 * Retuns as string transormed Exception information.
	 * May be extended in child classes for additional functionality.
	 *
	 * @param Exception $e
	 * @return string
	 */
	protected function _exceptionToString( Exception $e )
	{
		$trace = $e->getTrace();
		$trace = current( $trace );

		return ( $e->getMessage() . "\n" . print_r( $trace, true ) . "\n" );
	}
}