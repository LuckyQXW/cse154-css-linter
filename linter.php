<?php
  // Defines the regex patterns used for validation
  const SELECTOR = "/(\S)+( )*{/";
  const RULE_SET_CLOSE = "/( )*}/";
  const RULE = "/( )*(\S)+:( )*(\S)*/";
  const SELECTOR_STRICT = "/^\S+((\S {1}\S)*(\S))* {/";
  const RULE_SPACING = "/^ {2}(\S)+: (\S)(.)+/";
  const END_WITH_SEMICOLON = "/(?<!;);( )*(?!.)/";
  const RULE_SET_CLOSE_STRICT = "/^}( )*$/";

  if(isset($_GET["tips"]) && $_GET["tips"] === "random") {
    header("Content-type: application/json");
    get_random_tip();
  } else if(isset($_GET["tips"]) && $_GET["tips"] === "all") {
    if($_GET["mode"] === "text") {
      header("Content-type: application/json");
      get_all_tips(true);
    } else {
      header("Content-type: text/plain");
      get_all_tips();
    }
  } else if(isset($_POST["code"])) {
    header("Content-type: application/json");
    validate($_POST["code"]);
  } else {
    header("HTTP/1.1 400 Invalid Request");
    echo "Missing required tips parameter!";
  }

  /**
   * Gets all the tips from the CSS Code Quality Guide
   * Content copied from
   * https://courses.cs.washington.edu/courses/cse154/codequalityguide/_site/css/
   * @param  [boolean] $text - indicates whether the tips should be returned in
   *                           plain text
   */
  function get_all_tips($text = false) {
    $file = "resources/cssguide.txt";
    $all_tips = array();
    if(file_exists($file)){
      $content = file_get_contents($file) or die("ERROR: Cannot open the file.");
      if($text) {
        echo $content;
      } else {
        $tips = explode("\n", $content);
        // Handles the extra empty element in the array added during parsing
        array_pop($tips);
        for($i = 0; $i < count($tips); $i++) {
          $result = array();
          $result["tip"] = $tips[$i];
          array_push($all_tips, $result);
        }
        echo json_encode($all_tips);
      }
    } else{
      echo "ERROR: File does not exist.";
    }
  }

  /**
   * Gets a random tip from the CSS Code Quality Guide
   * Content copied from
   * https://courses.cs.washington.edu/courses/cse154/codequalityguide/_site/css/
   */
  function get_random_tip() {
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
          array_push($spacing_error, extra_newline_error($lines[$i], $i));
        }
      } else if($start_of_css && preg_match_all(RULE, $lines[$i])) {
        if(!preg_match_all(RULE_SPACING, $lines[$i])) {
          array_push($spacing_error, colon_spacing_error($lines[$i], $i));
        }
        if(!(preg_match_all(END_WITH_SEMICOLON, $lines[$i]))) {
          array_push($spacing_error, semicolon_error($lines[$i], $i));
        }
        if($lines[$i + 1] === "") {
          array_push($spacing_error, extra_newline_error($lines[$i], $i));
        }
      } else if(preg_match_all(RULE_SET_CLOSE, $lines[$i])) {
        if(!preg_match(RULE_SET_CLOSE_STRICT, $lines[$i])) {
          array_push($spacing_error, rule_set_close_error($lines[$i], $i));
        }
        if($i < count($lines) - 1 && $lines[$i + 1] != "") {
          array_push($spacing_error, missing_newline_error($lines[$i], $i));
        } else if($i < count($lines) - 2 && $lines[$i + 2] == "") {
          array_push($spacing_error, extra_newline_between_sets_error($lines[$i], $i));
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
                         "spacing error",
                         "line {$index}: wrong spacing around selector",
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
                         "spacing error",
                         "line {$index}: wrong leading space or spacing around colons",
                         $line);
  }

  /**
   * Constructs a colon spacing error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the colon spacing error
   */
  function semicolon_error($line, $index) {
    return spacing_error($index,
                         "semicolon error",
                         "line {$index}: rule does not end with semicolon",
                         $line);
  }

  /**
   * Constructs a missing new line error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the missing new line error
   */
  function missing_newline_error($line, $index) {
    return spacing_error($index,
                         "spacing error",
                         "line {$index}: missing a new line between rule sets",
                         $line);
  }

  /**
   * Constructs an extra new line error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the extra new line error
   */
  function extra_newline_error($line, $index) {
    return spacing_error($index,
                         "spacing error",
                         "line {$index}: extra new line inside a rule set",
                         $line);
  }

  /**
   * Constructs an extra new line between rule sets error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the extra new line error
   */
  function extra_newline_between_sets_error($line, $index) {
    return spacing_error($index,
                         "spacing error",
                         "line {$index}: extra new line between rule sets",
                         $line);
  }

  /**
   * Constructs a rule set close error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing rule set close error
   */
  function rule_set_close_error($line, $index) {
    return spacing_error($index,
                         "spacing error",
                         "line {$index}: leading space before end bracket or"
                         ." extra content after end bracket",
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
