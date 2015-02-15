<?php

set_time_limit(0);

class TrainDataIO{

// データ設定
function buildData(){
  $this->loadXML();
  $this->setData();
  $this->postProcess();
  $this->write();
}


// XMLデータをSimpleXMLで読み込み
function loadXML(){
  $filename = "S12-12.xml";
  $file = file_get_contents($filename);
  // reduce namespace
  $file = str_replace("ksj:", "", $file);
  $file = str_replace("gml:", "", $file);
  $this->xml = simplexml_load_string($file);
}

// データ設定
function setData(){
  foreach ($this->xml->TheNumberofTheStationPassengersGettingonandoff as $station){
    $name = (string) $station->snm;
    $id = str_replace("sp", "", (string) $station["id"]);
    $this->stations[$name]["name"]["ja"] = $name;
    $this->stations[$name]["name"]["ja_hrgn"] = $this->kanji2kana($name);
    $this->stations[$name]["name"]["en"] = ucfirst($this->kana2romaji($this->stations[$name]["name"]["ja_hrgn"]));
    $this->stations[$name]["geo"] = $this->setGeo($id);
    $this->stations[$name]["company"] = (string) $station->aco;
    $this->stations[$name]["line"] = (string) $station->rnm;
  }
}

// 緯度・経度をリンクしている別箇所から引っ張ってくる
function setGeo($id){
  $curve = $this->xml->xpath('/Dataset/Curve[@id="cv'.$id.'"]');
  $geos  = $curve[0]->segments->LineStringSegment->posList;
  $geos  = (string) $geos;
  $geos  = trim($geos);
  $geos  = preg_replace("/\n\s+/", " ", $geos);
  $geos  = split(" ", $geos);
  $geo   = $this->estimatesCenter($geos);
  $out   = array("lat" => $geo[0], "lng" => $geo[1]);
  return $out;
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

// ひらがなをローマ字に変換する
function kana2romaji($str){
  $str = mb_convert_kana($str, 'cHV', 'utf-8');

  $kana = array(
      'きゃ', 'きぃ', 'きゅ', 'きぇ', 'きょ',
      'ぎゃ', 'ぎぃ', 'ぎゅ', 'ぎぇ', 'ぎょ',
      'くぁ', 'くぃ', 'くぅ', 'くぇ', 'くぉ',
      'ぐぁ', 'ぐぃ', 'ぐぅ', 'ぐぇ', 'ぐぉ',
      'しゃ', 'しぃ', 'しゅ', 'しぇ', 'しょ',
      'じゃ', 'じぃ', 'じゅ', 'じぇ', 'じょ',
      'ちゃ', 'ちぃ', 'ちゅ', 'ちぇ', 'ちょ',
      'ぢゃ', 'ぢぃ', 'ぢゅ', 'ぢぇ', 'ぢょ',
      'つぁ', 'つぃ', 'つぇ', 'つぉ',
      'てゃ', 'てぃ', 'てゅ', 'てぇ', 'てょ',
      'でゃ', 'でぃ', 'でぅ', 'でぇ', 'でょ',
      'とぁ', 'とぃ', 'とぅ', 'とぇ', 'とぉ',
      'にゃ', 'にぃ', 'にゅ', 'にぇ', 'にょ',
      'ヴぁ', 'ヴぃ', 'ヴぇ', 'ヴぉ',
      'ひゃ', 'ひぃ', 'ひゅ', 'ひぇ', 'ひょ',
      'ふぁ', 'ふぃ', 'ふぇ', 'ふぉ',
      'ふゃ', 'ふゅ', 'ふょ',
      'びゃ', 'びぃ', 'びゅ', 'びぇ', 'びょ',
      'ヴゃ', 'ヴぃ', 'ヴゅ', 'ヴぇ', 'ヴょ',  
      'ぴゃ', 'ぴぃ', 'ぴゅ', 'ぴぇ', 'ぴょ',
      'みゃ', 'みぃ', 'みゅ', 'みぇ', 'みょ',  
      'りゃ', 'りぃ', 'りゅ', 'りぇ', 'りょ',
      'うぃ', 'うぇ', 'いぇ'
  );
   
  $romaji  = array(
      'kya', 'kyi', 'kyu', 'kye', 'kyo',
      'gya', 'gyi', 'gyu', 'gye', 'gyo',
      'qwa', 'qwi', 'qwu', 'qwe', 'qwo',
      'gwa', 'gwi', 'gwu', 'gwe', 'gwo',
      'sya', 'syi', 'syu', 'sye', 'syo',
      'ja', 'jyi', 'ju', 'je', 'jo',
      'cha', 'cyi', 'chu', 'che', 'cho',
      'dya', 'dyi', 'dyu', 'dye', 'dyo',
      'tsa', 'tsi', 'tse', 'tso',
      'tha', 'ti', 'thu', 'the', 'tho',
      'dha', 'di', 'dhu', 'dhe', 'dho',
      'twa', 'twi', 'twu', 'twe', 'two',
      'nya', 'nyi', 'nyu', 'nye', 'nyo',
      'va', 'vi', 've', 'vo',
      'hya', 'hyi', 'hyu', 'hye', 'hyo',
      'fa', 'fi', 'fe', 'fo',
      'fya', 'fyu', 'fyo',
      'bya', 'byi', 'byu', 'bye', 'byo',
      'vya', 'vyi', 'vyu', 'vye', 'vyo',
      'pya', 'pyi', 'pyu', 'pye', 'pyo',
      'mya', 'myi', 'myu', 'mye', 'myo',
      'rya', 'ryi', 'ryu', 'rye', 'ryo',
      'wi', 'we', 'ye'
  );
   
  $str = $this->kana_replace($str, $kana, $romaji);

  $kana = array(
      'あ', 'い', 'う', 'え', 'お',
      'か', 'き', 'く', 'け', 'こ',
      'さ', 'し', 'す', 'せ', 'そ',
      'た', 'ち', 'つ', 'て', 'と',
      'な', 'に', 'ぬ', 'ね', 'の',
      'は', 'ひ', 'ふ', 'へ', 'ほ',
      'ま', 'み', 'む', 'め', 'も',
      'や', 'ゆ', 'よ',
      'ら', 'り', 'る', 'れ', 'ろ',
      'わ', 'ゐ', 'ゑ', 'を', 'ん',
      'が', 'ぎ', 'ぐ', 'げ', 'ご',
      'ざ', 'じ', 'ず', 'ぜ', 'ぞ',
      'だ', 'ぢ', 'づ', 'で', 'ど',
      'ば', 'び', 'ぶ', 'べ', 'ぼ',
      'ぱ', 'ぴ', 'ぷ', 'ぺ', 'ぽ'
  );
   
  $romaji = array(
      'a', 'i', 'u', 'e', 'o',
      'ka', 'ki', 'ku', 'ke', 'ko',
      'sa', 'shi', 'su', 'se', 'so',
      'ta', 'chi', 'tsu', 'te', 'to',
      'na', 'ni', 'nu', 'ne', 'no',
      'ha', 'hi', 'fu', 'he', 'ho',
      'ma', 'mi', 'mu', 'me', 'mo',
      'ya', 'yu', 'yo',
      'ra', 'ri', 'ru', 're', 'ro',
      'wa', 'wyi', 'wye', 'wo', 'n',
      'ga', 'gi', 'gu', 'ge', 'go',
      'za', 'ji', 'zu', 'ze', 'zo',
      'da', 'ji', 'du', 'de', 'do',
      'ba', 'bi', 'bu', 'be', 'bo',
      'pa', 'pi', 'pu', 'pe', 'po'
  );
   
  $str = $this->kana_replace($str, $kana, $romaji);
   
  $str = preg_replace('/(っ$|っ[^a-z])/u', "xtu", $str);
  $res = preg_match_all('/(っ)(.)/u', $str, $matches);
  if(!empty($res)){
      for($i=0;isset($matches[0][$i]);$i++){
          if($matches[0][$i] == 'っc') $matches[2][$i] = 't';
          $str = preg_replace('/' . $matches[1][$i] . '/u', $matches[2][$i], $str, 1);
      }
  }
   
  $kana = array(
      'ぁ', 'ぃ', 'ぅ', 'ぇ', 'ぉ',
      'ヵ', 'ヶ', 'っ', 'ゃ', 'ゅ', 'ょ', 'ゎ', '、', '。', '　'
  );
   
  $romaji = array(
      'a', 'i', 'u', 'e', 'o',
      'ka', 'ke', 'xtu', 'xya', 'xyu', 'xyo', 'xwa', ', ', '.', ' '
  );
  $str = $this->kana_replace($str, $kana, $romaji);
   
  $str = preg_replace('/^ー|[^a-z]ー/u', '', $str);
  $res = preg_match_all('/(.)(ー)/u', $str, $matches);

  if($res){
      for($i=0;isset($matches[0][$i]);$i++){
          if( $matches[1][$i] == "a" ){ $replace = 'â'; }
          else if( $matches[1][$i] == "i" ){ $replace = 'î'; }
          else if( $matches[1][$i] == "u" ){ $replace = 'û'; }
          else if( $matches[1][$i] == "e" ){ $replace = 'ê'; }
          else if( $matches[1][$i] == "o" ){ $replace = 'ô'; }
          else { $replace = ""; }
           
          $str = preg_replace('/' . $matches[0][$i] . '/u', $replace, $str, 1);
      }
  }
   
  return $str;
}

function kana_replace($str, $kana, $romaji){
  $patterns = array();
  foreach($kana as $value){
      $patterns[] = '/' . $value . '/';
  }
   
  $str = preg_replace($patterns, $romaji, $str);
  return $str;
}

function postProcess(){
  foreach ($this->stations as $name => $station){
    if ($name == "弘明寺" && $station["company"] == "京浜急行電鉄"){
      $this->stations[$name]["name"]["ja"] = "弘明寺（京急）";
    }
    if ($name == "弘明寺" && $station["company"] == "横浜市"){
      $this->stations[$name]["name"]["ja"] = "弘明寺（横浜市営）";
    }
    if ($name == "浅草" && $station["company"] == "首都圏新都市鉄道"){
      $this->stations[$name]["name"]["ja"] = "浅草（TX）";
    }
  }
}

// JSONを書き出す
function write(){
  $json = json_encode($this->stations);
  $fp = fopen("stations.json", "w");
  if ($fp){
    fwrite($fp, $json);
    fclose($fp);
  }
}

function loadAll(){
  $stations = file_get_contents("stations.json");
  header("content-type: application/json");
  print_r($stations);
}

function loadYomi(){
  $file = file_get_contents("stations.json");
  $stations = json_decode($file);
  $out = "";

  foreach ($stations as $name => $station){
    $out .= '"'.$name.'": {'.
            '"hrgn": "'. $station->name->ja_hrgn .'",'.
            '"en": "'. $station->name->en .'"'.
            '},';
  }

  $out = '{'.substr($out, 0, -1).'}';
  header("content-type: application/json; encoding: utf-8");
  echo $out;
}

function near(){

  if (!isset($_GET["x"]) || !isset($_GET["y"])) exit("No position specified.");
  if (!preg_match("/^[0-9\.]+$/", $_GET["x"]))  exit("invalid X");
  if (!preg_match("/^[0-9\.]+$/", $_GET["y"]))  exit("invalid Y");

  $file = file_get_contents("stations.json");
  $stations = json_decode($file);
  $x = (float) $_GET["x"];
  $y = (float) $_GET["y"];
  $i = 0;

  // 線形探索による最近傍駅探索、要するに総当り・・
  foreach ($stations as $id => $station){
    $vx = $x - (float) $station->geo->lat;
    $vy = $y - (float) $station->geo->lng;
    $d[$i]["id"] = $id;
    $d[$i]["distance"] = sqrt(pow($vx,2) + pow($vy,2)); // 三平方での距離算出
    $t[$i] = $d[$i]["distance"];
    $i++;
  }

  array_multisort($t, SORT_NUMERIC, $d);
  
  // 再近傍駅のID
  $name = $d[0]["id"];
  echo $stations->{$name}->name->ja;
}

}

?>