# CSSLint API Documentation
The CSSLint API provides information on CSS Code Quality according to the CSE 154
standard and allows user to check the style of their code.

## Get the full CSS Code Quality Guide
**Request Format:** linter.php?tips=all

**Request Type:** GET

**Returned Data Format**: Plain Text

**Description:** Returns all the tips from the CSS Code Quality Guide

**Example Request:** linter.php?tips=all

**Example Response:**
```
1.1 Always order CSS in a logical way that makes it easy to read. The recommended strategy would be to put “generic” selectors at the top, such as the body, followed by context selectors, classes then IDs.
1.2 Always place @import statements before any rule sets.
...
```

**Error Handling:**
If missing the `all`, it will 400 error with a message `Please request either a random tip or all the tips!`

## Get a random tip from the CSS Code Quality Guide
**Request Format:** linter.php?tips=random

**Request Type**: GET

**Returned Data Format**: JSON

**Description:** Returns one random tip from the CSS Code Quality Guide

**Example Request:** linter.php?tips=random

**Example Response:**
```json
{
  "tips": "1.2 Always place @import statements before any rule sets."
}
```

**Error Handling:**
If missing the `random`, it will 400 error with a message `Please request either a random tip or all the tips!`

## Validate CSS Code
**Request Format:** linter.php endpoint with POST parameters of `code`

**Request Type**: POST

**Returned Data Format**: JSON

**Description:** Returns the validation result. The linter will check for the two main types of errors:
* Duplicated rules: the `first-index` and `second-index` contains the indices of the two identical lines in CSS (ignore white space), `message` gives a detail message in the format of "line {first-index} and line {second-index} are duplicates", and `content` shows the content of the duplicated lines.
* Format errors: `index` contains the index of the line where the error is detected, and `content` shows the content of line. Here is a list of all possible errors and their corresponding `message`:
+ Selector spacing error, when there is extra leading space in front of the selector or there is not strictly one space between the selector and the open bracket. `message` format is "line {$index}: wrong spacing around selector".
+ Extra newline error, when there is an extra newline inside the rule set. `message` format is "line {$index}: extra new line inside a rule set".
+ Rule spacing error, when a CSS rule has the wrong indentation (assume all rules have 2 leading space) or has wrong spacing around the colon in the rule. `message` format is "line {$index}: wrong leading space or spacing around colons for rule".
+ Semicolon error, when a CSS rule does not end with a semicolon. `message` format is "line {$index}: rule does not end with semicolon".
+ Missing newline error, when there is a newline missing between CSS rule sets. `message` format is "line {$index}: missing a new line between rule sets".
+ Extra newline between sets error, when there is more than one new line between CSS rule sets. `message` format is "line {$index}: extra new line between rule sets".
+ Rule set close error, when there is extra leading space in front of the rule closing bracket or there is extra content after the closing bracket. `message` format is "line {$index}: leading space before end bracket or extra content after end bracket".

**Example Request:** linter.php endpoint with POST parameters of
`code=
h2  {
  margin-bottom: 0;
  margin-bottom: 0;
}`

**Example Response:**
```json
{
  "duplicates": [
    {
      "first-index": 2,
      "second-index": 3,
      "message": "line 2 and line 3 are duplicates",
      "content": "  margin-bottom: 0;"
    }
  ],
  "format-errors":
  [
    {
      "index": 0,
      "message": "line 0: wrong spacing around selector",
      "content": "h2  {"
    }
  ]
}
```

**Error Handling:**
If missing the `code`, it will 400 error with a message `Please send nonempty code for validation!`
