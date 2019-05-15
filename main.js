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

  function init() {
    id("submit").addEventListener("click", submit);
    id("editor").addEventListener("change", refreshButton);
    id("get-tip").addEventListener("click", getTip);
  }

  /**
   * Validates the CSS code by sending the user CSS input to the server
   * @param {Event} e - the current click event
   */
  function submit(e) {
    e.preventDefault();
    let data = new FormData();
    data.append("code", id("editor").value);
    fetch(BASE_URL, {method: "POST", body: data})
      .then(checkStatus)
      .then(JSON.parse)
      .then(handleOutput)
      .catch(console.error);
  }

  /**
   * Handles the response from the server after validating the code
   * @param  {Object} json - json response from the server
   */
  function handleOutput(json) {
    id("output-area").innerHTML = "";
    if(json["duplicates"].length || json["spacing-errors"].length) {
      for(let i = 0; i < json["duplicates"].length; i++) {
        let message = json["duplicates"][i]["message"]
        + " (" + json["duplicates"][i]["content"] + ")";
        appendOutput(message);
      }
      for(let i = 0; i < json["spacing-errors"].length; i++) {
        let message = json["spacing-errors"][i]["message"]
        + " (" + json["spacing-errors"][i]["content"] + ")";
        appendOutput(message);
      }
    } else {
      passValidation();
    }
  }

  function appendOutput(message) {
    let output = document.createElement("div");
    output.classList.add("msg");
    let outputText = document.createElement("p");
    outputText.textContent = message;
    output.appendChild(outputText);
    id("output-area").appendChild(output);
  }

  function passValidation() {
    let output = document.createElement("div");
    output.classList.add("pass");
    let outputText = document.createElement("p");
    outputText.textContent = "Pass validation!";
    output.appendChild(outputText);
    id("output-area").appendChild(output);
  }
  /**
   * Gets a random CSS tip from the server
   */
  function getTip() {
    fetch(BASE_URL + "?content=randomtips")
      .then(checkStatus)
      .then(JSON.parse)
      .then(populateTip)
      .catch(console.error);
  }

  /**
   * Populates the css tips area with a random tip from the CSS Code Quality Guide
   * @param  {Object} json - the json object containing the random CSS tip
   */
  function populateTip(json) {
    let text = json.tip;
    let tip = document.createElement("p");
    qs("#random-tips-area p").textContent = text;
  }

  /**
   * Enables the button if the editor is not empty, disables it otherwise
   */
  function refreshButton() {
    id("submit").disabled = !this.value;
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
