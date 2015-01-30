<?php

require "model.php";

$generator = new TrainDataIO();

switch ($_GET["mode"]){
  case "load": $generator->load(); break;
  case "near": $generator->calc(); break;
  case "build": $generator->buildData(); break;
}

?>