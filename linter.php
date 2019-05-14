<?php
  header("Content-type: text/plain");


  if(isset($_GET["content"])) {
    if($_GET["content"] === "randomtips") {
      get_random_tips();
    }
  }

  if(isset($_POST["code"])) {
    validate($_POST["code"]);
  }

  function get_random_tips() {
    $file = "resources/cssguide.txt";
    if(file_exists($file)){
      $content = file_get_contents($file) or die("ERROR: Cannot open the file.");
      $tips = explode("\n", $content);
      // Handles the extra empty element in the array added during parsing
      array_pop($tips);
      $index = rand(0, count($tips) - 1);
      echo $tips[$index];
    } else{
      echo "ERROR: File does not exist.";
    }
  }

  function validate($code) {
    $lines = explode("\n", $code);
    echo checkDuplicates($lines);
  }

  function checkDuplicates($lines) {
    // $ignore = "/(\/\*|\*\/|})/";
    $rule = "/(.)+: (.)+;/";
    $result = "";
    for($i = 0; $i < count($lines); $i++) {
      for($j = $i + 1; $j < count($lines); $j++) {
        if(preg_match_all($rule, $lines[$i]) && $lines[$i] === $lines[$j]) {
          $match1 = $i + 1;
          $match2 = $j + 1;
          $result .= "line {$match1} and line {$match2} are duplicates\n";
        }
      }
    }
    if(!$result) {
      return "No duplicated rules!";
    }
    return $result;
  }
?>
