 Call Katex once resouces ready (ie, css/script dependencies)
var wait_here = setInterval(function(){
  if(typeof(katex) != "undefined"){
    clearInterval(wait_here)
    loadKatex();
   }
},50)

function loadKatex() {
  $(".math").each(function() {
    var equation = $(this).text();
    dom_el = $(this).get(0); // get current as dom element
    try {
      if($(this).hasClass("center")){
        katex.render(equation, dom_el, {displayMode: true});
      } else {
        katex.render(equation, dom_el);
      }
    } catch(err) {
      $(this).html("<span class='err'>"+err);
    }
  });
};

