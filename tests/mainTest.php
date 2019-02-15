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

			$styleAttrCollection = $ampConv->get_style_attribute_collection();
			$this->assertTrue( is_array($styleAttrCollection) );

			$this->assertTrue( gettype($amp) == gettype('') );
		}

	} // testDoConvert()

	/**
	 * 変換結果をチェックする
	 */
	public function testCheckOutput(){
		$path_base = __DIR__.'/testdata/';

		$html = file_get_contents($path_base.'full.amp.html');

		// LD JSON
		// and other data script
		$this->assertSame( preg_match('/'.preg_quote('"@context": "http://schema.org"', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<script type="application/json">', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('"dataType": "application/json"', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<script type="text/json">', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('"dataType": "text/json"', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('console.log(\'Normal JavaScript code.\');', '/').'/s', $html), 0 );

		// IE comment
		$this->assertSame( preg_match('/'.preg_quote('<!--[if IE 8]>', '/').'/s', $html), 0 );
		$this->assertSame( preg_match('/'.preg_quote('<!--[if lte IE 7]>', '/').'/s', $html), 0 );

		// WebFonts
		$this->assertSame( preg_match('/'.preg_quote('<link rel="stylesheet" href="https://fonts.googleapis.com/css.css" />', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<link rel="stylesheet" href="https://cloud.typography.com/css.css" />', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<link rel="stylesheet" href="https://fast.fonts.net/css.css" />', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<link rel="stylesheet" href="https://use.typekit.net/css.css" />', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/css.css" />', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<link rel="stylesheet" href="https://use.fontawesome.com/css.css" />', '/').'/s', $html), 1 );

		// CSS Import
		$this->assertSame( preg_match('/'.preg_quote('.imported004{ content: "imported004"; background-image: url("res/image.png"); }', '/').'/s', $html), 1 );

		// Inline CSS
		$this->assertSame( preg_match('/'.preg_quote('<div class="test3 amp-css-1"><p>style attribute 2</p></div>', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('.amp-css-1{color:#ff9;}', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<div class="amp-css-0"><p>style attribute 3</p></div>', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('.amp-css-0{color:#f9f;}', '/').'/s', $html), 1 );

		// AMP Images
		$this->assertSame( preg_match('/'.preg_quote('<div><amp-img src="./res/image.png" alt="Test Image" width="800" height="600" layout="responsive"></amp-img></div>', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<div><amp-img src="res/image.png" alt="Test Image (Not Closed)" width="800" height="600" layout="responsive"></amp-img></div>', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<div><amp-img src="res/image.png" width="20" height="30" alt="Test Image (大きさを指定されている)" layout="responsive"></amp-img></div>', '/').'/s', $html), 1 );

		// Other AMP tags
		$this->assertSame( preg_match('/'.preg_quote('<div><amp-iframe sandbox="allow-scripts allow-same-origin" layout="responsive" height="315" width="560" src="part.html"></amp-iframe></div>', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<div><amp-video src="res/video.mp4" layout="responsive" height="315" width="560"></amp-video></div>', '/').'/s', $html), 1 );
		$this->assertSame( preg_match('/'.preg_quote('<div><amp-audio src="res/audio.mp3"></amp-audio></div>', '/').'/s', $html), 1 );


	} // testCheckOutput()

}
