<?php
/**
 * AMP Converter
 */
namespace tomk79\ampConvert;

/**
 * AMP Converter class
 */
class utils{

	/**
	 * コストラクタ
	 */
	public function __construct(){
	}

	/**
	 * Simple HTML DOM オブジェクトを生成する
	 * @param  string $src HTMLソースコード
	 * @return object Simple HTML DOM オブジェクト
	 */
	public function create_simple_html_dom( $src ){
		// Simple HTML DOM
		$simple_html_dom = str_get_html(
			$src ,
			true, // $lowercase
			true, // $forceTagsClosed
			DEFAULT_TARGET_CHARSET, // $target_charset
			false, // $stripRN
			DEFAULT_BR_TEXT, // $defaultBRText
			DEFAULT_SPAN_TEXT // $defaultSpanText
		);
		return $simple_html_dom;
	}

}
