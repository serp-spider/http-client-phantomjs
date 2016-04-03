"use strict";


var system = require('system');
var page = require('webpage').create();
var pageStatusCode = null;
var pageHeaders = [];

var lastResourceError = null;


// PARSE THE INPUT /////////////////////
//
//
if (system.args.length !== 2) {
    console.error('A json representation of the request is required.');
    phantom.exit(1);
}

try {
    var inputData = JSON.parse(system.args[1]);
} catch (e) {
    console.error("unable to parse json string: ");
    phantom.exit(1);
}

if (!inputData) {
    console.error('Invalid input data. A valid json is required');
    phantom.exit(1);
}

if (!inputData.url) {
    console.error('No url was specified');
    phantom.exit(1);
}


var url = inputData.url;
var method = inputData.method || "GET";
var headers = inputData.headers || {};
var data = inputData.data || "";
//
//
// PARSE THE INPUT /////////////////////




// CONFIGURE THE PAGE //////////////////
//
//

page.onResourceError = function (resourceError) {
    lastResourceError = resourceError.errorString;
};

page.onResourceReceived = function (resource) {
    if (page.url == resource.url) {
        pageStatusCode = resource.status;
        pageHeaders = resource.headers;
    }
};

page.onError = function (msg, trace) {
    console.error('Error: ' + msg);
    phantom.exit(1);
};

page.viewportsize = inputData.viewportsize || {width: 1680, height: 1050};

if (headers['User-Agent']) {
    page.settings.userAgent = headers['User-Agent'];
}


if (!headers['Accept']) {
    headers['Accept'] = "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"
}
//
//
// CONFIGURE THE PAGE //////////////////




var settings = {
    operation: method,
    headers: headers,
    data: data
};

page.open(url, settings, function (status) {
    if (status !== 'success') {
        console.error('Error: could not fetch the page for the url: "' + url + '". Reason: ' + lastResourceError);
        phantom.exit(1);
    } else {

        var headers = {};
        for (var i=0; i<pageHeaders.length; i++) {
            headers[pageHeaders[i].name.toLocaleLowerCase()] = pageHeaders[i].value;
        }

        var data = {
            url: page.url,
            content: headers['content-type'] == 'application/json' ? page.plainText: page.content,
            status: pageStatusCode,
            headers: headers
        };
        console.log(JSON.stringify(data));

        // Workaround fixing exit error
        // https://github.com/ariya/phantomjs/issues/12697#issuecomment-61586030
        if (page) {
            page.close(); }
        setTimeout(function () {
            phantom.exit(); }, 0);
        phantom.onError = function (){};
    }

});


