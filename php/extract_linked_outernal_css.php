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
			$file_content = call_user_func($this->convert_options['read_file'], $path);
			// ひとまず: 開いたファイルをそのまま結合する。
			// TODO: url() や import() などを検索し、再帰的にファイルを取得して結合する
			$rtn .= $file_content;
		}
		return $rtn;
	}
}
