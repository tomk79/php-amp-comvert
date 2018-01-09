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

### 自動的に変換されない項目

次の項目は自動的に処理されません。コーディング上の配慮が必要です。

- 文書宣言は、 `<!DOCTYPE html>` にすること。
- 通常ページの `rel=amphtml` の link要素の href属性は、AMPページのURLを指定する。
- AMPページの `rel=canonical` の link要素の href属性は、通常ページのURLを指定する。存在しなければ自身(AMPページ)のURLを指定する。
- head要素の最初の子要素は `<meta charset="utf-8">` でなければいけない。
- head要素に `<meta name="viewport" content="width=device-width,minimum-scale=1">` を含めなければいけない。 `initial-scale=1` を加えることが望ましい。
- head要素に `<script async src="https://cdn.ampproject.org/v0.js"></script>` を含めなければいけない。
- head要素に boilerplate のスタイルシートを含めなければいけない。
- JSON-LD形式の [schema.org](http://schema.org/) のマークアップを含めることが推奨される。
- オープングラフ や Twitterカード の meta要素を 含めることが推奨される。
- HTMLで条件付きコメントは使用できない。(`<!--[if IE 6]>`)
- JavaScriptは使用できない。script要素だけでなく、on〜属性、href属性による利用も含めて禁止。
- HTMLはタグの制限事項、属性の制限事項を違反しないこと。
- スタイルシートは style要素に amp-custom属性 を付けてインラインで記述すること。 50KBを超えない範囲で、スタイルシートの制限事項を違反しないこと。
- 外部のスタイルシートはカスタムフォントを利用する以外では読み込めない。

### 要素

- script
	- JavaScriptは禁止されています。 script要素は、 `type=application/ld+json` を付けてJSON-LDとしてなら利用できます。
- img
	- 禁止されています。代わりに amp-img要素を利用します。
- video
	- 禁止されています。代わりに amp-video要素を利用します。
- audio
	- 禁止されています。代わりに amp-audio要素を利用します。
- iframe
	- 禁止されています。代わりに amp-iframe要素を利用します。
- form
	- `amp-form` のライブラリを読み込む必要があります。
- input
	- type属性が `image`、 `button`、 `password`、 `file` は禁止されています。
- style
	- head要素内の style要素には、 `amp-custom` 属性を付けて下さい。
- link
	- rel属性には、 [microformats.org](http://microformats.org/) に登録されている値を指定できます。 ただし、 `stylesheet`(許可されたカスタムフォントを除く)、 `preconnect`、 `prerender`、 `prefetch` は禁止されています。
- meta
	- http-equiv属性は禁止されています。
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

- style属性は使用できない。
- `xml:lang`、 `xml:base` など、XML関連の属性は使用できない。
- `onclick`、 `onchange` など、onで始まる全ての属性は使用できない。
- id属性、class属性では、 `-amp-`、 または `i-amp-` を含む値は使用できない。ただし、一部のコンポーネントではカスタマイズが許可される場合がある。

### スタイルシート

- style要素には `amp-custom` 属性を付けなければいけない。
- スタイルシートの内容は、50,000byte(50KB)以内に抑えなければいけない。
- `@-rules` は、 `@font-face`、 `@keyframes`、 `@media`、 `@supports` 以外は使用できない。
- 修飾子の `!important` は使用できない。
- behaviorプロパティ、 `-moz-binding` プロパティ、 `filter` プロパティは使用できない。
- `overflow`、 `overflow-x`、 `overflow-y` に `auto`、 `scroll` を指定してはいけない。AMPでは、ユーザーがコンテンツにスクロールバーを持たすことができない。

### AMPコンポーネント

- amp-access
- amp-access-laterpay
- amp-accordion
- amp-ad
- amp-analytics
- amp-anim
- amp-animation
- amp-apester-media
- amp-app-banner
- amp-bind
- amp-brid-player
- amp-brightcove
- amp-carousel
- amp-dailymotion
- amp-dynamic-css-classes
- amp-experiment
- amp-facebook
- amp-fit-text
- amp-font
- amp-form
- amp-fx-flying-carpet
- amp-gfycat
- amp-google-vrview-image
- amp-hulu
- amp-image-lightbox
- amp-instagram
- amp-install-serviceworker
- amp-jwplayer
- amp-kaltura-player
- amp-lightbox
- amp-list
- amp-live-list
- amp-mustache
- amp-o2-player
- amp-ooyala-player
- amp-pinterest
- amp-pixel
- amp-playbuzz
- amp-reach-player
- amp-reddit
- amp-selector
- amp-share-tracking
- amp-sidebar
- amp-social-share
- amp-soundcloud
- amp-springboard-player
- amp-sticky-ad
- amp-twitter
- amp-user-notification
- amp-vimeo
- amp-vine
- amp-viz-vega
- amp-youtube


## 更新履歴 - Change log

### tomk79/amp-convert dev-develop (2018年??月??日)

- Initial release.


## ライセンス - License

MIT License


## 作者 - Author

- Tomoya Koyanagi <tomk79@gmail.com>
- website: <http://www.pxt.jp/>
- Twitter: @tomk79 <http://twitter.com/tomk79/>
