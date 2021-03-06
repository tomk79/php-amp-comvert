<?php
/**
 * AMP Converter
 */
namespace tomk79\ampConvert;

/**
 * AMP Converter class
 */
class extract_linked_outernal_css{

	/** amp-convert メインクラス */
	private $ampConv;

	/** ユーティリティ */
	private $utils;

	/** 変換オプション */
	private $convert_options;

	/**
	 * コストラクタ
	 * @param  object $ampConv amp-convert メインクラス
	 * @param  object $utils ユーティリティ
	 * @param  array $convert_options オプション
	 */
	public function __construct($ampConv, $utils, $convert_options){
        $this->ampConv = $ampConv;
        $this->utils = $utils;
        $this->convert_options = $convert_options;
	}

	/**
	 * 外部CSSを結合する
	 * @param  string $html_src Original HTML Source.
	 * @return string Linked outernal CSS Source.
	 */
	public function extract($html_src){
		if( !is_callable($this->convert_options['read_file']) ){
			return '';
		}
		$rtn = '';
		$simple_html_dom = $this->utils->create_simple_html_dom($html_src);
		$ret = $simple_html_dom->find('link[rel=stylesheet]');
		foreach( $ret as $link ){
			$path = $link->attr['href'];
			if( $this->ampConv->is_url_webfont_provider($path) ){
				continue;
			}
			$rtn .= $this->import_css($path);
		}
		return $rtn;
	}

	/**
	 * CSSファイルを読み込む
	 */
	private function import_css($path){
		if( !preg_match('/\//s', $path) ){
			$path = './'.$path;
		}
		$file_content = call_user_func($this->convert_options['read_file'], $path);
		if( !is_string($file_content) || !strlen($file_content) ){
			return '';
		}

		// !important を削除
		$file_content = preg_replace('/\s+\!important/s', '', $file_content);

		// CSSコメントを削除
		$file_content = preg_replace('/\/\*.*?\*\//s', '', $file_content);

		// url() を処理する
		$file_content = $this->process_url($file_content, $path);

		return $file_content;
	}

	/**
	 * url() を処理する
	 */
	private function process_url($src, $path){
		$rtn = '';
		while(1){
			if( !preg_match('/^(.*?)(?:(\@import\s+)?url\(\s*(\'|\"|)(.*?)\3\s*\)|\@import\s+(\'|\")(.*?)\5)\s*\;(.*)$/s', $src, $matched) ){
				$rtn .= $src;
				break;
			}

			$rtn .= $matched[1];
			$import = trim($matched[2]);
			$delimiter = $matched[3];
			$link = trim($matched[4]);
			if( !strlen($import) && !strlen($delimiter) && !strlen($link) ){
				$import = '@import';
				$delimiter = $matched[5];
				$link = trim($matched[6]);
			}
			$src = $matched[7];

			// var_dump($link);

			if( strlen( $import ) ){
				if( preg_match( '/^\//s', $link ) ){
					$rtn .= $this->import_css($link);
				}else{
					$rtn .= $this->import_css(dirname($path).'/'.$link);
				}
			}else{
				$rtn .= 'url("';
				if( preg_match( '/^(?:\/|[a-zA-Z0-9]+\:)/s', $link ) ){
					$rtn .= $link;
				}else{
					$rtn .= $this->resolve_path( dirname($path).'/'.$link );
				}
				$rtn .= '");';
			}
			continue;
		}
		return $rtn;
	}

	/**
	 * パスを解決する
	 */
	private function resolve_path($path){
		$paths = explode('/', $path);
		$rtn = array();
		foreach($paths as $cur){
			if( $cur == '..' ){
				array_pop($rtn);
			}elseif( $cur == '.' ){
				continue;
			}else{
				array_push($rtn, $cur);
			}
		}
		return implode('/', $rtn);
	}

}
