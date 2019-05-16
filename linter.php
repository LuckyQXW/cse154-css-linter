<?php
/*
  Name: Wen Qiu
  Date: May 17, 2019
  Section : CSE 154 AJ

  This file provides back-end support for the CSSLinter API.
  Based on the input parameters supplied using GET requests,
  the API outputs different details about puppies in different formats.

  Web Service details:
  =====================================================================
  Required GET parameter:
  - tips
  Optional GET parameter:
  - mode
  Required POST parameter:
  - code
  Output formats:
  - Plain text and JSON
  Output Details:
  - If the tips parameter is passed and set to "random", the API
    will return a random tip from the CSS Code Quality Guide as JSON.
  - If the tips parameter is passed and set to "all", the API
    will return all tips from the CSS Code Quality Guide as JSON.
  - If the tips parameter is passed and set to "all", and the "mode"
    parameter is passed and set to "text", the API will return all tips from
    the CSS Code Quality Guide as plain text.
  - If the code parameter is passed, the API will validate its value as a CSS
    file and return the validation result as JSON.
  - Else outputs 400 error message as plain text.
 */
 header("Content-type: application/json");

  // Defines the regex patterns used for validation
  const SELECTOR = "/(\S)+( )*{/";
  const RULE_SET_CLOSE = "/( )*}/";
  const RULE = "/(.)+:( )*(\S)*/";
  const SELECTOR_STRICT = "/^\S+((\S {1}\S)*(\S))* {/";
  const RULE_SPACING = "/^ {2}(\S)+: (\S)(.)+/";
  const END_WITH_SEMICOLON = "/(?<!;);( )*(?!.)/";
  const RULE_SET_CLOSE_STRICT = "/^}( )*$/";

  if(isset($_GET["tips"])) {
    if($_GET["tips"] === "random") {
      get_random_tip();
    } else if($_GET["tips"] === "all") {
      header("Content-type: text/plain");
      get_all_tips();
    } else {
      header("HTTP/1.1 400 Invalid Request");
      echo "Please request either a random tip or all the tips!";
    }
  } else if(isset($_POST["code"])) {
    if($_POST["code"] !== "") {
      validate($_POST["code"]);
    } else {
      header("HTTP/1.1 400 Invalid Request");
      echo "Please send nonempty code for validation!";
    }
  } else {
    header("HTTP/1.1 400 Invalid Request");
    echo "Missing required parameters! Refer to API for available requests.";
  }

  /**
   * Gets all the tips from the CSS Code Quality Guide
   * Content copied from
   * https://courses.cs.washington.edu/courses/cse154/codequalityguide/_site/css/
   * @param  [boolean] $text - indicates whether the tips should be returned in
   *                           plain text
   */
  function get_all_tips() {
    $file = "resources/cssguide.txt";
    $all_tips = array();
    if(file_exists($file)){
      $content = file_get_contents($file) or die("ERROR: Cannot open the file.");
      echo $content;
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
    $result["format-errors"] = check_format_errors($lines);
    echo json_encode($result);
  }

  /**
   * Checks format error and missing semicolons from the given CSS code
   * @param  [string[]] $lines - an array of CSS code split by new line
   * @return [array] - an associative array including information on the line
   *                   number, the type of error, a detailed message describing
   *                   the error, and the content of the line where the error is
   *                   detected
   */
  function check_format_errors($lines) {
    $format_error = array();
    $start_of_css = false;
    for($i = 0; $i < count($lines); $i++) {
      if(preg_match_all(SELECTOR, $lines[$i])) {
        $start_of_css = true;
        if(!preg_match_all(SELECTOR_STRICT, $lines[$i])) {
          array_push($format_error, selector_spacing_error($lines[$i], $i));
        }
        if($lines[$i + 1] === "") {
          array_push($format_error, extra_newline_error($lines[$i], $i));
        }
      } else if($start_of_css && preg_match_all(RULE, $lines[$i])) {
        if(!preg_match_all(RULE_SPACING, $lines[$i])) {
          array_push($format_error, rule_spacing_error($lines[$i], $i));
        }
        if(!(preg_match_all(END_WITH_SEMICOLON, $lines[$i]))) {
          array_push($format_error, semicolon_error($lines[$i], $i));
        }
        if($lines[$i + 1] === "") {
          array_push($format_error, extra_newline_error($lines[$i], $i));
        }
      } else if(preg_match_all(RULE_SET_CLOSE, $lines[$i])) {
        if(!preg_match(RULE_SET_CLOSE_STRICT, $lines[$i])) {
          array_push($format_error, rule_set_close_error($lines[$i], $i));
        }
        if($i < count($lines) - 1 && $lines[$i + 1] !== "") {
          array_push($format_error, missing_newline_error($lines[$i], $i));
        } else if($i < count($lines) - 2 && $lines[$i + 2] === "") {
          array_push($format_error, extra_newline_between_sets_error($lines[$i], $i));
        }
      }
    }
    return $format_error;
  }

  /**
   * Constructs a selector spacing error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the selector spacing error
   */
  function selector_spacing_error($line, $index) {
    return format_error($index,
                        "line {$index}: wrong spacing around selector",
                        $line);
  }

  /**
   * Constructs a colon spacing error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the colon spacing error
   */
  function rule_spacing_error($line, $index) {
    return format_error($index,
                        "line {$index}: wrong leading space or spacing around colons for rule",
                        $line);
  }

  /**
   * Constructs a colon spacing error message
   * @param  [string] $line - the line of code where the error is detected
   * @param  [int] $index - the line number
   * @return [array] - an associative array describing the colon spacing error
   */
  function semicolon_error($line, $index) {
    return format_error($index,
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
    return format_error($index,
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
    return format_error($index,
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
    return format_error($index,
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
    return format_error($index,
                        "line {$index}: leading space before end bracket or"
                        ." extra content after end bracket",
                        $line);
  }

  /**
   * Constructs a generic format error message
   * @param  [int] $index - the line number
   * @param  [string] $message - the detailed error message
   * @param  [string] $content - the line of code where the error is detected
   * @return [array] - an associative array describing the generic format error
   */
  function format_error($index, $message, $content) {
    $error_msg = array();
    $error_msg["index"] = $index;
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
        if(preg_match_all($rule, $lines[$i]) && trim($lines[$i]) === trim($lines[$j])) {
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
