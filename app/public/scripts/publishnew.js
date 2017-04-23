
/**
 * Main class for the publish page
 */
class Publish {
  constructor() {
    formBody = $('#formBody');
    this.resizable = $('#resizable');
    this.preview = $('#preview');

    // Setup Editor
    this.editor = new Editor('editor', formBody, resizable, updateArticle, previewArticle);
    this.previewEqualEditor();

    // Setup Split stack
    splitStackRadio = $("input[name='optradio']:checked")
    this.splitstack = new SplitStack(splitStackRadio, 1, this.preview);
  }

  // Make preview aligned with editor
  function previewEqualEditor(){
    // Only resize if in split mode
    if (this.splitstack.stacked == 1)
      return;
    // Get resizable offset from
    resOffset = this.resizable.offset().top - $('#col1').offset().top;
    // Set col above preview equal to offset
    $('#top_col').css('height', resOffset);

    // Set preview size equal to resizable
    editHeight = this.resizable.height();
    this.preview.css('height', editHeight);
  }
} // end Publish

/**
 * Manages Ace Editor
 */
class Editor {
  /**
   * @param {string} editorId - div id name of editor
   * @param {object} editorField - dom of form body
   * @param {function} updateArticle - editor calls this fn on update
   * @param {function} previewArticle - editor calls this fn on preview
   */
  constructor(editorId, editorField, resizable, updateArticle, previewArticle) {
    theme = 'ace/theme/monokai';
    resizable = $("#resizable");
    this.updateArticle = updateArticle;
    this.previewArticle = previewArticle;

    // Setupt the editor
    this.editor = basicSetup(editorId);
    this.makeResizable(resizable, editorField);
    this.setKeyboardShortcuts(updateArticle, previewArticle);
    // Add buttons after resizable object
    this.addJavascript(resizable);
    this.addMarkdown(resizable);
    this.addGutter(resizable);
  }

  function basicSetup(editorId, theme) {
    editor = ace.edit(editorId);
    editor.setTheme(theme);
    editor.getSession().setTabSize(2);
    editor.getSession().setValue(formBody.val()); // get text from form body
    editor.getSession().setUseSoftTabs(true);
    editor.getSession().setMode("ace/mode/markdown");
    editor.setFontSize(14);
    editor.setKeyboardHandler("ace/keyboard/vim"); //vim bindings
    editor.getSession().setUseWrapMode(true); // wrap text
    editor.renderer.setShowGutter(true);
    return editor;
  }

  function makeResizable(resizable, editorField) {
    // Setup resizable
    resizable.height(editorField.height()); // same height as form body
    editorField.css("display","none"); // hide body
    // Make editor resizable
    resizable.resizable({
      handles: 's',
      minHeight: 200,
      resize: function( event, ui ) {
        editor.resize();
        previewEqualEditor();
      }
    });
  }

  function setKeyboardShortcuts() {
    // Keyboard shortcuts
    this.editor.commands.addCommand({
      // Save shortcut
      name: 'save',
      bindKey: {win: "Ctrl-S", "mac": "Cmd-S"},
      exec: function(editor) {
        this.updateArticle(); //in publish.js
        // should take value and send to self via ajax
      },
      // Preview shortcut
      bindKey: {win: "Ctrl-P", "mac": "Cmd-P"},
      exec: function(editor) {
        this.previewArticle();
      }
  });
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
  function addGutter(textArea) {
    addButton(textArea, "gutter", "gutter");

    $( "#gutter" ).click(function() {
      gutter = gutter == true? false : true;
      this.editor.renderer.setShowGutter(gutter);
    });
  }


  // Add gutter toggle and event function
  function addMarkdown(textArea) {
    addButton(textArea, "md", "markdown");

    // Set mode to markdown
    $( "#md" ).click(function() {
      this.editor.getSession().setMode("ace/mode/markdown");
    });

  }

  // Add gutter toggle and function
  function addJavascript(textArea) {
    addButton(textArea, "js", "javascript");

    // Set mode to javascript
    $( "#js" ).click(function() {
      this.editor.getSession().setMode("ace/mode/javascript");
    });
  }

} // end Editor class

class SplitStack {

  /**
   * @param {dom} radioGroup - group with options for split/stack
   * @param {int} stacked - 0 or 1, if 1 then starts off stacked
   * @param {object} preview - dom for preview div
   */
  constructor(radioGroup, stacked, preview) {
    this.radioGroup = radioGroup;
    this.stacked = stacked; //default to passed stacked
    this.preview = preview;

    radioValue = this.radioGroup.val();
    this.autoStack = radioValue == 'auto' ? true : false;

    // Split right away if auto
    if (this.autoStack) {splitStackWindows();}

    // Resize on Window size change
    this.monitorWindowResize();

    // Monitor radio button changes
    this.monitorRadioChange();

  }

  /**
   * Auto split screen on large view
   * Check for split on window resize if in auto mode
   */
  function monitorWindowResize() {
    $(window).resize(function() {
      if (this.autoStack) {
        this.splitStackWindows();
      }
    });
  }

  function monitorRadioChange() {
    $(document).ready(function() {
      this.radioGroup.change(function() {
        if (this.value == 'auto') {
          this.autoStack = true;
          this.splitStackWindows();
        }
        else if (this.value == 'split') {
          this.autoStack = false;
          this.splitWindows();
        }
        else if (this.value == 'stack') {
          this.autoStack = false;
          this.stackWindows();
        }
      });
    });
  }

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
    this.preview.css('height', $('#col1').height());

    // Wrap preview
    $("#preview").wrap( "<div id='r_row' class='row'></div>" );
    $('#preview').before("<div id='top_col' class='col-md-12'></div>");
    $('#top_col').prepend("<div id='drag'></div>");
    this.preview.addClass('col-md-12');
    this.preview.css('overflow', 'auto');

    this.stacked = 0;
    this.previewEqualEditor();
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
    this.preview.unwrap( "#r_row" );
    $("#top_col").remove();
    this.preview.removeClass('col-md-12');
    this.preview.css('overflow', '');
    $('#drag').css('top', '0');
    this.stacked = 1;
  }

}// end SplitStack class

/**
 * Handle the form UI
 */
class FormHandler {
  /**
   * @param {object} publishForm - dom of publish form
   * @param {object} editor - Ace editor object
   */
  constructor(publishForm, editor, formBody) {
    this.publishForm = publishForm;
    this.editor = editor;
    this.formBody = formBody;
    this.result = $('#result');
    this.errorOut = $('#preview');

    this.monitorSubmit();
    monitorCategory($('#category'));
  }

  /**
   * Capture submit button for new page
   */
  function monitorSubmit() {
    $(function() { //shorthand document.ready function
      this.publishForm.on('submit', function(e) { //use on if jQuery 1.7+
        e.preventDefault();  // Stop form from submitting normally

        // First, update form with editor content
        this.formBody.val(this.editor.getSession().getValue());

        form_data = this.publishForm.serialize();
        this.ajaxSend("send", "new", 'POST', form_data);

      });
    });
  }

  // When a webpage is selected from the dropdown, load content in form
  function monitorCategory(category) {
    category.on('change', function() {
      this.clearForm();
      loadArticlesFromSelectedCategory($('#category'), $('#article_sel'));
    });
  }

  function loadArticlesFromSelectedCategory(source, target, sort) {
    if (typeof(sort)==='undefined') sort = " title ASC";
    url: 'getpagesfromcategory/' + source.val() + '/' + sort;
    results = this.ajax(url, 'GET', 'json');

    var options = '';
    // Clear selection
    target.empty();
    // Add default
    target.append($('<option>', {
          value: "null",
          text: "Select article to update"
    }));
    // Add returned values
    for (var i = 0; i < results.length; i++) {
      target.append($('<option>', {
            value: results[i].id,
            text: results[i].title
      }));
    }
  }

  function monitorListSort() {
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
  }

  function monitorArticleSelect() {
    // When a webpage is selected from the dropdown, load content in form
    $('#article_sel').on('change', function() {
      loadFromSelectedArticle($('#article_sel'));
    });
  }

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


  /**
   * Setupt monitors for certain actions on the form
   */
  function monitorActions() {
    // Monitor delete click
    $('#delete').click(function() {
      if(window.confirm('Are you sure you want to delete this article?')) {
        this.ajaxSend('delete/ ' + $('#article_sel').val());
      }
    });

    // Monitor Update button click
    $('#update').click(function() {
      this.updateArticle();
    });

    // Preview button click
    $('#prev').click(function() {
      this.previewArticle();
    });
  }

  function updateArticle() {
    // First, copy data from editor to text
    form_body.val(this.editor.getSession().getValue());
    formData = this.publishForm.serialize();
    this.ajaxSend("update", 'POST', formData);
  }

  /**
   * Returns data from the request formatted in data type asked
   */
  function ajaxGet(url, requestType, dataType) {
    $.ajax({
        url: url,
        type: requestType,
        dataType: dataType,
        success: function(data) {
          return data;
        },
        error: function (xhr, ajaxOptions, thrownError) {
          this.printError(xhr, thrownError);
        }
    });
  }

  /**
   * @param {string} requestType - 'GET' or 'POST'
   */
  function ajaxSend(url, requestType, data) {
    $.ajax({
        url: url,
        type: requestType,
        data: data,
        success: function(msg) {
          this.printResult(msg);
        },
        error: function (xhr, ajaxOptions, thrownError) {
          this.printError(xhr, thrownError);
        }
    });
  }

  function printError(xhr, error) {
    this.printResult(error);
    this.errorOut.empty();
    this.errorOut.append(xhr.status);
    this.errorOut.append(error);
    this.errorOut.append(xhr.responseText);
  }

  function printResult(msg){
    // Print message to #result div
    this.result.empty().append(msg);
    // Print message on top of the editor, and fade div
    var overlay = $("<div id=overlay>"+msg+"</div>")
    this.editor.append(overlay);
    overlay.delay(3000).fadeOut();
  }

  // Output article in preview div
  function previewArticle() {
    // Make sure text area has latest from editor
    this.formBody.val(this.editor.getSession().getValue());

    output = $('#preview');
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
          this.printError(xhr, thrownError);
        }
    });
  }

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
}// end FormHandler class

class Upload {
}// end Upload class

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
