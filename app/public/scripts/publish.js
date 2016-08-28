
// ------------------------------------------------------
// SPLIT/STACK PREVIEW WINDOW
// ------------------------------------------------------
var radioValue = $("input[name='optradio']:checked").val();
var autoStack = radioValue == 'auto' ? true : false;

var stacked = 1;
// Auto split screen on very large view
var largeWin = false;
// Check for split on window resize if in auto mode
$(window).resize(function() {
  if (autoStack) {
    splitStackWindows();
  }
  resizeEditor();
});

// Check for split on page load
$(function () {
  if (autoStack) {
    splitStackWindows();
  }
});

// On radio change
$(document).ready(function() {
  $('input[type=radio][name=optradio]').change(function() {
    if (this.value == 'auto') {
      autoStack = true;
      splitStackWindows();
    }
    else if (this.value == 'split') {
      autoStack = false;
      splitWindows();
    }
    else if (this.value == 'stack') {
      autoStack = false;
      stackWindows();
    }
  });
});

// Will split if larger than certain size
function splitStackWindows() {
  if ($(window).width() > 1300) {
    splitWindows();
  } else if ($(window).width() < 1300) {
    stackWindows();
  }
}

function resizeEditor() {
  var body_width = $("#body_group").width();
  $("#ace0").css("width", body_width);
}

function splitWindows() {
  if (stacked == 0)
    return;
  $('.container').attr('class', 'container_fluid');
  $(".container_fluid").css("margin-left", "50px");
  $(".container_fluid").css("margin-right", "50px");
  $('#col1').removeClass('col-md-12').addClass('col-md-6');
  $('#col2').removeClass('col-md-12').addClass('col-md-6');
  $('#col2').css('height', $('#col1').height());
  $('#preview').css('height', $('#col1').height());

  // Wrap preview
  $("#preview").wrap( "<div id='r_row' class='row'></div>" );
  $('#preview').before("<div id='top_col' class='col-md-12'></div>");
  $('#top_col').prepend("<div id='drag'></div>");
  $('#preview').addClass('col-md-12');
  $('#preview').css('overflow', 'auto');
  stacked = 0;
  resizeEditor();
}

function stackWindows() {
  if (stacked == 1)
    return;
  $('.container_fluid').attr('class', 'container');
  $(".container").css("margin-left", "");
  $(".container").css("margin-right", "");
  $('#col1').removeClass('col-md-6').addClass('col-md-12');
  $('#col2').removeClass('col-md-6').addClass('col-md-12');
  $('#col2').css('height', '');

  // Unwrap preview div
  $("#preview").unwrap( "#r_row" );
  $("#top_col").remove();
  $('#preview').removeClass('col-md-12');
  $('#preview').css('overflow', '');
  $('#drag').css('top', '0');
  stacked = 1;
  resizeEditor();
}

// Move bar
var isResizing = false;
var lastDownY = null;
var maxHeight = 0;

$(function () {
  var container = $('#r_row'),
    topcol = $('#top_col'),
    botcol = $('#preview'),
    handle = $('#drag');

  // Delegate col2 mouseover to #drag
  $('#col2').on('mousedown', '#drag', function (e) {
    maxHeight = $("#r_row").height();
    isResizing = true;
    lastDownY = e.screenY;
  });

  $(document).on('mousemove', function (e) {
    // we don't want to do anything if we aren't resizing.
    if (!isResizing)
      return;

    // Reset vars in case deleted
    container = $('#r_row');
    topcol = $('#top_col');
    botcol = $('#preview');
    handle = $('#drag');

    var offsetVert = Math.round(e.screenY - lastDownY);

    top_height = Math.min(topcol.height() + offsetVert, maxHeight);
    bot_height = maxHeight - top_height;

    topcol.css('height', top_height);
    botcol.css('height', bot_height);
    lastDownY = e.screenY;
    container.css('height', maxHeight);
    handle.css('top', top_height - handle.height());
  }).on('mouseup', function (e) {
    // stop resizing
    isResizing = false;
  });
});
// ------------------------------------------------------

// Capture submit button for new page
$(function() { //shorthand document.ready function
  $('#publish_form').on('submit', function(e) { //use on if jQuery 1.7+
    e.preventDefault();  // Stop form from submitting normally

    success_out = $('#result');
    error_out = $('#preview');
    form_data = $('#publish_form').serialize();

    $.ajax({
        url: "new",
        type: 'POST',
        data: form_data,
        success: function(msg) {
          success_out.empty().append(msg);
        },
        error: function (xhr, ajaxOptions, thrownError) {
          error_out.empty();
          error_out.append(xhr.status);
          error_out.append(thrownError);
          error_out.append(xhr.responseText);
        }
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
        // Capture published state as boolean
        var published = data.published == 1 ? true : false;
        $("#is_published").prop("checked", published);

        var parse_math = data.parse_math == 1 ? true : false;
        $("#parse_math").prop("checked", parse_math);

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
  // First, copy data from editor to text
  var editor = ace.edit("ace0");
  form_body = $('#body');
  form_body.val(editor.getSession().getValue());
  success_out = $('#result');
  error_out = $('#preview');
  form_data = $('#publish_form').serialize();

  $.ajax({
      url: "update",
      type: 'POST',
      data: form_data,
      success: function(msg) {
        success_out.empty().append(msg);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        error_out.empty();
        error_out.append(xhr.status);
        error_out.append(thrownError);
        error_out.append(xhr.responseText);
      }
  });
}

// Output article in preview div
function previewArticle() {
  // Make sure text area has latest from editor
  var editor = ace.edit("ace0");
  form_body = $('#body');
  form_body.val(editor.getSession().getValue());

  output = $('#preview');
  error_out = output;
  form_data = $('#publish_form').serialize();

  // Get the JSON data
  $.ajax({
      url: "/article/parse_md",
      type: 'POST',
      data: form_data,
      success: function(msg) {
        output.empty().append(msg['body']);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        error_out.empty();
        error_out.append(xhr.status);
        error_out.append(thrownError);
        error_out.append(xhr.responseText);
      }
  });

}

// Add a CSS to the head on the fly
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

// Add a CSS to the head on the fly
function loadExternalJS(jsFile) {
  var file;
  file         = document.createElement('script');
  file.type    = 'text/javascript';
  file.src     = jsFile;
  document.getElementsByTagName("head")[0].appendChild(file);
}

// Preview button click
$('#prev').click(function() {
  previewArticle();
});

// Update button click
$('#update').click(function() {
  updateArticle();
});
