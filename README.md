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

- 文書宣言が `<!DOCTYPE html>` に揃えられます。ない場合は追加されます。
- html要素に amp属性 が追加されます。
- charaset属性を持ったmeta要素が、headセクションの先頭に移動されます。ない場合は追加されます。
- http-equiv属性を持ったmeta要素が削除されます。
- head要素に `style[amp-boilerplate]` が追加されます。
- head要素に `<script async src="https://cdn.ampproject.org/v0.js"></script>` が追加されます。
- head要素に `<meta name="viewport" content="width=device-width,minimum-scale=1">` が追加されます。既に viewport が存在する場合は、 `width` が `device-width` に、 `minimum-scale` が `1` に強制的に上書きされ、そのほかの値がある場合は維持されます。
- amp-iframe, amp-audio, amp-video の各要素が検出されるとき、head要素にそれぞれ必要な JavaScript ライブラリが追加されます。
- style要素に、 amp-custom属性が追加されます。 style要素が複数検出される場合は、1つに統合されます。
- style属性を持つ要素がある場合、 class名 に変換し、 スタイルを style要素内に追記し、 class として参照するように書き換えます。
- type属性が `application/ld+json` (JSON-LD形式)、`application/json` および `text/json` 以外の script要素が削除されます。
- `link[rel=stylesheet]` の参照するCSSが `style[amp-custom]` に結合されます。(ただし、`url()` や `@import` 等で参照されたファイルは結合されません)
- style要素中の `@charset`、 `!important`、 CSSコメント(`/* 〜 */`) が削除されます。
- 条件付きコメント(例： `<!--[if IE 6]>`)が削除されます。
- body要素の内容が [lullabot/amp](https://packagist.org/packages/lullabot/amp) で変換されます。
- img要素が amp-img要素に置き換えられます。画像ファイルの実体を参照可能な場合は、 `width` 、 `height` の属性を補完します。

### 自動的に変換されない項目

次の項目は自動的に処理されません。コーディング上の配慮が必要です。

- 通常ページの `rel=amphtml` の link要素の href属性は、AMPページのURLを指定してください。
- AMPページの `rel=canonical` の link要素の href属性は、通常ページのURLを指定してください。存在しなければ自身(AMPページ)のURLを指定します。
- head要素 の viewport に `initial-scale=1` を加えることが推奨されます。
- オープングラフ や Twitterカード の meta要素を 含めることが推奨されます。
- スタイルシートは、50KB以内におさめてください。
- 外部のスタイルシートはカスタムフォントを利用する以外では読み込めません。

### 要素

- form
	- `amp-form` のライブラリを読み込む必要があります。
- input
	- type属性が `image`、 `button`、 `password`、 `file` は禁止されています。
- link
	- rel属性には、 [microformats.org](http://microformats.org/) に登録されている値を指定できます。 ただし、 `stylesheet`(許可されたカスタムフォントを除く)、 `preconnect`、 `prerender`、 `prefetch` は禁止されています。
- a
	- href属性は `javascript:` で始めてはいけません。 target属性は `_blank` でなければいけません。
- base
- frame
- frameset
- object
- param
- applet
- embed
	- 禁止されています。

### 属性

- `xml:lang`、 `xml:base` など、XML関連の属性は使用できません。
- id属性、class属性では、 `-amp-`、 または `i-amp-` を含む値は使用できません。(ただし、一部のコンポーネントではカスタマイズが許可される場合があります)

### スタイルシート

- `@-rules` は、 `@font-face`、 `@keyframes`、 `@media`、 `@supports` 以外は使用できません。
- 修飾子の `!important` は使用できません。
- behaviorプロパティ、 `-moz-binding` プロパティ、 `filter` プロパティは使用できません。
- `overflow`、 `overflow-x`、 `overflow-y` に `auto`、 `scroll` を指定してはいけない。AMPでは、ユーザーがコンテンツにスクロールバーを持たすことができません。


## 更新履歴 - Change log

### tomk79/amp-convert v0.2.0 (2020年10月17日)

- Update `lullabot/amp` to `^2.0`

### tomk79/amp-convert v0.1.7 (2019年10月7日)

- PHP 7.3 系で発生する不具合を修正。
- インラインで書かれた `style` 属性値中に HTML特殊文字が含まれる場合に、正しく変換されない事がある不具合を修正。

### tomk79/amp-convert v0.1.6 (2019年2月17日)

- Webフォント配布サイトへの `<link rel="stylesheet" />` を変換しないようになった。

### tomk79/amp-convert v0.1.5 (2019年2月1日)

- インラインのstyle属性から生成される class 名が短くなった。

### tomk79/amp-convert v0.1.4 (2019年1月29日)

- style属性を持つ要素がある場合、 class名 に変換し、 スタイルを style要素内に追記し、 class として参照するように書き換えるようになった。
- `$ampConv->get_style_attribute_collection()` を追加。
- CSSの `@import` 句を読み込んで結合するようになった。

### tomk79/amp-convert v0.1.3 (2019年1月7日)

- type属性が `application/json`, `text/json` の scriptタグを削除しないようになった。

### tomk79/amp-convert v0.1.2 (2018年2月6日)

- amp-imgの変換方式を変更し、 `layout=responsive` が付加されるようになった。

### tomk79/amp-convert v0.1.1 (2018年2月5日)

- CSS中の `!important`、 CSSコメント(`/* 〜 */`) を削除するようになった。
- パースエラーが起きることがある不具合を修正。

### tomk79/amp-convert v0.1.0 (2018年2月4日)

- Initial release.


## ライセンス - License

MIT License


## 作者 - Author

- Tomoya Koyanagi <tomk79@gmail.com>
- website: <https://www.pxt.jp/>
- Twitter: @tomk79 <https://twitter.com/tomk79/>
