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

	/** 加工前のオリジナル HTMLコード */
	private $html_original;

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
		$this->html_original = $html;
		return true;
	}

	/**
	 * 変換を実行する
	 * @return String AMP変換後のソースコード
	 */
	public function convert(){
		$html = $this->html_original;

		// DOCTYPE を書き換える
		$html = preg_replace('/^(?:\s*\<!DOCTYPE.*?>)?/is', '<!DOCTYPE html>', $html);

		// Simple HTML DOM
		$simple_html_dom = str_get_html(
			$html ,
			true, // $lowercase
			true, // $forceTagsClosed
			DEFAULT_TARGET_CHARSET, // $target_charset
			false, // $stripRN
			DEFAULT_BR_TEXT, // $defaultBRText
			DEFAULT_SPAN_TEXT // $defaultSpanText
		);

		// head要素をAMP変換する
		$this->convert_head_to_amp($simple_html_dom);

		// body要素をAMP変換する
		$this->convert_body_to_amp($simple_html_dom);

		// HTML要素に `amp` 属性を付加
		$ret = $simple_html_dom->find('html');
		foreach( $ret as $retRow ){
			$retRow->amp = true;
		}

		return $simple_html_dom->outertext;
	}

	/**
	 * head要素をAMP変換する
	 * @param  object $simple_html_dom Simple HTML DOM オブジェクト
	 * @return void このメソッドは値を返しません
	 */
	private function convert_head_to_amp($simple_html_dom){
		$ampproject_v0js = trim(file_get_contents(__DIR__.'/../resources/ampproject_v0js.html'));
		$boilerplate = trim(file_get_contents(__DIR__.'/../resources/boilerplate.html'));

		$ret = $simple_html_dom->find('head');
		if(!count($ret)){
			// headセクションがなければスキップ
			return;
		}
		foreach( $ret as $head ){
			$topIndent = preg_replace('/^(\s*)(.*?)$/s', '$1', $head->innertext);

			// `http-equiv` を持つ meta 要素を削除
			$tmpRet = $head->find('meta[http-equiv]');
			foreach($tmpRet as $tmpRetRow){
				$tmpRetRow->outertext = '';
			}

			// `charset` を持つ meta 要素を先頭に移動
			$tmpRet = @$head->find('meta[charset]');
			if( !count($tmpRet) ){
				$tmpOutertext = '<meta charset="utf-8" />';
			}else{
				$tmpOutertext = '';
				foreach($tmpRet as $tmpRetRow){
					$tmpOutertext .= $tmpRetRow->outertext;
					$tmpRetRow->outertext = '';
				}
			}
			$headInnerText = '';
			$headInnerText .= $topIndent.$tmpOutertext;
			$headInnerText .= $topIndent.$boilerplate;
			$headInnerText .= $topIndent.$ampproject_v0js;
			$headInnerText .= $head->innertext;
			$head->innertext = $headInnerText;

		}

		return;
	}

	/**
	 * body要素をAMP変換する
	 * @param  object $simple_html_dom Simple HTML DOM オブジェクト
	 * @return void このメソッドは値を返しません
	 */
	private function convert_body_to_amp($simple_html_dom){
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
		return;
	}

}
