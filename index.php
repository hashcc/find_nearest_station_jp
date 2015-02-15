<?php

require "model.php";

$generator = new TrainDataIO();

switch ($_GET["mode"]){
  case "load":  $generator->loadAll(); break;
  case "yomi":  $generator->loadYomi(); break;
  case "near":  $generator->near(); break;
  case "build": echo "残念、サーバ側ではこのコマンドは停止しているのです";
                $generator->buildData(); break;
}

?>