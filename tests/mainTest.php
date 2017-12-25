<?php
/**
 * test
 */
class mainTest extends PHPUnit_Framework_TestCase{

	/**
	 * ファイルシステムユーティリティ
	 */
	private $fs;

	/**
	 * setup
	 */
	public function setup(){
		// $this->fs = new \tomk79\filesystem();
	}

	/**
	 * 変換を実行してみる
	 */
	public function testMain(){

		$ampConv = new tomk79\ampConvert\main();

		$html = file_get_contents(__DIR__.'/testdata/part.html');
		$amp = $ampConv->convert($html);
		// var_dump($amp);

		$this->assertTrue( gettype($amp) == gettype('') );


		$html = file_get_contents(__DIR__.'/testdata/full.html');
		$amp = $ampConv->convert($html);
		// var_dump($amp);

		$this->assertTrue( gettype($amp) == gettype('') );


	}//testMain()

}
