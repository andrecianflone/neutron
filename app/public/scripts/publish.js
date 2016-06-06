
// New Page, disables default form submit
$(function() { //shorthand document.ready function
  $('#publish_form').on('submit', function(e) { //use on if jQuery 1.7+
    e.preventDefault();  //prevent form from submitting
    var data = $("#publish_form :input").serializeArray();
    var dict = convertToDict(data);
    console.log(dict);
  });
});

// When a webpage is selected, load it in the form
$('#article_sel').on('change', function() {
  if (this.value != "null") {
    $.getJSON('getpage/' + this.value, null,
      function(data){
        $("#url").val(data.url);
        $("#title").val(data.title);
        $("#blurb").val(data.blurb);
        $("#body").val(data.body);

        var editor = ace.edit("ace0");
        editor.getSession().setValue(data.body);
      }
    );
    //$('#submit').trigger('click');
  }
});

/**
 * Convenience function to convert form inputs into dict
 */
function convertToDict(obj) {
  var output = {}
  for (inp in obj) {
    console.log(inp.name);
    output[inp['name']] = inp['value'];
  }
  return output;
}



