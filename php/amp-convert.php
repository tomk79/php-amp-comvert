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

	/** 変換オプション */
	private $convert_options;

	/** ユーティリティ */
	private $utils;

	/** style属性値のコレクション */
	private $style_attribute_collection = array();

	/** 許可されたWebフォント配布元 origin */
	private $allowed_webfont_providers_origin = array(
		'cloud.typography.com',
		'fast.fonts.net',
		'fonts.googleapis.com',
		'use.typekit.net',
		'maxcdn.bootstrapcdn.com',
		'use.fontawesome.com',
	);

	/**
	 * コストラクタ
	 */
	public function __construct(){
		$this->utils = new utils();
	}

	/**
	 * HTMLコードを読み込む
	 * @param  string $html HTMLソースコード
	 * @return boolean 常に `true`
	 */
	public function load($html){
		$this->html_original = $html;
		return true;
	}

	/**
	 * 変換を実行する
	 * @param  array $options オプション
	 * @return string AMP変換後のソースコード
	 */
	public function convert($options = array()){
		if(!is_array($options)){
			$options = array();
		}
		$this->convert_options = $options;

		$html = $this->html_original;

		// DOCTYPE を書き換える
		$html = preg_replace('/^(?:\s*\<!DOCTYPE.*?>)?/is', '<!DOCTYPE html>', $html);

		set_time_limit(60);

		// body要素をAMP変換する
		$html = $this->convert_body_to_amp($html);

		set_time_limit(60);

		// head要素をAMP変換する
		$html = $this->convert_head_to_amp($html);

		set_time_limit(60);

		// script要素をAMP変換する
		$html = $this->convert_script_to_amp($html);

		set_time_limit(60);

		// style要素をAMP変換する
		$html = $this->convert_style_to_amp($html);

		set_time_limit(60);

		// 条件付きコメントを削除する
		$html = $this->remove_conditional_comment($html);

		set_time_limit(60);

		// HTML要素に `amp` 属性を付加
		$simple_html_dom = $this->utils->create_simple_html_dom($html);
		$ret = $simple_html_dom->find('html');
		foreach( $ret as $retRow ){
			$retRow->amp = true;
		}
		$html = $simple_html_dom->outertext;

		set_time_limit(30);
		return $html;
	}

	/**
	 * script要素をAMP変換する
	 * @param  string $html_src HTMLソース
	 * @return string 変換されたHTMLソース
	 */
	private function convert_script_to_amp($html_src){
		$simple_html_dom = $this->utils->create_simple_html_dom($html_src);

		$ret = $simple_html_dom->find('script');
		foreach( $ret as $script ){
			if( !is_object($script) ){ continue; }
			$tmpType = null;
			if(array_key_exists('type', $script->attr)){
				$tmpType = $script->attr['type'];
			}
			if( $tmpType == 'application/ld+json' ){
				// JSON-LD情報は残す
				continue;
			}
			if( $tmpType == 'application/json' ){
				// JSONは残す (実行できない形式のデータは許容される)
				continue;
			}
			if( $tmpType == 'text/json' ){
				// JSONは残す (実行できない形式のデータは許容される)
				continue;
			}

			$tmpAsync = null;
			if(array_key_exists('async', $script->attr)){
				$tmpAsync = $script->attr['async'];
			}
			$tmpSrc = null;
			if(array_key_exists('src', $script->attr)){
				$tmpSrc = $script->attr['src'];
			}
			if( $tmpAsync && preg_match('/^'.preg_quote('https://cdn.ampproject.org/', '/').'.*/', $tmpSrc.'') ){
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
		$stylesheet_contents = '';

		$extract_linked_outernal_css = new extract_linked_outernal_css($this, $this->utils, $this->convert_options);
		$stylesheet_contents .= $extract_linked_outernal_css->extract($html_src);

		$simple_html_dom = $this->utils->create_simple_html_dom($html_src);

		$ret = $simple_html_dom->find('link[rel=stylesheet]');
		foreach( $ret as $link ){
			if( $this->is_url_webfont_provider($link->attr['href']) ){
				continue;
			}
			$link->outertext = '';
		}

		$ret = $simple_html_dom->find('style');
		foreach( $ret as $style ){
			if( @$style->attr['amp-boilerplate'] ){
				// boilerplateは残す
				continue;
			}

			$stylesheet_contents .= $style->innertext;
			$style->attr['amp-custom'] = true;
		}

		foreach($this->style_attribute_collection as $class_name => $style){
			// style属性から収集したスタイルを追加
			$stylesheet_contents .= '.'.$class_name.'{'.$style.'}'."\n";
		}

		$stylesheet_contents = preg_replace('/\@charset\s+(\"|\')[a-zA-Z0-9\_\-]+\1\s*\;?/s', '', $stylesheet_contents);

		foreach( $ret as $style ){
			if( @$style->attr['amp-boilerplate'] ){
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

		$html_src = $simple_html_dom->outertext;
		return $html_src;
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
		$simple_html_dom = $this->utils->create_simple_html_dom($html_src);

		$title = '';
		$ampproject_js = trim(file_get_contents(__DIR__.'/../resources/ampproject_v0js.html'));
		$ampproject_amp_iframe = trim(file_get_contents(__DIR__.'/../resources/ampproject_v0_amp_iframe.html'));
		$ampproject_amp_video = trim(file_get_contents(__DIR__.'/../resources/ampproject_v0_amp_video.html'));
		$ampproject_amp_audio = trim(file_get_contents(__DIR__.'/../resources/ampproject_v0_amp_audio.html'));
		$boilerplate = trim(file_get_contents(__DIR__.'/../resources/boilerplate.html'));

		$ret = $simple_html_dom->find('head');
		if(!count($ret)){
			// headセクションがなければスキップ
			return $simple_html_dom->outertext;
		}
		foreach( $ret as $head ){
			$topIndent = preg_replace('/^(\s*)(.*?)$/s', '$1', $head->innertext);

			// title
			$tmpRet = $head->find('title');
			foreach($tmpRet as $tmpRetRow){
				$title .= $tmpRetRow->innertext;
				$tmpRetRow->outertext = '';
			}

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

			// viewport を持つ meta 要素を先頭に移動
			$tmpRet = @$head->find('meta[name=viewport]');
			$viewport = '';
			if( count($tmpRet) ){
				$viewport = @$tmpRet[0]->attr['content'];
				foreach($tmpRet as $tmpRetRow){
					$tmpRetRow->outertext = '';
				}
			}

			$headInnerText = '';
			$headInnerText .= $topIndent.$tmpOutertext;
			$headInnerText .= $topIndent.'<title>'.$title.'</title>';
			$headInnerText .= $topIndent.$boilerplate;
			$headInnerText .= $topIndent.$ampproject_js;
			$headInnerText .= $topIndent.'<meta name="viewport" content="'.htmlspecialchars($this->optimize_viewport($viewport)).'" />';
			if( preg_match( '/\<amp\-iframe/s', $html_src ) ){
				$headInnerText .= $topIndent.$ampproject_amp_iframe;
			}
			if( preg_match( '/\<amp\-video/s', $html_src ) ){
				$headInnerText .= $topIndent.$ampproject_amp_video;
			}
			if( preg_match( '/\<amp\-audio/s', $html_src ) ){
				$headInnerText .= $topIndent.$ampproject_amp_audio;
			}
			$headInnerText .= $topIndent.'<style></style>'; // link[rel=stylesheet] があって style がない場合のために、器を用意する
			$headInnerText .= $head->innertext;
			$head->innertext = $headInnerText;

		}

		return $simple_html_dom->outertext;
	}

	/**
	 * viewport を調整する
	 * @param  string $viewport 元のviewport値
	 * @return string 整理し直されたviewport値
	 */
	private function optimize_viewport( $viewport ){
		$viewport_ary = array();
		$tmp_viewport_ary = explode(',', $viewport);
		foreach($tmp_viewport_ary as $tmp_viewport_row){
			$tmp_viewport_row = trim($tmp_viewport_row);
			if(!strlen($tmp_viewport_row)) {continue;}
			list($tmp_key, $tmp_val) = explode('=', $tmp_viewport_row);
			$viewport_ary[trim($tmp_key)] = trim($tmp_val);
		}

		// AMP仕様による固定値を強制指定
		$viewport_ary['width'] = 'device-width';
		$viewport_ary['minimum-scale'] = '1';

		$tmp_viewport_ary = array();
		foreach( $viewport_ary as $tmp_key=>$tmp_val ){
			array_push($tmp_viewport_ary, $tmp_key.'='.$tmp_val);
		}
		$viewport = implode(',', $tmp_viewport_ary);
		unset($tmp_viewport_ary, $tmp_viewport_row, $tmp_key, $tmp_val);
		return $viewport;
	}

	/**
	 * body要素をAMP変換する
	 * @param  string $html_src HTMLソース
	 * @return string 変換されたHTMLソース
	 */
	private function convert_body_to_amp($html_src){

		// style属性をスキャン
		$simple_html_dom = $this->utils->create_simple_html_dom($html_src);
		$styleAttrs = $simple_html_dom->find('*[style]');
		$this->style_attribute_collection = array();
		$class_num = 0;
		$class_idxs = array();
		foreach( $styleAttrs as $element ){
			$tmp_css = $element->attr['style'];
			$class_idx = md5($tmp_css);
			if( !array_key_exists($class_idx, $class_idxs) ){
				$class_idxs[$class_idx] = $class_num ++;
			}
			$class_name = 'amp-css-'.$class_idxs[$class_idx];

			$this->style_attribute_collection[$class_name] = $tmp_css;
			if(!@strlen($element->attr['class'])){
				$element->attr[' class'] = $class_name;
			}else{
				$element->attr['class'] = implode(' ', array($element->attr['class'], $class_name));
			}
		}
		$html_src = $simple_html_dom->outertext;

		// img要素をスキャン
		$simple_html_dom = $this->utils->create_simple_html_dom($html_src);
		$imgs = $simple_html_dom->find('img');
		foreach( $imgs as $img ){
			if( !is_callable($this->convert_options['read_file']) || !is_callable('getimagesizefromstring') ){
				continue;
			}
			$file_content = call_user_func($this->convert_options['read_file'], $img->attr['src']);
			$image_info = getimagesizefromstring($file_content);
			if(!@strlen($img->attr['width']) && @$image_info[0]){
				$img->attr[' width'] = $image_info[0].'';
			}
			if(!@strlen($img->attr['height']) && @$image_info[1]){
				$img->attr[' height'] = $image_info[1].'';
			}
		}
		$html_src = $simple_html_dom->outertext;

		$simple_html_dom = $this->utils->create_simple_html_dom($html_src);
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
		$html_src = $simple_html_dom->outertext;

		return $html_src;
	}

	/**
	 * style属性値のコレクションを取得する
	 */
	public function get_style_attribute_collection(){
		return $this->style_attribute_collection;
	}

	/**
	 * URLが許可されたWebフォント提供元かどうか調べる
	 */
	public function is_url_webfont_provider( $url ){
		$origins = $this->allowed_webfont_providers_origin;
		foreach($origins as $origin){
			if( preg_match('/^(?:https?\:)?\/\/'.preg_quote($origin,'/').'\//s', $url) ){
				return true;
			}
		}
		return false;
	}
}
