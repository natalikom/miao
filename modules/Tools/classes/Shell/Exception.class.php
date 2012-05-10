<?php
/**
 * UniPg
 * @package Tools
 */

/**
 *
 * @package Tools
 * @subpackage Tools_Shell
 *
 * @author vpak
 *
 */
class Miao_Tools_Shell_Exception extends Miao_Tools_Exception
{
	public function __construct( $message, $method = '', $code = null )
	{
		$message = sprintf( '%s: %s', $method, $message );
		parent::__construct( $message );
	}
}
