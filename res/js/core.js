
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
