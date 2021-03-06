<?php
class Miao_Form_Control_Textarea_Test extends PHPUnit_Framework_TestCase
{

	/**
	 * @dataProvider providerTestMain
	 */
	public function testMain( $name, $actual, $value = '', $attributes = array(), $exceptionName = '' )
	{
		if ( !empty( $exceptionName ) )
		{
			$this->setExpectedException( $exceptionName );
		}

		$control = new Miao_Form_Control_Textarea( $name, $attributes );
		$control->setValue( $value );

		$expected = $control->render();

		$this->assertEquals( $expected, $actual );
	}

	public function providerTestMain()
	{
		$data = array();

		$actual = '<textarea name="name" class="input-xlarge">value</textarea>';
		$data[] = array(
			'name',
			$actual,
			'value',
			array( 'class' => 'input-xlarge' ) );

		return $data;
	}
}