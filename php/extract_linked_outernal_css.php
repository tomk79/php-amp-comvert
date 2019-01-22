<?php
/**
 * AMP Converter
 */
namespace tomk79\ampConvert;

/**
 * AMP Converter class
 */
class extract_linked_outernal_css{

	/** ユーティリティ */
	private $utils;

	/** 変換オプション */
	private $convert_options;

	/**
	 * コストラクタ
	 * @param  object $utils ユーティリティ
	 * @param  array $convert_options オプション
	 */
	public function __construct($utils, $convert_options){
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

		// @import句を処理する
		$file_content = $this->process_import($file_content, $path);

		// url() を処理する
		$file_content = $this->process_url($file_content, $path);

		return $file_content;
	}

	/**
	 * @import句を処理する
	 */
	private function process_import($src, $path){
		$rtn = '';
		while(1){
			if( !preg_match('/^(.*?)\@import\s+url\(\s*(\'|\"|)(.*?)\2\s*\)\s*\;(.*)$/s', $src, $matched) ){
				$rtn .= $src;
				break;
			}

			$rtn .= $matched[1];
			$delimiter = $matched[2];
			$link = trim($matched[3]);
			$src = $matched[4];

			// var_dump($link);

			if( preg_match( '/^\//s', $link ) ){
				$rtn .= $this->import_css($link);
			}else{
				$rtn .= $this->import_css(dirname($path).'/'.$link);
			}

			continue;
		}
		return $rtn;
	}

	/**
	 * url() を処理する
	 */
	private function process_url($src, $path){
		// TODO: url() を検索し、再帰的にファイルを取得して結合する
		return $src;
	}

}
