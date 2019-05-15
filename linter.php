<?php
  header("Content-type: application/json");

  const SELECTOR = "/( )*(.)+( )*{/";
  const RULE_SET_CLOSE = "/( )*}/";
  const RULE = "/( )*(.)+:( )*(.)*/";
  const SELECTOR_STRICT = "/([a-zA-Z])+ {/";
  const COLON_SPACE_AFTER = "/: (?! )/";
  const NO_COLON_SPACE_BEFORE = "/([a-zA-Z]):/";

  if(isset($_GET["content"])) {
    if($_GET["content"] === "randomtips") {
      get_random_tips();
    }
  } else if(isset($_POST["code"])) {
    validate($_POST["code"]);
  } else {
    header("HTTP/1.1 400 Invalid Request");
    echo "Missing required name parameter!";
  }

  function get_random_tips() {
    $file = "resources/cssguide.txt";
    if(file_exists($file)){
      $content = file_get_contents($file) or die("ERROR: Cannot open the file.");
      $tips = explode("\n", $content);
      // Handles the extra empty element in the array added during parsing
      array_pop($tips);
      $index = rand(0, count($tips) - 1);
      $result = array();
      $result["tip"] = $tips[$index];
      echo json_encode($result);
    } else{
      echo "ERROR: File does not exist.";
    }
  }

  function validate($code) {
    $lines = explode("\n", $code);
    $result = array();
    $result["duplicates"] = check_duplicates($lines);
    $result["spacing-errors"] = check_spacing_errors($lines);
    echo json_encode($result);
  }

  function check_spacing_errors($lines) {
    $spacing_error = array();
    $start_of_css = false;
    for($i = 0; $i < count($lines); $i++) {
      if(preg_match_all(SELECTOR, $lines[$i])) {
        $start_of_css = true;
        if(!preg_match_all(SELECTOR_STRICT, $lines[$i])) {
          array_push($spacing_error, selector_spacing_error($lines[$i], $i));
        }
        if($lines[$i + 1] === "") {
          array_push($spacing_error, extra_new_line_error($lines[$i], $i));
        }
      } else if($start_of_css && preg_match_all(RULE, $lines[$i])) {
        if(!(preg_match_all(COLON_SPACE_AFTER, $lines[$i])
        && preg_match_all(NO_COLON_SPACE_BEFORE, $lines[$i]))) {
          array_push($spacing_error, colon_spacing_error($lines[$i], $i));
        }
        if(!strpos($lines[$i], ";")) {
          array_push($spacing_error, missing_semicolon_error($lines[$i], $i));
        }
        if($lines[$i + 1] === "") {
          array_push($spacing_error, extra_new_line_error($lines[$i], $i));
        }
      } else if(preg_match_all(RULE_SET_CLOSE, $lines[$i])) {
        if($i !== count($lines) - 1 && $lines[$i + 1] != "") {
          array_push($spacing_error, missing_new_line_error($lines[$i], $i));
        }
      }
    }
    return $spacing_error;
  }

  function selector_spacing_error($line, $index) {
    return spacing_error($index,
                             "selector spacing error",
                             "line {$index}: wrong spacing between selector and open bracket",
                             $line);
  }

  function colon_spacing_error($line, $index) {
    return spacing_error($index,
                         "rule colon spacing error",
                         "line {$index}: wrong spacing around colons in rule",
                         $line);
  }

  function missing_semicolon_error($line, $index) {
    return spacing_error($index,
                         "missing semicolon error",
                         "line {$index}: missing semicolon in rule",
                         $line);
  }

  function missing_new_line_error($line, $index) {
    return spacing_error($index,
                         "missing newline error",
                         "line {$index}: missing a new line between rule sets",
                         $line);
  }

  function extra_new_line_error($line, $index) {
    return spacing_error($index,
                         "extra newline error",
                         "line {$index}: extra new line inside a rule set",
                         $line);
  }

  function spacing_error($index, $type, $message, $content) {
    $error_msg = array();
    $error_msg["index"] = $index;
    $error_msg["type"] = $type;
    $error_msg["message"] = $message;
    $error_msg["content"] = $content;
    return $error_msg;
  }

  function check_duplicates($lines) {
    $duplicates = array();
    $rule = "/(.)+: (.)+;/";
    for($i = 0; $i < count($lines); $i++) {
      for($j = $i + 1; $j < count($lines); $j++) {
        if(preg_match_all($rule, $lines[$i]) && $lines[$i] === $lines[$j]) {
          $match_msg = array();
          $match1 = $i + 1;
          $match2 = $j + 1;
          $msg = "line {$match1} and line {$match2} are duplicates";
          $match_msg["first-index"] = $match1;
          $match_msg["second-index"] = $match2;
          $match_msg["message"] = $msg;
          $match_msg["content"] = $lines[$i];
          array_push($duplicates, $match_msg);
        }
      }
    }
    return $duplicates;
  }
?>
