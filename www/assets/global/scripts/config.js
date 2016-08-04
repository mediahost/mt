// get js config vars
var configJson = document.getElementById('jsConf');
var configVars = JSON.parse(configJson.textContent || configJson.innerHTML);

// create global vars
var basePath = configVars.basePath;
var lang = configVars.locale;
var currencySymbol = configVars.currencySymbol;
var currencyName = configVars.currencyName;
var links = configVars.links;
var applets = configVars.applets;
var loginError = configVars.loginError;
