<?php
class Miao_Tools_Shell_Test extends PHPUnit_Framework_TestCase
{
	public function testEmpty()
	{
		$this->assertTrue( true );
	}

	public function setUp()
	{
	}

	public function tearDown()
	{
		$sourcePath = Miao_PHPUnit::getSourceFolder( __CLASS__ );

		$commonFile = $sourcePath .= '/testMergeFile/common.txt';
		if ( file_exists( $commonFile ) )
		{
			unlink( $commonFile );
		}
	}

	/**
	 * @dataProvider providerTestGetRemoteFile
	 */
	public function testGetRemoteFile( $serverName, $filename, $actual = null, $exceptionName = '' )
	{
		if ( !empty( $exceptionName ) )
		{
			$this->setExpectedException( $exceptionName );
		}

		$dstFilename = dirname( $filename ) . '/temp.txt';
		$file = Miao_Tools_Shell::getInstance( true )->getRemoteFile(
		$serverName, $filename, $dstFilename );
		$expected = file_get_contents( $file );
		unlink( $file );
		$this->assertEquals( $expected, $actual );
	}

	public function providerTestGetRemoteFile()
	{
		$data = array();

		$sourceDir = Miao_PHPUnit::getSourceFolder( __METHOD__ );

		$data[] = array( 'localhost', $sourceDir . '/1.txt', '1' );
		$data[] = array(
			'localhost',
		$sourceDir . '/nofile.txt',
		null,
			'Miao_Tools_Shell_Exception' );
		$data[] = array(
			'vpak.dv.dv.dv.dv.dv',
		$sourceDir . '/1.txt',
		null,
			'Miao_Tools_Shell_Exception' );
		$data[] = array( 'localhost', '', null, 'Miao_Tools_Shell_Exception' );
		$data[] = array(
			'',
		$sourceDir . '/1.txt',
		null,
			'Miao_Tools_Shell_Exception' );

		return $data;
	}

	/**
	 * @dataProvider providerTestMergeFile
	 *
	 * @param array $files
	 * @param string $commonFile Общий файл
	 */
	public function testMergeFile( array $files, $commonFile, $actualFile, $deleteFileFrom = false, $exceptionName = '' )
	{
		if ( !empty( $exceptionName ) )
		{
			$this->setExpectedException( $exceptionName );
		}

		$helper = Miao_Tools_Shell::getInstance();
		foreach ( $files as $file )
		{
			$res = $helper->mergeFile( $commonFile, $file, $deleteFileFrom );

			$this->assertTrue( $res );
			$this->assertEquals( $deleteFileFrom, !file_exists( $file ) );
		}
		$this->assertFileEquals( $commonFile, $actualFile );
		unlink( $commonFile );
	}

	public function providerTestMergeFile()
	{
		$data = array();

		$exceptionName = 'Miao_Tools_Shell_Exception';
		$sourcePath = Miao_PHPUnit::getSourceFolder( __METHOD__ );

		$data[] = array(
		array( $sourcePath . '/1.txt', $sourcePath . '/2.txt' ),
		$sourcePath . '/common.txt',
		$sourcePath . '/actual.txt' );

		$data[] = array(
		array( $sourcePath . '/1.txt', $sourcePath . '/2.txt' ),
			'',
		$sourcePath . '/actual.txt',
		false,
			'Miao_Tools_Shell_Exception' );

		$data[] = array(
		array( $sourcePath . '/1.txt', $sourcePath . '/3.txt' ),
		$sourcePath . '/common.txt',
		$sourcePath . '/actual.txt',
		false,
			'Miao_Tools_Shell_Exception' );

		file_put_contents( $sourcePath . '/a.txt', '1' );
		file_put_contents( $sourcePath . '/b.txt', '2' );
		$data[] = array(
		array( $sourcePath . '/a.txt', $sourcePath . '/b.txt' ),
		$sourcePath . '/common.txt',
		$sourcePath . '/actual.txt',
		true );

		return $data;
	}

	/**
	 * @dataProvider providerTestTailFile
	 * @param unknown_type $filename
	 * @param unknown_type $lines
	 * @param unknown_type $exceptionName
	 */
	public function testTailFile( $filename, $lines, $actual, $exceptionName = '' )
	{
		if ( !empty( $exceptionName ) )
		{
			$this->setExpectedException( $exceptionName );
		}

		$helper = Miao_Tools_Shell::getInstance();
		$expected = $helper->tailFile( $filename, $lines, false );

		$this->assertEquals( $expected, $actual );

		if ( file_exists( $filename ) )
		{
			unlink( $filename );
		}
	}

	public function providerTestTailFile()
	{
		$data = array();

		$sourcePatch = Miao_PHPUnit::getSourceFolder( __METHOD__ );
		$exceptionName = 'Miao_Tools_Shell_Exception';

		$data[] = array( '', null, '', $exceptionName );

		$filename = $sourcePatch . 'aaa.txt';
		file_put_contents( $filename, "1\n2" );
		$data[] = array( $filename, 1, "2" );

		$filename = $sourcePatch . 'bbb.txt';
		file_put_contents( $filename, "1\n2\n3" );
		$data[] = array( $filename, 2, "2\n3" );

		return $data;
	}

	public function testRemoteFind()
	{
		$shell = Miao_Tools_Shell::getInstance();

		$path = Miao_PHPUnit::getSourceFolder( __METHOD__ );
		$server = $shell->getServerName();

		$expected = $shell->remoteFind( $server, $path, 'f' );
		$actual = array(
		$path . '/1.txt',
		$path . '/sub/1.txt',
		$path . '/sub/2.txt' );
		$this->assertEquals( $expected, $actual );

		$expected = $shell->remoteFind( $server, $path . '/sub', 'f' );
		$actual = array( $path . '/sub/1.txt', $path . '/sub/2.txt' );
		$this->assertEquals( $expected, $actual );

		$expected = $shell->remoteFind( $server, $path, 'd' );
		$actual = array( 1 => $path . '/sub' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @dataProvider providerTestArch
	 *
	 * @param array $files
	 * @param unknown_type $archName
	 */
	public function testArch( array $files, $archName, $testDirName, $exceptionName = '' )
	{
		if ( !empty( $exceptionName ) )
		{
			$this->setExpectedException( $exceptionName );
		}

		$helper = Miao_Tools_Shell::getInstance();

		$expected = $helper->createArch( $archName, $files );
		$this->assertEquals( $expected, $archName );

		$this->assertTrue( file_exists( $archName ) );
		// ----------------


		$sourceDir = Miao_PHPUnit::getSourceFolder( __METHOD__ );
		$extractDir = $sourceDir . '/extract';
		mkdir( $extractDir );

		$extractList = $helper->extractArch( $archName, $extractDir );

		$this->assertFalse( file_exists( $archName ) );

		foreach ( $extractList as $expectedFile )
		{
			$actualFile = str_replace( '/extract', $testDirName, $expectedFile );

			$this->assertFileEquals( $expectedFile, $actualFile );
		}
		// ----------------

		Miao_PHPUnit::rmdirr( $extractDir );
	}

	public function providerTestArch()
	{
		$exceptionName = 'Miao_Tools_Shell_Exception';

		$data = array();
		$sourceDir = Miao_PHPUnit::getSourceFolder( __METHOD__ );
		$archDir = Miao_Config::Main()->get( 'config.paths.tmp' );

		//просто файлики
		$data[] = array(
		array( $sourceDir . '/test1/1.txt', $sourceDir . '/test1/2.txt' ),
			'test.tar.gz',
			'/test1',
		$exceptionName );
		$data[] = array(
		array( $sourceDir . '/test1/1.txt', $sourceDir . '/test1/2.txt' ),
		$archDir . '/test.tgz',
			'/test1' );
		$data[] = array(
		array( $sourceDir . '/test1/1.txt', $sourceDir . '/test1/2.txt' ),
			'test.zxcvzx',
			'/test1',
		$exceptionName );

		//с директориями
		$data[] = array(
		array(
		$sourceDir . '/test1/2.txt',
		$sourceDir . '/test2/2.txt',
		$sourceDir . '/test2/subdir/1.txt' ),
		$archDir . 'test.tgz',
			'/test2' );
		$data[] = array(
		array(
		$sourceDir . '/test1/2.txt',
		$sourceDir . '/test2/2.txt',
		$sourceDir . '/test2/subdir' ),
		$archDir . 'test.tgz',
			'/test2' );

		return $data;
	}

	/**
	 * @dataProvider providerTestMakeDir
	 *
	 * @param unknown_type $dirname
	 * @param unknown_type $mode
	 */
	public function testMakeDir( $dirname, $mode )
	{
		$shell = Miao_Tools_Shell::getInstance();
		$shell->makeDir( $dirname, $mode );

		$this->assertFileExists( $dirname );
		$this->assertTrue( is_dir( $dirname ) );

		rmdir( $dirname );

		$this->assertFileNotExists( $dirname );
	}

	public function providerTestMakeDir()
	{
		$data = array();

		$sourceDir = Miao_PHPUnit::getSourceFolder( __METHOD__ );
		$data[] = array( $sourceDir, 777 );

		return $data;
	}

	/**
	 *
	 * @dataProvider providerTestIconvFile
	 * @author vpak 30.11.2010
	 */
	public function testIconvFile( $actual, $in_charset, $out_charset, $src, $dest = null, $exceptionName = '' )
	{
		$shell = Miao_Tools_Shell::getInstance();
		$expected = $shell->iconvFile( $in_charset, $out_charset, $src, $dest );

		$this->assertFileEquals( $expected, $actual );
		unlink( $dest );
	}

	public function providerTestIconvFile()
	{
		$data = array();

		$sourceDir = Miao_PHPUnit::getSourceFolder( __METHOD__ );
		$actual = $sourceDir . '/text_1_win.txt';
		$src = $sourceDir . '/text_1_utf.txt';
		$dest = $sourceDir . '/tmp.txt';
		$data[] = array( $actual, 'UTF-8', 'WINDOWS-1251', $src, $dest );

		$sourceDir = Miao_PHPUnit::getSourceFolder( __METHOD__ );
		$actual = $sourceDir . '/text_1_utf.txt';
		$src = $sourceDir . '/text_1_win.txt';
		$dest = $sourceDir . '/tmp.txt';
		$data[] = array( $actual, 'WINDOWS-1251', 'UTF-8', $src, $dest );

		return $data;
	}

	public function testLog()
	{
		$filename = Miao_PHPUnit::getTempPath() . '/test_shell_log';
		$log = Miao_Log::easyFactory($filename);

		$shell = Miao_Tools_Shell::getInstance();
		$shell->setLog($log);
		$cmd = 'ls -la';
		$shell->shellExec( $cmd );

		$content = file_get_contents( $filename );
		$this->assertTrue( !empty( $content ) );

		unlink($filename);
	}
}