// get js config vars
var configJson = document.getElementById('jsConf');
var configVars = JSON.parse(configJson.textContent || configJson.innerHTML);

// create global vars
var basePath = configVars.basePath;
var lang = configVars.lang;
var currencySymbol = configVars.currencySymbol;
