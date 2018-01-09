# tomk79/amp-convert
HTML5で書かれたファイルを[AMPフォーマット](https://www.ampproject.org/ja/docs/reference/spec#required-markup)に変換します。


## 使い方 - Usage

```php
<?php
require_once('/path/to/vendor/autoload.php');
$ampConv = new tomk79\ampConvert\AMPConverter();

$html = file_get_contents('/path/to/sample.html');
$ampConv->load($html);
$amp = $ampConv->convert();

echo $amp;
```


## 変換処理

### 自動的に変換される項目

- body要素の内容が [lullabot/amp](https://packagist.org/packages/lullabot/amp) で変換されます。
- html要素に amp属性 が追加されます。



## 更新履歴 - Change log

### tomk79/amp-convert dev-develop (2018年??月??日)

- Initial release.


## ライセンス - License

MIT License


## 作者 - Author

- Tomoya Koyanagi <tomk79@gmail.com>
- website: <http://www.pxt.jp/>
- Twitter: @tomk79 <http://twitter.com/tomk79/>
