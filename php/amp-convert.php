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
class main{

	/**
	 * コストラクタ
	 */
	public function __construct(){
	}

	/**
	 * 変換を実行する
	 */
	public function convert($html){
		// AMP 変換
		$amp = new AMP();
		$amp->loadHtml($html);
		$html = $amp->convertToAmpHtml();
		return $html;
	}

}
