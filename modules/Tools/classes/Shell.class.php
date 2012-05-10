<?php
/**
 * UniPg
 * @package Tools
 */

/**
 * Команды выполняемые через shell
 *
 * @package Tools
 * @subpackage Tools_Shell
 *
 * @author vpak
 *
 */
class Miao_Tools_Shell
{
	private static $_instance = null;

	/**
	 *
	 * @var Miao_Tools_Log
	 */
	private $_log = null;

	private function __construct()
	{

	}

	/**
	 *
	 * Set log
	 * @param Miao_Log $log
	 */
	public function setLog( Miao_Log $log )
	{
		$this->_log = $log;
	}

	/**
	 *
	 * @param bool $forceNew
	 * @return Miao_Tools_Shell
	 */
	static public function getInstance( $forceNew = false )
	{
		if ( is_null( self::$_instance ) || true == $forceNew )
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function getServerName()
	{
		$cmd = 'uname -n';
		$returnVal = 0;
		$output = null;
		$result = $this->exec( $cmd, $returnVal );

		if ( 0 != $returnVal )
		{
			$message = sprintf( '%s', $output[ 0 ] );
			$this->_addMessageLog( $message, Miao_Log::ERR );

			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}

		return $result;
	}

	public function getRemoteFile( $serverName, $srcFilename, $dstFilename = null )
	{
		if ( is_null( $dstFilename ) )
		{
			$dstFilename = $srcFilename;
		}

		if ( empty( $serverName ) )
		{
			$message = sprintf( 'Invalid param $serverName: must be not empty' );
			$this->_addMessageLog( $message, Miao_Log::ERR );

			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}
		if ( empty( $srcFilename ) )
		{
			$message = sprintf(
				'Invalid param $srcFilename: must be not empty' );
			$this->_addMessageLog( $message, Miao_Log::ERR );

			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}

		$cmd = sprintf( 'scp %s:%s %s', $serverName, $srcFilename,
			$dstFilename );

		$returnVal = 0;
		$result = $this->exec( $cmd, $returnVal );

		return $dstFilename;
	}

	/**
	 * Слияние файлов
	 * @param string $fileTo Куда копируем (общий файл)
	 * @param string $fileFrom Откуда копируем
	 * @param string $deleteFileFrom Удалять файл $fileFrom
	 */
	public function mergeFile( $fileTo, $fileFrom, $deleteFileFrom = true )
	{
		if ( empty( $fileTo ) )
		{
			$message = 'Invalid param $fileTo: must be not empty';
			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}

		$fileToDirname = dirname( $fileTo );
		if ( !file_exists( $fileToDirname ) )
		{
			try
			{
				Miao_Tools_Shell::getInstance()->makeDir( $fileToDirname );
			}
			catch ( Miao_Tools_Shell_Exception $e )
			{
				$message = sprintf( "%s", $e->getMessage() );
				$this->_addMessageLog( $message,
					Miao_Tools_Log::LOG_LEVEL_WARNING );
			}
		}

		$cmd0 = '';
		$cmd = '';
		if ( !file_exists( $fileTo ) )
		{
			$cmd = sprintf( 'cp %s %s', $fileFrom, $fileTo );
		}
		else
		{
			$cmd0 = sprintf( 'echo >> %s', $fileTo );
			$cmd = sprintf( 'cat %s >> %s', $fileFrom, $fileTo );
		}

		try
		{
			$returnVal = 0;
			if ( !empty( $cmd0 ) )
			{
				$this->exec( $cmd0, $returnVal, false );
			}
			$this->exec( $cmd, $returnVal, false );
		}
		catch ( Miao_Tools_Shell_Exception $e )
		{
			$message = sprintf( 'Params: $fileTo(%s), $fileFrom(%s). %s',
				$fileTo, $fileFrom, $e->getMessage() );
			$this->_addMessageLog( $message, Miao_Log::ERR );
			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}

		if ( $deleteFileFrom )
		{
			unlink( $fileFrom );
		}

		$result = false;
		if ( 0 == $returnVal )
		{
			$result = true;
		}
		return $result;
	}

	/**
	 *
	 * Копирование данных из базы в файл scv
	 * @param string $connection Connection string
	 * @param string $tableName Table name
	 * @param string $filename
	 * @param string $delimiter
	 * @param string $nullAs
	 */
	public function dbCopyFrom( $connection, $tableName, $filename, $delimiter = "\t", $nullAs = "N" )
	{
		if ( !file_exists( $filename ) )
		{
			$message = sprintf( 'File not found (%s)', $filename );
			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}
		$config = Uniora_Core_Config::Project();
		$psql = $config->Helper->Shell->psql;

		$command = '';
		$command .= sprintf( 'cat "%s" | ', $filename );
		$command .= sprintf(
			'%s "%s" -c "set client_encoding to win1251; copy %s from stdin with delimiter as \'%s\' null as \'%s\' csv ESCAPE  AS  E\'\\\\\\\'"',
			$psql, $connection, $tableName, $delimiter, $nullAs );

		$returnVal = 0;
		$result = $this->exec( $command, $returnVal, false );

		$result = false;
		if ( 0 == $returnVal )
		{
			$result = true;
		}
		return $result;
	}

	/**
	 * Копирование данных из файла scv в базу
	 * @param string $connection Connection string
	 * @param string $query
	 * @param string $filename
	 * @param string $delimiter
	 * @param string $nullAs
	 */
	public function dbCopyTo( $connection, $query, $filename, $delimiter = "\t", $nullAs = "N" )
	{
		$dir = dirname( $filename );
		if ( !file_exists( $dir ) || !is_dir( $dir ) || !is_writable( $dir ) )
		{
			$message = sprintf(
				'Directory (%s) is not exists or is not writable', $dir );
			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}
		if ( file_exists( $filename ) )
		{
			$message = sprintf(
				'File (%s) exists, delete file before export data', $filename );
			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}
		$config = Uniora_Core_Config::Project();
		$psql = $config->Helper->Shell->psql;

		$command = '';
		$command .= sprintf(
			'%s "%s" -c "set client_encoding to win1251; copy (%s) to stdout with delimiter as \'%s\' null as \'%s\' csv ESCAPE  AS  E\'\\\\\\\'"',
			$psql, $connection, $query, $delimiter, $nullAs );
		$command .= sprintf( ' > "%s"', $filename );

		$returnVal = 0;
		$result = $this->exec( $command, $returnVal, false );

		$result = false;
		if ( 0 == $returnVal )
		{
			$result = true;
		}
		return $result;
	}

	/**
	 *
	 * Чтение файла при помощи tail
	 * @param string $filename
	 * @param string $lines
	 * @param string $verbose
	 * @param string $server
	 * @throws Miao_Tools_Shell_Exception
	 */
	public function tailFile( $filename, $lines = 100, $verbose = true, $server = null )
	{
		if ( !file_exists( $filename ) )
		{
			$message = sprintf( 'File not found (%s).', $filename );
			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}

		$verbose = ( $verbose ) ? '-v' : '';
		if ( !empty( $lines ) )
		{
			$cmd = sprintf( 'tail %s -n %s %s', $verbose, $lines, $filename );
		}
		else
		{
			$cmd = sprintf( 'echo "%s"; cat "%s"', $filename, $filename );
		}

		if ( !is_null( $server ) )
		{
			$cmd = sprintf( 'ssh %s "%s"', $server, $cmd );
		}

		$result = $this->shellExec( $cmd, false );
		return $result;
	}

	/**
	 *
	 * Удаленный поиск файлов при помощи find
	 * @param string $server
	 * @param string $path
	 * @param string $type
	 * @param string $name
	 * @param string $other
	 */
	public function remoteFind( $server, $path, $type, $name = '', $other = '' )
	{
		$cmd = sprintf(
			'ssh %s "if [ -d \"%s\" ]; then find \"%s\" -type %s | grep -v ".svn"; fi;"  ',
			$server, $path, $path, $type );
		$result = $this->shellExec( $cmd, false );

		$result = trim( $result );
		$result = explode( "\n", $result );

		foreach ( $result as $key => & $item )
		{
			$item = trim( $item );
			if ( $path == $item || empty( $item ) )
			{
				unset( $result[ $key ] );
			}
		}

		return $result;
	}

	/**
	 *
	 * Создание архива tgz
	 * @param string $name
	 * @param array $files
	 * @throws Miao_Tools_Shell_Exception
	 */
	public function createArch( $name, array $files )
	{
		if ( !$this->_checkFullName( $name ) )
		{
			$message = sprintf( 'Имя файла (%s) должно быть абсолютным.',
				$name );
			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}

		//copy
		$tmpDir = dirname( $name );
		//Miao_Tools_FileSystem_Directory::createPath( $tmpDir );
		if ( !file_exists( $tmpDir ) )
		{
			mkdir( $tmpDir, 0777, true );
		}
		$copyDir = tempnam( $tmpDir, 'backup_' );
		unlink( $copyDir );

		$cmdCopy = sprintf( 'mkdir -p %s', $copyDir );
		foreach ( $files as $file )
		{
			if ( !$this->_checkFullName( $file ) )
			{
				$message = sprintf( 'Имя файла (%s) должно быть абсолютным.',
					$name );
				throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
			}

			$cmdCopy .= sprintf( ' && cp -r %s %s', $file, $copyDir );
		}

		$returnVal = 0;
		$this->exec( $cmdCopy, $returnVal, false );

		//make tar
		$cmdTar = sprintf( 'cd %s && tar -czf %s %s && rm -rf %s',
			$copyDir, $name, './*', $copyDir );
		$returnVal = 0;
		$this->exec( $cmdTar, $returnVal, false );

		$result = $name;
		return $result;
	}

	/**
	 *
	 * Распаковка арихва tgz
	 * @param string $archName
	 * @param string $extractDir
	 * @param bool $deleteArch
	 */
	public function extractArch( $archName, $extractDir, $deleteArch = true )
	{
		$cmdTar = sprintf( 'tar -xzf %s -C %s && ls -1 %s', $archName,
			$extractDir, $extractDir );

		if ( $deleteArch )
		{
			$cmdTar .= sprintf( ' && rm %s', $archName );
		}

		$returnVal = 0;
		$result = $this->shellExec( $cmdTar, false );

		$result = explode( "\n", trim( $result, "\n" ) );

		foreach ( $result as & $item )
		{
			$item = $extractDir . DIRECTORY_SEPARATOR . ltrim( $item, './' );
		}

		return $result;
	}

	/**
	 * TODO: добавить проверку исключительных ситуаций
	 * @param string $dirname
	 * @param string $mode
	 */
	public function makeDir( $dirname, $mode = 764 )
	{
		if ( !file_exists( $dirname ) )
		{
			$cmd = sprintf( 'mkdir -m %s -p %s', $mode, $dirname );

			$returnVal = 0;
			$this->exec( $cmd, $returnVal );
		}
	}

	public function shellExec( $cmd, $escape = true )
	{
		if ( empty( $cmd ) )
		{
			$message = sprintf( 'Invalid param $cmd: must be not empty' );
			$this->_addMessageLog( $message, Miao_Log::ERR );

			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}
		$message = sprintf( 'Start command: %s', $cmd );
		$this->_addMessageLog( $message );

		if ( $escape )
		{
			$cmd = escapeshellcmd( $cmd );
		}

		$result = shell_exec( $cmd );

		$message = sprintf( 'End command: %s. Result: %s', $cmd, $result );
		$this->_addMessageLog( $message );

		return $result;
	}

	public function exec( $cmd, & $returnVal, $escape = true, & $output = null )
	{
		if ( empty( $cmd ) )
		{
			$message = sprintf( 'Invalid param $cmd: must be not empty' );
			$this->_addMessageLog( $message, Miao_Log::ERR );

			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}

		$message = sprintf( 'Start command: %s', $cmd );
		$this->_addMessageLog( $message );

		if ( $escape )
		{
			$cmd = escapeshellcmd( $cmd );
		}
		$cmd .= ' 2>&1';

		$result = exec( $cmd, $output, $returnVal );

		if ( 0 != $returnVal )
		{
			if ( is_array( $output ) )
			{
				$output = array_shift( $output );
			}
			$message = sprintf( 'Cmd: %s. %s', $cmd, $output );
			$this->_addMessageLog( $message, Miao_Log::ERR );

			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}

		$message = sprintf( 'End command: %s. Return: %s. Result: %s', $cmd,
			$returnVal, $result );
		$this->_addMessageLog( $message );

		return $result;
	}

	public function iconvFile( $in_charset, $out_charset, $src, $dest = null )
	{
		if ( is_null( $dest ) || 0 == mb_strlen( $dest, 'UTF-8' ) )
		{
			$dest = $src;
		}
		$cmd = sprintf( 'iconv -f "%s" -t "%s" -c "%s" > "%s"', $in_charset,
			$out_charset, $src, $dest );

		$returnVal = 0;
		$output = null;
		$result = $this->exec( $cmd, $returnVal, false );
		if ( 0 != $returnVal )
		{
			$message = sprintf( '%s', $output[ 0 ] );
			$this->_addMessageLog( $message, Miao_Log::ERR );

			throw new Miao_Tools_Shell_Exception( $message, __METHOD__ );
		}
		return $dest;
	}

	private function _addMessageLog( $message, $logLevel = Miao_Log::DEBUG )
	{
		if ( !is_null( $this->_log ) )
		{
			$this->_log->log( $message, $logLevel );
		}
	}

	private function _checkFullName( $filename )
	{
		$result = true;
		if ( empty( $filename ) || !is_string( $filename ) || $filename[ 0 ] !== DIRECTORY_SEPARATOR )
		{
			$result = false;
		}
		return $result;
	}

	private function _checkArchName( $name )
	{
		$result = true;
		if ( strlen( $name ) < 5 )
		{
			$result = false;
		}

		$pos = stripos( $name, '.' );
		if ( false === $pos )
		{
			$result = false;
		}
		else
		{
			$ext = substr( $name, $pos );
			if ( $ext != 'tgz' )
			{
				$result = false;
			}
		}
		return $result;
	}
}
