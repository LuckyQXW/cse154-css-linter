/**
 * Name: Wen Qiu
 * Date: May 17, 2019
 * Section: CSE 154 AJ
 * This is the JavaScript for the CSS Lint handling the verification of the CSS
 * style and other user interactions.
 */
(function() {
  "use strict";
  window.addEventListener("load", init);
  const BASE_URL = "linter.php";

  /**
   * Initializes the CSS lint and attaches action to the interface
   */
  function init() {
    id("submit").addEventListener("click", submit);
    id("get-tip").addEventListener("click", getTip);
    id("hide-warning").addEventListener("click", hideWarning);
    id("get-full-guide").addEventListener("click", getFullGuide);
  }

  /**
   * Validates the CSS code by sending the user CSS input to the server
   * @param {Event} e - the current click event
   */
  function submit(e) {
    e.preventDefault();
    if (id("editor").value === "") {
      id("editor").placeholder = "Please copy and paste your FULL CSS code in here first!";
    } else {
      let data = new FormData();
      data.append("code", id("editor").value);
      fetch(BASE_URL, {method: "POST", body: data})
        .then(checkStatus)
        .then(JSON.parse)
        .then(handleOutput)
        .catch(displayError);
    }
  }

  /**
   * Handles the response from the server after validating the code
   * @param  {Object} json - json response from the server
   */
  function handleOutput(json) {
    id("output-area").innerHTML = "";
    if (json["duplicates"].length || json["format-errors"].length) {
      for (let i = 0; i < json["duplicates"].length; i++) {
        let message = json["duplicates"][i]["message"]
        + " (" + json["duplicates"][i]["content"] + ")";
        appendOutput(message);
      }
      for (let i = 0; i < json["format-errors"].length; i++) {
        let message = json["format-errors"][i]["message"]
        + " (" + json["format-errors"][i]["content"] + ")";
        appendOutput(message);
      }
    } else {
      passValidation();
    }
  }

  /**
   * Appends an output message to the output area
   * @param  {String} message - the message to be displayed
   */
  function appendOutput(message) {
    let output = document.createElement("div");
    output.classList.add("msg");
    let outputText = document.createElement("pre");
    outputText.textContent = message;
    output.appendChild(outputText);
    id("output-area").appendChild(output);
  }

  /**
   * Shows the CSS code has passed the validation in the output area
   */
  function passValidation() {
    let output = document.createElement("div");
    output.classList.add("pass");
    let outputText = document.createElement("pre");
    outputText.textContent = "Pass validation!";
    output.appendChild(outputText);
    id("output-area").appendChild(output);
  }

  /**
   * Gets a random CSS tip from the server
   */
  function getTip() {
    fetch(BASE_URL + "?tips=random")
      .then(checkStatus)
      .then(JSON.parse)
      .then(populateTip)
      .catch(displayError);
  }

  /**
   * Populates the css tips area with a random tip from the CSS Code Quality Guide
   * @param  {Object} json - the json object containing the random CSS tip
   */
  function populateTip(json) {
    let text = json.tip;
    qs("#random-tips-area p").textContent = text;
  }

  /**
   * Hides the warning
   */
  function hideWarning() {
    id("disclaimer").classList.add("hidden");
  }

  /**
   * Gets the full CSS Code Quality Guide and displays it as an output
   */
  function getFullGuide() {
    fetch(BASE_URL + "?tips=all&mode=text")
      .then(checkStatus)
      .then(displayFullGuide)
      .catch(displayError);
  }

  /**
   * Displays the full CSS Code Quality Guide in the output area
   * @param  {String} text - the CSS Code Quality Guide in plain text
   */
  function displayFullGuide(text) {
    id("output-area").innerHTML = "";
    let lines = text.split("\n");
    let guide = document.createElement("div");
    for (let i = 0; i < lines.length; i++) {
      let guideText = document.createElement("p");
      guideText.textContent = lines[i];
      guide.appendChild(guideText);
    }
    id("output-area").appendChild(guide);
  }

  /**
   * Displays an error message when the server is down or internet connection is broken
   */
  function displayError() {
    id("output-area").innerHTML = "";
    appendOutput("Oops, something is wrong with the server or your internet!");
  }

  /**
   * Helper method for getting element by id
   * @param {String} elementID - the id with which the target objects are attached to
   * @return {Object} the DOM element object with the specified id
   */
  function id(elementID) {
    return document.getElementById(elementID);
  }

  /**
   * Helper method for getting an element by selector
   * @param {String} selector - the selector used to select the target elements
   * @return {Object} The first element in the DOM selected with the given selector
   */
  function qs(selector) {
    return document.querySelector(selector);
  }

  /**
    * Helper function to return the response's result text if successful, otherwise
    * returns the rejected Promise result with an error status and corresponding text
    * Used the template from spec
    * @param {object} response - response to check for success/error
    * @returns {object} - valid result text if response was successful, otherwise rejected
    *                     Promise result
    */
   function checkStatus(response) {
     if (response.status >= 200 && response.status < 300 || response.status == 0) {
       return response.text();
     } else {
       return Promise.reject(new Error(response.status + ": " + response.statusText));
     }
   }
})();
