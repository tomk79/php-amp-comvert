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
		// AMP 変換
		$amp = new AMP();
		$amp->loadHtml($this->html);
		$html = $amp->convertToAmpHtml();
		return $html;
	}

}
