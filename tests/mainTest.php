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

		$html_file_list = array(
			'part',
			'full',
			'full_min',
		);

		foreach($html_file_list as $html_file_name){
			$html = file_get_contents(__DIR__.'/testdata/'.$html_file_name.'.html');
			$ampConv = new tomk79\ampConvert\AMPConverter();

			$result = $ampConv->load($html);
			$this->assertTrue( $result );

			$amp = $ampConv->convert();
			// var_dump($amp);
			$this->fs->save_file( __DIR__.'/testdata/'.$html_file_name.'.amp.html', $amp );

			$this->assertTrue( gettype($amp) == gettype('') );
		}

	}//testMain()

}
