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
	 * 変換を実行する
	 */
	public function testDoConvert(){

		$html_file_list = array(
			'part',
			'full',
			'full_min',
			'full_linkcss_nostyle',
		);

		foreach($html_file_list as $html_file_name){
			$html = file_get_contents(__DIR__.'/testdata/'.$html_file_name.'.html');
			$ampConv = new tomk79\ampConvert\AMPConverter();

			$result = $ampConv->load($html);
			$this->assertTrue( $result );

			$amp = $ampConv->convert(array(
				'read_file'=>function($path) use ($html_file_name){
					$realpath = null;
					if( preg_match('/^\//', $path) ){
						$realpath = __DIR__.'/testdata/'.preg_replace('/^\/+/', '', $path);
					}else{
						$realpath = __DIR__.'/testdata/'.dirname($html_file_name.'.html').'/'.$path;
					}
					if(!is_file($realpath)){return false;}
					return file_get_contents($realpath);
				}
			));
			// var_dump($amp);
			$this->fs->save_file( __DIR__.'/testdata/'.$html_file_name.'.amp.html', $amp );

			$this->assertTrue( gettype($amp) == gettype('') );
		}

	} // testDoConvert()

	/**
	 * 変換結果をチェックする
	 */
	public function testCheckOutput(){
		$path_base = __DIR__.'/testdata/';

		$html = file_get_contents($path_base.'full.amp.html');
		$this->assertSame( preg_match('/'.preg_quote('"@context": "http://schema.org"', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('"dataType": "application/json"', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('"dataType": "text/json"', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('console.log(\'Normal JavaScript code.\');', '/').'/s', $html), 0 );

	} // testCheckOutput()

}
