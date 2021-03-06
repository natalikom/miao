<?php
abstract class Miao_Session_Handler
{
	/**
	 *
	 * @return Miao_Session_Handler
	 */
	static public function factory()
	{
		$className = 'none';
		if ( Miao_Config::checkConfig( __CLASS__ ) )
		{
			$config = Miao_Config::Libs( __CLASS__, false );
			$className = $config->get( 'type', $className );
		}
		$className = 'Miao_Session_Handler_' . ucfirst( $className );
		$result = new $className();
		return $result;
	}

	abstract public function init();
}