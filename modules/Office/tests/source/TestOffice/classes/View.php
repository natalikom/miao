<?php
/**
 *
 * @author vpak
 * @date 2013-09-04 16:06:44
 */

namespace Miao\TestOffice;

class View extends \Miao\Office\Controller\View implements \Miao\Office\Controller\ViewInterface
{
    protected $_expires = 10;

    protected function _init()
	{
        $this->_initTemplateVariables();
        $this->_initHeaders();
	}

    /**
     * (non-PHPdoc)
     * @see \Miao\Office\Controller\ViewInterface
     */
	public function initializeBlock()
	{
    }

    protected function _initTemplateVariables()
    {
    }

    protected function _initHeaders()
    {
        $this->getOffice()->getHeader()->set( 'X-UA-Compatible', 'X-UA-Compatible: IE=Edge,chrome=1' );
        $this->getOffice()->getHeader()->set( 'X-Accel-Expires', 'X-Accel-Expires: ' . $this->_expires );
        $this->getOffice()->getHeader()->set( 'Cache-Control', 'Cache-Control: max-age=' . $this->_expires );
    }
}