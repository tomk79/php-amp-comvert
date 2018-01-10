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
	 * Simple HTML DOM オブジェクトを生成する
	 * @param  string $src HTMLソースコード
	 * @return object Simple HTML DOM オブジェクト
	 */
	private function create_simple_html_dom( $src ){
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

	/**
	 * 変換を実行する
	 * @return string AMP変換後のソースコード
	 */
	public function convert(){
		$html = $this->html_original;

		// DOCTYPE を書き換える
		$html = preg_replace('/^(?:\s*\<!DOCTYPE.*?>)?/is', '<!DOCTYPE html>', $html);

		// body要素をAMP変換する
		$html = $this->convert_body_to_amp($html);

		// head要素をAMP変換する
		$html = $this->convert_head_to_amp($html);

		// script要素をAMP変換する
		$html = $this->convert_script_to_amp($html);

		// style要素をAMP変換する
		$html = $this->convert_style_to_amp($html);

		// 条件付きコメントを削除する
		$html = $this->remove_conditional_comment($html);

		// HTML要素に `amp` 属性を付加
		$simple_html_dom = $this->create_simple_html_dom($html);
		$ret = $simple_html_dom->find('html');
		foreach( $ret as $retRow ){
			$retRow->amp = true;
		}
		$html = $simple_html_dom->outertext;

		return $html;
	}

	/**
	 * script要素をAMP変換する
	 * @param  string $html_src HTMLソース
	 * @return string 変換されたHTMLソース
	 */
	private function convert_script_to_amp($html_src){
		$simple_html_dom = $this->create_simple_html_dom($html_src);

		$ret = $simple_html_dom->find('script');
		foreach( $ret as $script ){
			if( $script->attr['type'] == 'application/ld+json' ){
				// JSON-LD情報は残す
				continue;
			}
			if( $script->attr['async'] && $script->attr['src'] == 'https://cdn.ampproject.org/v0.js' ){
				// AMPが要求するJSは残す
				continue;
			}

			$script->outertext = '';
		}

		return $simple_html_dom->outertext;
	}

	/**
	 * style要素をAMP変換する
	 * @param  string $html_src HTMLソース
	 * @return string 変換されたHTMLソース
	 */
	private function convert_style_to_amp($html_src){
		// 先にこれをしておかないと、style の後にスペースが入らない (Simple HTML DOM のバグ？)
		$html_src = preg_replace('/\<style\>/', '<style amp-custom>', $html_src);

		$simple_html_dom = $this->create_simple_html_dom($html_src);

		$ret = $simple_html_dom->find('style');
		$stylesheet_contents = '';
		foreach( $ret as $style ){
			if( $style->attr['amp-boilerplate'] ){
				// boilerplateは残す
				continue;
			}

			$stylesheet_contents .= $style->innertext;
			$style->attr['amp-custom'] = true;
		}
		foreach( $ret as $style ){
			if( $style->attr['amp-boilerplate'] ){
				// boilerplateは残す
				continue;
			}

			if( strlen($stylesheet_contents) ){
				$style->innertext = $stylesheet_contents;
				$stylesheet_contents = '';
			}else{
				$style->outertext = '';
			}
		}

		return $simple_html_dom->outertext;
	}

	/**
	 * 条件コメントを削除する
	 * @param  string $html_src HTMLソース
	 * @return string 変換されたHTMLソース
	 */
	private function remove_conditional_comment($html_src){
		$html_src = preg_replace('/\<\!\-\-\[.*?\]\>.*?\<\!\[.*?\]\-\-\>/s', '', $html_src);
		return $html_src;
	}

	/**
	 * head要素をAMP変換する
	 * @param  string $html_src HTMLソース
	 * @return string 変換されたHTMLソース
	 */
	private function convert_head_to_amp($html_src){
		$simple_html_dom = $this->create_simple_html_dom($html_src);

		$ampproject_v0js = trim(file_get_contents(__DIR__.'/../resources/ampproject_v0js.html'));
		$boilerplate = trim(file_get_contents(__DIR__.'/../resources/boilerplate.html'));

		$ret = $simple_html_dom->find('head');
		if(!count($ret)){
			// headセクションがなければスキップ
			return $simple_html_dom->outertext;
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

		return $simple_html_dom->outertext;
	}

	/**
	 * body要素をAMP変換する
	 * @param  string $html_src HTMLソース
	 * @return string 変換されたHTMLソース
	 */
	private function convert_body_to_amp($html_src){
		$simple_html_dom = $this->create_simple_html_dom($html_src);

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

		return $simple_html_dom->outertext;
	}

}
