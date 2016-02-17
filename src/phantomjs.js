"use strict";


var system = require('system');
var page = require('webpage').create();
var finalUrl = null;
var redirectCount = 0;

if (system.args.length !== 2) {
    console.error('A json representation of the request is required.');
    phantom.exit(1);
}

try {
    var inputData = JSON.parse(system.args[1]);
}catch(e){
    console.log("unable to parse json string: ");
    phantom.exit(1);
}

if(!inputData){
    console.error('Invalid input data. A valid json is required');
    phantom.exit(1);
}

if(!inputData.url){
    console.error('No url was specified');
    phantom.exit(1);
}

var url = inputData.url;
var method = inputData.method || "GET";
var headers = inputData.headers || {};
var data = inputData.data || "";
var maxRedirect = inputData.maxRedirect || 20;

if(headers['User-Agent']){
    page.settings.userAgent = headers['User-Agent'];
}


page.onResourceReceived = function(resource) {
    finalUrl = resource.url;
    redirectCount++;
    if(redirectCount>maxRedirect){
        console.error('Error: too many redirects');
        phantom.exit(1);
    }
    console.log(resource.url);

    console.log(redirectCount);

};


page.onResourceError = function(resourceError) {
    console.error('Error: ' + resourceError);
    phantom.exit(1);
};
page.onError = function(msg, trace) {
    console.error('Error: ' + msg);
    phantom.exit(1);
};


var httpCall = function(to, settings){
    page.open(url, settings, function (status) {
        if (status !== 'success') {
            console.error('Error: could not reach the url: ' + url);
            phantom.exit(1);
        } else {
            console.log(url);
            console.log(finalUrl);
            console.log(page.content);
            phantom.exit();
        }

    });
};

var settings = {
    operation: method,
    headers: headers,
    data: data
};

httpCall(url, settings);


