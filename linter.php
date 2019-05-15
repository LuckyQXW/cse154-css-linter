<?php
  header("Content-type: application/json");

  // Defines the regex patterns used for validation
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

  /**
   * Gets a random tip from the CSS Code Quality Guide
   * Content copied from
   * https://courses.cs.washington.edu/courses/cse154/codequalityguide/_site/css/
   */
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

  /**
   * Validates the given CSS code
   * @param  [string] $code - the CSS code input from the user
   */
  function validate($code) {
    $lines = explode("\n", $code);
    $result = array();
    $result["duplicates"] = check_duplicates($lines);
    $result["spacing-errors"] = check_spacing_errors($lines);
    echo json_encode($result);
  }

  /**
   * Checks spacing error and missing semicolons from the given CSS code
   * @param  [string[]] $lines - an array of CSS code split by new line
   * @return [array] - an associative array including information on the line
   *                   number, the type of error, a detailed message describing
   *                   the error, and the content of the line where the error is
   *                   detected
   */
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

  /**
   * Constructs a selector spacing error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the selector spacing error
   */
  function selector_spacing_error($line, $index) {
    return spacing_error($index,
                         "selector spacing error",
                         "line {$index}: wrong spacing between selector and open bracket",
                         $line);
  }

  /**
   * Constructs a colon spacing error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the colon spacing error
   */
  function colon_spacing_error($line, $index) {
    return spacing_error($index,
                         "rule colon spacing error",
                         "line {$index}: wrong spacing around colons in rule",
                         $line);
  }

  /**
   * Constructs a colon spacing error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the colon spacing error
   */
  function missing_semicolon_error($line, $index) {
    return spacing_error($index,
                         "missing semicolon error",
                         "line {$index}: missing semicolon in rule",
                         $line);
  }

  /**
   * Constructs a missing new line error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the missing new line error
   */
  function missing_new_line_error($line, $index) {
    return spacing_error($index,
                         "missing newline error",
                         "line {$index}: missing a new line between rule sets",
                         $line);
  }

  /**
   * Constructs an extra new line error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the extra new line error
   */
  function extra_new_line_error($line, $index) {
    return spacing_error($index,
                         "extra newline error",
                         "line {$index}: extra new line inside a rule set",
                         $line);
  }

  /**
   * Constructs a generic spacing error message
   * @param  [int] $index - the line number
   * @param  [string] $type - the type of error
   * @param  [string] $message - the detailed error message
   * @param  [string] $content - the line of code where the error is detected
   * @return [array] - an associative array describing the generic spacing error
   */
  function spacing_error($index, $type, $message, $content) {
    $error_msg = array();
    $error_msg["index"] = $index;
    $error_msg["type"] = $type;
    $error_msg["message"] = $message;
    $error_msg["content"] = $content;
    return $error_msg;
  }

  /**
   * Checks duplicated rules in the given CSS code
   * @param  [string[]] $lines - an array of CSS code split by new line
   * @return [array] - an associative array including information on the line
   *                   numbers of the two duplicates, a detailed message describing
   *                   the duplicated rules, and the duplicated content
   */
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
