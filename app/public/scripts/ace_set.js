
// Call ace editor once page finished loading
$(function(){
//ace.config.loadModule("ace/keyboard/vim", function(m) {
    //var VimApi = require("ace/keyboard/vim").CodeMirror.Vim
    //VimApi.defineEx("write", "w", function(cm, input) {
        //cm.ace.execCommand("save")
    //})
//});
  loadAce(true);
  addButtons()
});

// Hook up ACE editor to all textareas with data-editor attribute
function loadAce(gut) {
  var gutter = gut;
  var count = 0;
  $('textarea[data-editor]').each(function () {
    var textarea = $(this);
    var mode = textarea.data('editor');
    var editDiv = $('<div>', {
      position: 'absolute',
      width: textarea.width() + 26,
      height: textarea.height(),
      'class': textarea.attr('class'),
      'id': 'ace' + count++
    }).insertBefore(textarea);
    textarea.css('display', 'none');
    var editor = ace.edit(editDiv[0]);
    //myeditor = editor;
    //numEditors += 1;
    editor.getSession().setTabSize(2);
    editor.getSession().setValue(textarea.val());
    editor.getSession().setUseSoftTabs(true);
    editor.getSession().setMode("ace/mode/" + mode);
    editor.setTheme("ace/theme/solarized_dark");
    editor.setFontSize(14);
    editor.setKeyboardHandler("ace/keyboard/vim"); //vim bindings
    editor.getSession().setUseWrapMode(true); // wrap text
    editor.renderer.setShowGutter(gutter);

    // copy back to textarea on form submit...
    textarea.closest('form').submit(function () {
        textarea.val(editor.getSession().getValue());
    });

    // Save keyboard shortcut
    editor.commands.addCommand({
        name: 'save',
        bindKey: {win: "Ctrl-S", "mac": "Cmd-S"},
        exec: function(editor) {
          textarea.val(editor.getSession().getValue());
          updateArticle();
          // should take value and send to self via ajax
        }
    })

  });
};

/**
 * Add buttons after Ace editor
 */
function addButtons() {
  var textArea = document.querySelector('[data-editor]');
  var parentNode = textArea.parentNode
  var aceEditor = ace.edit('ace0');

  afterNode = textArea;
  addGutter(afterNode, aceEditor);
  addMarkdown(afterNode, aceEditor);
  addJavascript(afterNode, aceEditor);

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


// Add gutter toggle and function
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









