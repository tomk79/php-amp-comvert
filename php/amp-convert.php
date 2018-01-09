<?php
/**
 * AMP Converter
 */
namespace tomk79\ampConvert;

use Lullabot\AMP\AMP;
use Lullabot\AMP\Validate\Scope;

/**
 * AMP Converter class
 */
class AMPConverter{

	/** HTMLコード */
	private $html;

	/**
	 * コストラクタ
	 */
	public function __construct(){
	}

	/**
	 * HTMLコードを読み込む
	 * @param  String $html HTMLソースコード
	 * @return Boolean 常に `true`
	 */
	public function load($html){
		$this->html = $html;
		return true;
	}

	/**
	 * 変換を実行する
	 * @return String AMP変換後のソースコード
	 */
	public function convert(){

		// HTML DOM
		$simple_html_dom = str_get_html(
			$this->html ,
			true, // $lowercase
			true, // $forceTagsClosed
			DEFAULT_TARGET_CHARSET, // $target_charset
			false, // $stripRN
			DEFAULT_BR_TEXT, // $defaultBRText
			DEFAULT_SPAN_TEXT // $defaultSpanText
		);
		$ret = $simple_html_dom->find('body');
		if(count($ret)){
			foreach( $ret as $retRow ){
				// AMP 変換
				$amp = new AMP();
				$amp->loadHtml($retRow->innertext);
				$retRow->innertext = $amp->convertToAmpHtml();
			}
		}else{
			// AMP 変換
			$amp = new AMP();
			$amp->loadHtml($simple_html_dom->outertext);
			$simple_html_dom->outertext = $amp->convertToAmpHtml();
		}

		// HTML要素に `amp` 属性を付加
		$ret = $simple_html_dom->find('html');
		foreach( $ret as $retRow ){
			$retRow->amp = true;
		}

		return $simple_html_dom->outertext;
	}

}
