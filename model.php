<?php

class TrainDataIO{

// データ設定コントローラ
function buildData(){
  $this->loadXML();
  $this->setData();
  $this->setGeo();
  $this->write();
}


// XMLデータをSimpleXMLで読み込み
function loadXML(){
  $filename = "S12-12.xml";
  $this->xml = simplexml_load_file($filename);
  $this->xml->registerXPathNamespace("gml", "http://www.opengis.net/gml/3.2");
  $this->xml->registerXPathNamespace("ksj", "http://nlftp.mlit.go.jp/ksj/schemas/ksj-app");
  $this->xml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
  $this->xml->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");

  $this->elements = array("snm", "aco", "rnm", "crd", "crc", "cdp", "cen", "rmk", "p11");
  $this->elements_after = array("name", "company", "line", "railGroup", "companyGroup", "duplication", "existence", "remarks", "passengers");
}

// データ設定
function setData(){

  $root = "/ksj:Dataset/ksj:TheNumberofTheStationPassengersGettingonandoff";

  // 各アイテム毎＆1駅毎に処理
  foreach ($this->elements as $element){
    $id = 1;
    $this->elements = array("snm", "aco", "rnm", "crd", "crc", "cdp", "cen", "rmk", "p11");
    $this->elements_after = array("name", "company", "line", "railGroup", "companyGroup", "duplication", "existence", "remarks", "passengers");
    
    // 属性ごとに値を挿入
    foreach ($this->xml->xpath($root."/ksj:".$element) as $value){
      // 要らない奴はスルー
      if ($element == "crd" || $element == "crc" || $element == "cdp" || $element == "cen" || $element == "rmk" || $element == "p11") break;
      // 駅名はひらがなデータも持っておく
      if ($element == "snm") $this->stations[$id]["Yomi"] = $this->kanji2kana($value);
      // 項目名を置換
      $attr = str_replace($this->elements, $this->elements_after, $element);
      // 駅名・データ設定
      $this->stations[$id]["id"] = $id;
      $this->stations[$id][$attr] = (string) $value;
      $id++;
    }
  }

}

// 緯度・経度をリンクしている別箇所から引っ張ってくる
function setGeo(){

  $geo =  "/ksj:Dataset/gml:Curve/gml:segments/gml:LineStringSegment/gml:posList";

  // 場所を追加
  $id = 1;
  foreach ($this->xml->xpath($geo) as $sta){
    $g = $sta;
    $g = (string) $g;
    $g = trim($g);
    $g = preg_replace("/\n\s+/", " ", $g);
    $g = split(" ", $g);
    $this->stations[$id]["geo"] = $g;
    $this->stations[$id]["center"] = $this->estimatesCenter($g);
    $id++;
  }

}

// 複数個の緯度・経度の存在を考慮して、その中心点となる緯度・経度を推定する
function estimatesCenter($geo){

  // 緯度・経度がセットになっていないものは却下
  if (count($geo)%2 != 0) return;

  // 奇数個の緯度・経度がある場合は真ん中のものを中心点として採用
  if ((count($geo)-2)%4 == 0){
    $lat = $geo[(count($geo)/2-1)];
    $lng = $geo[(count($geo)/2)];
    return array($lat, $lng);
  }

  // 偶数個の緯度・経度がある場合は真ん中２つの緯度・経度の中点を中心点として採用
  if ((count($geo)-2)%4 != 0){
    $lat = ($geo[(count($geo)/2-2)] + $geo[(count($geo)/2)]) /2;
    $lng = ($geo[(count($geo)/2-1)] + $geo[(count($geo)/2+1)]) / 2;
    return array($lat, $lng);
  }

}

// 漢字混じりの地名をひらがなに書き下す
function kanji2kana($str){  
  $mecab = new MeCab_Tagger();
  $yomi = "";
  
  for ($node=$mecab->parseToNode($str); $node; $node=$node->getNext()){
    if ($node->getStat() != 2 && $node->getStat() != 3){
      $feature = split(",", $node->getFeature());
      $katakana =  isset($feature[7]) ? $feature[7] : $node->surface;
      $hiragana = mb_convert_kana($katakana, "c");
      $yomi .= $hiragana;
    }
  }
  
  return $yomi;
}

// XMLを書き出す
function write(){
  $fp = fopen("./stations.serialized", "w");
  $file = serialize($this->stations);
  fwrite($fp, $file);
  fclose($fp);
}

function load(){
  $file = file_get_contents("stations.serialized");
  $stations = unserialize($file);
  echo "<pre>";
  print_r($stations);
  echo "</pre>";
}

function calc(){

  if (!isset($_GET["x"]) || !isset($_GET["y"])) exit("No position specified.");
  if (!preg_match("/^[0-9\.]+$/", $_GET["x"]))  exit("invalid X");
  if (!preg_match("/^[0-9\.]+$/", $_GET["y"]))  exit("invalid Y");

  $file = file_get_contents("stations.serialized");
  $stations = unserialize($file);
  $x = (float) $_GET["x"];
  $y = (float) $_GET["y"];
  $i = 0;

  // 線形探索による最近傍駅探索、要するに総当り・・
  foreach ($stations as $id => $station){
    $vx = $x - (float) $station["center"][0];
    $vy = $y - (float) $station["center"][1];
    $d[$i]["id"] = $id;
    $d[$i]["distance"] = sqrt(pow($vx,2) + pow($vy,2)); // 三平方での距離算出
    $t[$i] = $d[$i]["distance"];
    $i++;
  }

  array_multisort($t, SORT_NUMERIC, $d);
  //print_r($d);
  
  // 再近傍駅のID
  $nearest_id = $d[0]["id"];
  $nearest = array(
    "name" => $stations[$nearest_id]["name"],
    "company" => $stations[$nearest_id]["company"],
    "line" => $stations[$nearest_id]["line"],
    "yomi" => $stations[$nearest_id]["Yomi"]
  );

  $nearest["name"] = str_replace("･", "・", $nearest["name"]);
  $nearest["yomi"] = str_replace("曳舟", "ひきふね", $nearest["yomi"]);
  if ($nearest["name"] == "弘明寺" && $nearest["company"] == "京浜急行電鉄") $nearest["name"] = "弘明寺（京急）";
  if ($nearest["name"] == "弘明寺" && $nearest["company"] == "横浜市") $nearest["name"] = "弘明寺（横浜市営）";
  if ($nearest["name"] == "浅草" && $nearest["company"] == "首都圏新都市鉄道") $nearest["name"] = "浅草（TX）";

  echo $nearest["name"];

}

}

?>