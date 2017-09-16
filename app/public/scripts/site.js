
$(document).ready(function() {
  // Once doc loaded, call mathJax
  renderMath();
}

// Add a CSS file to the head
function loadExternalCSS(cssFile) {
  if(document.createStyleSheet) {
    try { document.createStyleSheet(cssFile); } catch (e) { }
  }
  else {
    var css;
    css         = document.createElement('link');
    css.rel     = 'stylesheet';
    css.type    = 'text/css';
    css.media   = "all";
    css.href    = cssFile;
    document.getElementsByTagName("head")[0].appendChild(css);
  }
}

// Add a JS file to the head
function loadExternalJS(jsFile) {
  var file;
  file         = document.createElement('script');
  file.type    = 'text/javascript';
  file.src     = jsFile;
  document.getElementsByTagName("head")[0].appendChild(file);
}
