
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

// Auto split screen on very large view
var largeWin = false;
$(window).resize(function() {
  var body_width = $("#body_group").width();
  $("#ace0").css("width", body_width);
  if ($(window).width() > 1300 && largeWin == false) {
    largeWin = true;
    splitWindows();
  }
  else if ($(window).width() < 1300) {
    largeWin = false;
    stackWindows();
 }
});

function splitWindows() {
    $('.container').attr('class', 'container_fluid');
    $(".container_fluid").css("margin-left", "50px");
    $(".container_fluid").css("margin-right", "50px");
    $('#col1').removeClass('col-md-12').addClass('col-md-6');
    $('#col2').removeClass('col-md-12').addClass('col-md-6');
}

function stackWindows() {
    $('.container_fluid').attr('class', 'container');
    $(".container").css("margin-left", "");
    $(".container").css("margin-right", "");
    $('#col1').removeClass('col-md-6').addClass('col-md-12');
    $('#col2').removeClass('col-md-6').addClass('col-md-12');
}

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
        // Capture published state as boolean
        var box = data.published == 1 ? true : false;
        $("#is_published").prop("checked", box);

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

// Output article in preview div
function previewArticle() {
  // Make sure text area has latest from editor
  var editor = ace.edit("ace0");
  form_body = $('#body');
  form_body.val(editor.getSession().getValue());

  // Get JSON data through post
  var posting = $.post('/article/parse_md', $('#publish_form').serialize());
  // Print returned body in preview div
  posting.done(function(msg) {
    $('#preview').empty().append(msg['body']);
  });
}

// Preview button click
$('#prev').click(function() {
  previewArticle();
});
