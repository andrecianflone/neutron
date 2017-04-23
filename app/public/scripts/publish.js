
// ------------------------------------------------------
// ACE EDITOR
// ------------------------------------------------------

// Main setup
$(document).ready(function() {
  // Setup ace
  editor = ace.edit('editor');
  editor.setTheme('ace/theme/monokai');

  // Setup resizable
  resizable = $("#resizable");
  formBody = $("#body");
  resizable.height(formBody.height()); // same height as form body
  formBody.css("display","none"); // hide body
  // Make editor resizable
  resizable.resizable({
    handles: 's',
    minHeight: 200,
    resize: function( event, ui ) {
      editor.resize();
      previewEqualEditor();
    }
  });

  configureEditor(editor, formBody);
  addButtons(editor, resizable);
});

// Configure the editor
function configureEditor(editor, formBody) {
  editor.getSession().setTabSize(2);
  editor.getSession().setValue(formBody.val()); // get text from form body
  editor.getSession().setUseSoftTabs(true);
  editor.getSession().setMode("ace/mode/markdown");
  editor.setTheme("ace/theme/solarized_dark");
  editor.setFontSize(14);
  editor.setKeyboardHandler("ace/keyboard/vim"); //vim bindings
  editor.getSession().setUseWrapMode(true); // wrap text
  editor.renderer.setShowGutter(true);

  // Keyboard shortcuts
  editor.commands.addCommand({
    // Save shortcut
    name: 'save',
    bindKey: {win: "Ctrl-S", "mac": "Cmd-S"},
    exec: function(editor) {
      updateArticle(); //in publish.js
      // should take value and send to self via ajax
    },
    // Preview shortcut
    bindKey: {win: "Ctrl-P", "mac": "Cmd-P"},
    exec: function(editor) {
      previewArticle();
    }
  });
}

/**
 * Add buttons after Ace editor
 */
function addButtons(editor, afterNode) {
  addJavascript(afterNode, editor);
  addMarkdown(afterNode, editor);
  addGutter(afterNode, editor);
}

/**
 * Add single button
 */
function addButton(afterNode, btnId, text) {
  var button = `
    <button id="${btnId}" type="button" class="btn btn-primary">${text}</button>
    `;
  $(button).insertAfter(afterNode);
}

// Add gutter toggle and function
function addGutter(textArea, editor) {
  addButton(textArea, "gutter", "gutter");

  $( "#gutter" ).click(function() {
    gutter = gutter == true? false : true;
    editor.renderer.setShowGutter(gutter);
  });
}


// Add gutter toggle and event function
function addMarkdown(textArea, editor) {
  addButton(textArea, "md", "markdown");

  // Set mode to markdown
  $( "#md" ).click(function() {
    editor.getSession().setMode("ace/mode/markdown");
  });

}

// Add gutter toggle and function
function addJavascript(textArea, editor) {
  addButton(textArea, "js", "javascript");

  // Set mode to javascript
  $( "#js" ).click(function() {
    editor.getSession().setMode("ace/mode/javascript");
  });
}

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
  previewEqualEditor();
}

// Make preview aligned with editor
function previewEqualEditor(){
  // Only resize if in split mode
  if (stacked == 1)
    return;
  resizable = $('#resizable');
  // Get resizable offset from
  resOffset = resizable.offset().top - $('#col1').offset().top;
  // Set col above preview equal to offset
  $('#top_col').css('height', resOffset);

  // Set preview size equal to resizable
  editHeight = resizable.height();
  $('#preview').css('height', editHeight);
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
    isResizing = true; // resize possible while mouse click
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
// ----------------------------------------------------------------------------
// FORM HANDLING
// ----------------------------------------------------------------------------
// Capture submit button for new page
$(function() { //shorthand document.ready function
  $('#publish_form').on('submit', function(e) { //use on if jQuery 1.7+
    e.preventDefault();  // Stop form from submitting normally

    // First, update form with editor content
    var editor = ace.edit('editor');
    form_body = $('#body');
    form_body.val(editor.getSession().getValue());

    error_out = $('#preview');
    form_data = $('#publish_form').serialize();

    $.ajax({
        url: "new",
        type: 'POST',
        data: form_data,
        success: function(msg) {
          printResult(msg);
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

// When a webpage is selected from the dropdown, load content in form
$('#category').on('change', function() {
  clearForm();
  loadArticlesFromSelectedCategory($('#category'), $('#article_sel'));
});

// Load all article id/title from category
function loadArticlesFromSelectedCategory(source, target, sort) {
  if (typeof(sort)==='undefined') sort = " title ASC";
  error_out = $('#preview');
  $.ajax({
      url: 'getpagesfromcategory/' + source.val() + '/' + sort,
      type: 'GET',
      dataType: 'json',
      success: function(j) {
        var options = '';
        // Clear selection
        target.empty();
        // Add default
        target.append($('<option>', {
              value: "null",
              text: "Select article to update"
        }));
        // Add returned values
        for (var i = 0; i < j.length; i++) {
          target.append($('<option>', {
                value: j[i].id,
                text: j[i].title
          }));
        }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        error_out.empty();
        error_out.append(xhr.status);
        error_out.append(thrownError);
        error_out.append(xhr.responseText);
      }
  });
}

// Sort the list
$(document).ready(function() {
  $('input[type=radio][name=sortArticle]').change(function() {
    sort = '';
    if (this.value == 'date') {
      sort = "`dt_display` DESC";
    }
    else if (this.value == 'title') {
      sort = "`title` ASC";
    }
    source = $('#category');
    target = $('#article_sel');
    loadArticlesFromSelectedCategory(source, target, sort) ;
  });
});

function clearForm() {
  $("#url").val('');
  $("#title").val('');
  $("#tags_set").val('');
  $("#dt_display").val('');
  $("#blurb").val('');
  $("#body").val('');
  $("#is_published").prop("checked", false);
  $("#parse_math").prop("checked", false);
  // Paste body to editor
  var editor = ace.edit('editor');
  editor.getSession().setValue('');
  // Flush preview window
  $("#preview").empty();
}

// When a webpage is selected from the dropdown, load content in form
$('#article_sel').on('change', function() {
  loadFromSelectedArticle($('#article_sel'));
});

// Load page into form
function loadFromSelectedArticle(elem) {
  if (elem.val() != "null") {
    $.getJSON('getpage/' + elem.val(), null,
      function(data){
        $("#url").val(data.url);
        $("#title").val(data.title);
        raw = data.dt_display.split(" ");
        dt = raw[0].split("-");
        dt = dt[0] + "-" + dt[1] + "-" + dt[2];
        $("#dt_display").val(dt);
        $("#tags_set").val(data.tags);
        $("#blurb").val(data.blurb);
        $("#body").val(data.body);
        var cat = data.category;
        if (cat == null || cat == '') {
          cat = "null";
        }
        $("#set_category").val(cat);
        // Capture published state as boolean
        var published = data.published == 1 ? true : false;
        $("#is_published").prop("checked", published);

        var parse_math = data.parse_math == 1 ? true : false;
        $("#parse_math").prop("checked", parse_math);
        // Paste body to editor
        var editor = ace.edit('editor');
        editor.getSession().setValue(data.body);
        // Flush preview window
        $("#preview").empty();
      }
    );
  }
}

// Delete articles
$('#delete').click(function() {
  if(window.confirm('Are you sure you want to delete this article?')) {
    var get = $.get('delete/ ' + $('#article_sel').val());

    get.done(function(msg) {
      printResult(msg);
    });
  }
});

// Update button click
$('#update').click(function() {
  updateArticle();
});
function updateArticle() {
  // First, copy data from editor to text
  var editor = ace.edit('editor');
  form_body = $('#body');
  form_body.val(editor.getSession().getValue());
  error_out = $('#preview');
  form_data = $('#publish_form').serialize();

  $.ajax({
      url: "update",
      type: 'POST',
      data: form_data,
      success: function(msg) {
        printResult(msg);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        error_out.empty();
        error_out.append(xhr.status);
        error_out.append(thrownError);
        error_out.append(xhr.responseText);
      }
  });
}

// Preview button click
$('#prev').click(function() {
  previewArticle();
});

// Output article in preview div
function previewArticle() {
  // Make sure text area has latest from editor
  var editor = ace.edit('editor');
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
      beforeSend: function(msg){
        output.html("Loading...");
      },
      success: function(msg) {
        // Print to the browser
        output.empty().append(msg['body']);
        // Fix the <code> formatting by rerunning prism
        prismRun(); // in prism.js file
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

function printResult(msg){
  // Print message to #result div
  result = $('#result')
  result.empty().append(msg);
  // Print message on top of the editor, and fade div
  var overlay = $("<div id=overlay>"+msg+"</div>")
  $('#editor').append(overlay);
  overlay.delay(3000).fadeOut();
}

//-----------------------------------------------------------------------------
// Upload file
//-----------------------------------------------------------------------------

// Capture some upload form buttons
// List all images in the folder
$( "#list_img" ).click(function() {
  result = $('#preview'); // where to print results
  error_out = result;
  var formData = new FormData(document.querySelector('#upload_file'));
  $.ajax({
    url: "listfiles",
    type: 'POST',
    data: formData,
    cache: false,
    contentType: false,
    enctype: 'multipart/form-data',
    processData: false,
    beforeSend: function() {
      result.empty();
      error_out.empty();
    },
    success: function(data, textStatus, jqXHR) {
      result.append('Files in directory: <br>');
      $.each(data, function(i, item) {
        result.append(item + '<br>');
      });
    },
    error: function(xhr, textStatus, error) {
      // Handle errors
      error_out.append("Error number: " + xhr.status + '<br>');
      error_out.append("Error thrown: " + error + '<br>');
      error_out.append('Error message: ' + xhr.responseText + '<br>');
    }
  });
});

// Create new dir using value in text field
$("#new_dir").click(function() {
  result = $('#preview'); // where to print results
  error_out = $('#preview');
  // Get current selection value
  var formData = new FormData(document.querySelector('#upload_file'));
  //dir = $('#newDirText').val();
  $.ajax({
    url: "new_dir",
    type: 'POST',
    data: formData,
    cache: false,
    contentType: false,
    enctype: 'multipart/form-data',
    processData: false,
    success: function(data, textStatus, jqXHR) {
      result.append('Successfully created directory: ' + data.responseText);
    },
    error: function(xhr, textStatus, error) {
      // Handle errors
      error_out.append("Error number: " + xhr.status + '<br>');
      error_out.append("Error thrown: " + error + '<br>');
      error_out.append('Error message: ' + xhr.responseText + '<br>');
    }
  });
});

// Capture the form submit and upload the files
$('#upload_file').submit(function (event) {
  event.stopPropagation(); // Stop stuff happening
  event.preventDefault(); // Totally stop stuff happening
  result = $('#upload_result'); // where to print results
  error_out = $('#preview');

  // Spinner while load
  $('#hater_spinner').show();

  var formData = new FormData($(this)[0]);
  $.ajax({
    url: "upload",
    type: 'POST',
    data: formData,
    cache: false,
    contentType: false,
    enctype: 'multipart/form-data',
    processData: false,
    beforeSend: function() {
      $('#hater_spinner').hide();
      result.show().empty();
      error_out.empty();
    },
    success: function(data, textStatus, jqXHR) {
      result.append('Successfully uploaded files: ' + data);
    },
    error: function(xhr, textStatus, error) {
      // Handle errors
      error_out.append("Error number: " + xhr.status + '<br>');
      error_out.append("Error thrown: " + error + '<br>');
      error_out.append('Error message: ' + xhr.responseText + '<br>');
    },
    complete: function(){
      $('#hater_spinner').hide();
    }
  });
});
