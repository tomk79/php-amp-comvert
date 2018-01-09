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
		$this->fs = new \tomk79\filesystem();
	}

	/**
	 * 変換を実行してみる
	 */
	public function testMain(){

		$html = file_get_contents(__DIR__.'/testdata/part.html');
		$ampConv = new tomk79\ampConvert\AMPConverter();

		$result = $ampConv->load($html);
		$this->assertTrue( $result );

		$amp = $ampConv->convert();
		// var_dump($amp);
		$this->fs->save_file( __DIR__.'/testoutput/part.html', $amp );

		$this->assertTrue( gettype($amp) == gettype('') );


		$html = file_get_contents(__DIR__.'/testdata/full.html');
		$result = $ampConv->load($html);
		$this->assertTrue( $result );

		$amp = $ampConv->convert($html);
		// var_dump($amp);
		$this->fs->save_file( __DIR__.'/testoutput/full.html', $amp );

		$this->assertTrue( gettype($amp) == gettype('') );


	}//testMain()

}
