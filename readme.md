# これは何？
最寄り駅探索APIです。指定された緯度・経度から最寄り駅の名前を見つけます。
駅データは国土数値情報 駅別乗降客数を基にして、これを扱いやすいようにPHPシリアライズデータとして再整形しています。

国土数値情報 駅別乗降客数データ（S12-12.xml）

http://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-S12.html

# 使い方

## 最寄り駅表示（index.php?mode=near）

緯度経度を指定して、最寄り駅名を表示します

  - mode - "near"を指定
  - x - 緯度を指定 (WGS84)
  - y - 経度を指定 (WGS84)

http://www.railmaps.jp/api/stations/?mode=near&x=35.658611&y=139.745556 （東京タワーの最寄り駅/試験公開API/外部から利用しないでください）

## 駅データ一覧表示（index.php?mode=load）

駅データを一覧表示します


http://www.railmaps.jp/api/stations/?mode=load （試験公開API/外部から利用しないでください）

## データ再整形（index.php?mode=build）

再整形したデータはstations.serializedというデータになっているので不要ですが、基となるデータを新しいものに変えて、それを再整形した場合にお使いください。

その際、フォルダ内にXMLデータを配置した上で、下記のファイル名があっているかご確認ください。

```
function loadXML(){
  $filename = "S12-12.xml";
```

なお、駅名を途中でひらがなに直す処理をしているので、無駄にmecabに依存しています。よろしくお願い致します。

# ライセンス
- [MITライセンス](http://opensource.org/licenses/MIT)
- [国土数値情報](http://nlftp.mlit.go.jp/ksj/other/yakkan.html)は利用約款上はオープンライセンスを志向しているように思えますが、明記されていなかったので同梱しませんでした（同梱しなくてもAPI自体は動くので・・）