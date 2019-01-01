
function httpAsync(theUrl, method, body, success, failure)
{
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function() {
        if (xmlHttp.readyState == 4) {
          if (xmlHttp.status == 200) {
            success(JSON.parse(xmlHttp.responseText));
          } else {
            failure(JSON.parse(xmlHttp.responseText));
          }
        }
    }
    xmlHttp.open(method, theUrl, true); // true for asynchronous
    xmlHttp.send(body);
}

function httpGetAsync(theUrl, success, failure)
{
  httpAsync(theUrl, "GET", null, success, failure);
}

function httpPostAsync(theUrl, body, success, failure)
{
  httpAsync(theUrl, "POST", body, success, failure);
}

function httpPatchAsync(theUrl, body, success, failure)
{
  httpAsync(theUrl, "PATCH", body, success, failure);
}

function httpDeleteAsync(theUrl, body, success, failure)
{
  httpAsync(theUrl, "DELETE", body, success, failure);
}

function disableForm(formEle) {
  var elements = formEle.elements;
  for (var i = 0, len = elements.length; i < len; ++i) {
    elements[i].readOnly = true;
  }
}

function enableForm(formEle) {
  var elements = formEle.elements;
  for (var i = 0, len = elements.length; i < len; ++i) {
    elements[i].readOnly = false;
  }
}

function submitForm(theForm, theUrl, success) {

  var formEle = null;
  if (typeof theForm === "string") {
   formEle = document.querySelector(theForm);
  }
  if (formEle === null) {
    console.log("Invalid form elemnet")
    return false;
  }

  disableForm(formEle);

  var resultEle = formEle.querySelector(".js-form-result");
  if (resultEle !== null) {
    resultEle.innerHTML = "";
  }
  var messages = formEle.querySelectorAll(".input-message");
  Array.prototype.forEach.call(messages, function(messageEle) {
    messageEle.parentNode.removeChild(messageEle);
  });

  var data = {};

  function setDataItem(element) {
    if (element.hasAttribute("data-collection")) {
      var collection = element.getAttribute("data-collection");
      if (typeof data[collection] === "undefined") {
        data[collection] = {};
      }
      data[collection][element.name] = element.value;
    } else {
      data[element.name] = element.value;
    }
  }

  Array.prototype.forEach.call(formEle.querySelectorAll("input"), function(ele) {
      if (ele.type === "text" ||
          ele.type === "password" ||
          ele.type === "email" ||
          ele.type === "hidden") {
        setDataItem(ele);
      }
  });

  Array.prototype.forEach.call(formEle.querySelectorAll("select"), function(ele) {
    setDataItem(ele);
  });

  function displayFieldError(error) {
    var displayed = false;
    if (typeof error.field !== "undefined") {
      var fields = formEle.querySelectorAll("[name=" + error.field + "]");
      if (fields.length > 0) {
        fields.forEach(function(field) {
          var errorText = document.createElement("div");
          errorText.textContent = error.text;
          errorText.className = "input-message error";
          field.parentNode.insertBefore(errorText, field.nextSibling);
          displayed = true;
        });
      }
    }
    return displayed;
  }

  httpPostAsync(
    theUrl,
    JSON.stringify(data),
    function() {
      enableForm(formEle);
      if (typeof success === "string") {
        window.location.href = success;
      } else {
        success();
      }
    },
    function(response) {
      enableForm(formEle);
      if (resultEle !== null) {
        var fieldErrors = false;
        var nonFieldErrors = false;
        response.errors.forEach(function(error) {
          if (!displayFieldError(error)) {
            nonFieldErrors = true;
            var errEle = document.createElement("div");
            errEle.textContent = error.text;
            resultEle.appendChild(errEle);
          } else {
            fieldErrors = true;
          }
        });
        if (fieldErrors && !nonFieldErrors) {
          var errEle = document.createElement("div");
          errEle.textContent = "See fields for errors";
          resultEle.appendChild(errEle);
        }
      } else {
        console.log("Form failure");
        console.log(response);
      }
    }
  );

  return false;
}

function getAccounts(success, failure) {
  httpGetAsync("bin/accounts.php", success, failure);
}

function getAccountById(id, success, failure) {
  httpGetAsync("bin/account.php?id=" + id, success, failure);
}

function getAccountByName(name, success, failure) {
  httpGetAsync("bin/account.php?name=" + name, success, failure);
}

function getStats(success, failure) {
  httpGetAsync("bin/stats.php", success, failure);
}

function getStat(id, success, failure) {
  httpGetAsync("bin/stat.php?id=" + id, success, failure);
}

function error(message) {
  document.querySelector("body").innerHTML = message;
}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function numberWithCommas(x) {
  return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function displayDatetime(timestamp) {
  //var m = new Date(timestamp.replace(/-/g, '/').replace(' ', 'T'));
  var s = timestamp.replace(/[ :]/g, "-").split("-"),
      m = new Date(Date.UTC( s[0], s[1]-1, s[2], s[3], s[4], s[5] ));
  return ("0" + m.getDate()).slice(-2) + "/" +
         ("0" + (m.getMonth()+1)).slice(-2) + "/" +
         m.getFullYear() + " " +
         ("0" + m.getHours()).slice(-2) + ":" +
         ("0" + m.getMinutes()).slice(-2);
}

function initaliseDateTime() {

  /*To use,
   * - include res/include/timestamp.php in page
   * - call initialiseDateTime() on load and store returned object
   * - call <returned>.getTimestamp() to get selected timestamp
   * - catch any thrown error text
   */

  var nativePicker = document.querySelector('.native-timestamp');
  var fallbackPicker = document.querySelector('.fallback-timestamp');

  var datePicker = document.querySelector('#date');
  var yearSelect = document.querySelector('#year');
  var monthSelect = document.querySelector('#month');
  var daySelect = document.querySelector('#day');
  var hourSelect = document.querySelector('#hour');
  var minuteSelect = document.querySelector('#minute');

  // hide fallback initially
  fallbackPicker.style.display = 'none';

  var timestampFallback = false;
  // test whether a new datetime-local input falls back to a text input or not
  var test = document.createElement('input');
  test.type = 'datetime-local';
  // if it does, run the code inside the if() {} block
  if (test.type === 'text') {

    timestampFallback = true;

    // hide the native picker and show the fallback
    nativePicker.style.display = 'none';
    fallbackPicker.style.display = 'block';

    // populate the days and years dynamically
    // (the months are always the same, therefore hardcoded)
    populateDays(monthSelect.value);
    populateYears();
  }
  populateHours();
  populateMinutes();

  function populateDays(month) {
    // delete the current set of <option> elements out of the
    // day <select>, ready for the next set to be injected
    while(daySelect.firstChild){
      daySelect.removeChild(daySelect.firstChild);
    }

    var option = document.createElement('option');
    option.textContent = "day";
    option.value = "";
    option.selected = true;
    daySelect.appendChild(option);

    // Create variable to hold new number of days to inject
    var dayNum;

    // 31 or 30 days?
    if(month === 'January' | month === 'March' | month === 'May' | month === 'July' | month === 'August' | month === 'October' | month === 'December') {
      dayNum = 31;
    } else if(month === 'April' | month === 'June' | month === 'September' | month === 'November') {
      dayNum = 30;
    } else {
    // If month is February, calculate whether it is a leap year or not
      var year = yearSelect.value;
      (year - 2016) % 4 === 0 ? dayNum = 29 : dayNum = 28;
    }

    // inject the right number of new <option> elements into the day <select>
    for(i = 1; i <= dayNum; i++) {
      var option = document.createElement('option');
      option.textContent = (i < 10) ? ("0" + i) : i;
      daySelect.appendChild(option);
    }

    // if previous day has already been set, set daySelect's value
    // to that day, to avoid the day jumping back to 1 when you
    // change the year
    if(previousDay) {
      daySelect.value = previousDay;

      // If the previous day was set to a high number, say 31, and then
      // you chose a month with less total days in it (e.g. February),
      // this part of the code ensures that the highest day available
      // is selected, rather than showing a blank daySelect
      if(daySelect.value === "") {
        daySelect.value = previousDay - 1;
      }

      if(daySelect.value === "") {
        daySelect.value = previousDay - 2;
      }

      if(daySelect.value === "") {
        daySelect.value = previousDay - 3;
      }
    }
  }

  function populateYears() {
    // get this year as a number
    var date = new Date();
    var year = date.getFullYear();

    var option = document.createElement('option');
    option.textContent = "year";
    option.value = "";
    option.selected = true;
    yearSelect.appendChild(option);

    // Make this year, and the 100 years before it available in the year <select>
    for(var i = 0; year-i >= 2016; i++) {
      var option = document.createElement('option');
      option.textContent = year-i;
      yearSelect.appendChild(option);
    }
  }

  function populateHours() {

    var option = document.createElement('option');
    option.textContent = "h";
    option.value = "";
    option.selected = true;
    hourSelect.appendChild(option);

    // populate the hours <select> with the 24 hours of the day
    for(var i = 0; i <= 23; i++) {
      var option = document.createElement('option');
      option.textContent = (i < 10) ? ("0" + i) : i;
      hourSelect.appendChild(option);
    }
  }

  function populateMinutes() {

    var option = document.createElement('option');
    option.textContent = "m";
    option.value = "";
    option.selected = true;
    minuteSelect.appendChild(option);

    // populate the minutes <select> with the 60 hours of each minute
    for(var i = 0; i <= 59; i++) {
      var option = document.createElement('option');
      option.textContent = (i < 10) ? ("0" + i) : i;
      minuteSelect.appendChild(option);
    }
  }

  // when the month or year <select> values are changed, rerun populateDays()
  // in case the change affected the number of available days
  yearSelect.onchange = function() {
    populateDays(monthSelect.value);
  }

  monthSelect.onchange = function() {
    populateDays(monthSelect.value);
  }

  //preserve day selection
  var previousDay;

  // update what day has been set to previously
  // see end of populateDays() for usage
  daySelect.onchange = function() {
    previousDay = daySelect.value;
  }

  return {
    getTimestamp: function() {

      var timestampError = null;
      var date = "";
      var time = "";
      var timestamp = null;

      if (timestampFallback) {

        var day = daySelect.value;
        var month = monthSelect.value;
        var year = yearSelect.value;

        if (day === "" && month === "" && year === "") {
          // Nothing, defaulted later
        } else if (day === "" || month === "" || year === "") {
          timestampError = "Incomplete date";
        } else {
          date = year + "-" + month + "-" + day;
        }

      } else {

        date = datePicker.value;
      }

      if (timestampError === null && date === "") {
        var d = new Date();
        date = d.getFullYear() + "/" +
               ("0" + (d.getMonth()+1)).slice(-2) + "/" +
               ("0" + d.getDate()).slice(-2);
        if (hourSelect.value === "" && minuteSelect.value === "") {
          // No time and no date, select now
          time = ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
        } else {
          timestampError = "Time not allowed without date";
        }
      } else {
        date = date.replace(/-/g, '/');
      }

      if (timestampError === null && time === "") {
        if (hourSelect.value === "" && minuteSelect.value === "") {
          var d = new Date("2000/05/01 00:00");
          time = ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getUTCMinutes()).slice(-2);
        } else if (hourSelect.value === "" || minuteSelect.value === "") {
          timestampError = "Incomplete time";
        } else {
          time = hourSelect.value + ":" + minuteSelect.value;
        }
      }

      if (timestampError !== null) {
        throw timestampError;
      } else {
        var d = new Date(date + " " + time);
        return d.getUTCFullYear() + "-" +
                 ("0" + (d.getUTCMonth()+1)).slice(-2) + "-" +
                 ("0" + d.getUTCDate()).slice(-2) + " " +
                 ("0" + d.getUTCHours()).slice(-2) + ":" +
                 ("0" + d.getUTCMinutes()).slice(-2);

      }
    }
  };
}
