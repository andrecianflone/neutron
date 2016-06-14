
// Capture submit button for new page
$(function() { //shorthand document.ready function
  $('#publish_form').on('submit', function(e) { //use on if jQuery 1.7+
    e.preventDefault();  // Stop form from submitting normally

    // Send form data with ajax
    var posting = $.post('new', $('#publish_form').serialize());

    // Print returned message
    posting.done(function(msg) {
      $('#result').empty().append(msg);
    });

  });
});


// When a webpage is selected, load dropdown menu
$('#article_sel').on('change', function() {
  loadDropDown($('#article_sel'));
});
function loadDropDown(elem) {
  if (elem.val() != "null") {
    $.getJSON('getpage/' + elem.val(), null,
      function(data){
        $("#url").val(data.url);
        $("#title").val(data.title);
        $("#blurb").val(data.blurb);
        $("#body").val(data.body);
        $("#

        var editor = ace.edit("ace0");
        editor.getSession().setValue(data.body);
      }
    );
  }
}

// Delete articles
$('#delete').click(function() {
  if(window.confirm('Are you sure you want to delete this article?')) {
    var get = $.get('delete/ ' + $('#article_sel').val());

    get.done(function(msg) {
      $('#result').empty().append(msg);
    });
  }
});

function updateArticle() {
  // Send form data with ajax
  var posting = $.post('update', $('#publish_form').serialize());

  // Print returned message
  posting.done(function(msg) {
    $('#result').empty().append(msg);
  });
}

