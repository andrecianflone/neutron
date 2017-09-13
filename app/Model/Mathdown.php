<?php

namespace Neutron\Model;

/**
 * Deals with Latex math in the Markdown
 */
class Mathdown {

  public function __construct($math_mode) {
    // Math mode should be 'katex' or 'mathjax'
    $this->math_mode = $math_mode;
  }

  /**
   * Scan text for Latex math and convert for use with Katex
   */
  public function parsemath_pre($text) {
    $result = $this->add_tags($text);
    if($this->math_mode=='katex') {
      $result = $this->add_katex_links($result);
    }
    return $result;
  }

  /**
   * Remove markdown safe tags
   * Unfortunately, no inline tags are exempt from markdown, so must use ticks
   * Here we remove the ticks after the parsedown pass
   */
  public function parsemath_post($text) {
    $pattern1 = "/<\/p>[\n\r]+<div><spaninline/";
    $replacement1 = '<span';
    $pattern2 = "/spaninline><\/div>[\n\r]+<p>/";
    $replacement2 = 'span> ';
    $result = preg_replace(
        array($pattern1, $pattern2),
        array($replacement1, $replacement2),
        $text
    );
    return $result;
  }

  /**
   * Find math with regex and add proper tags
   * Adds block-level HTML tags, <div></div> around math, otherwise Markdown
   * screws up with the math parsing
   */
  private function add_tags($text) {
    // match inline $math$
    // first paren group: negative lookbehind, not preceded by '$'
    // last paren group: negative lookahead, not followed by '$'
    // Middle paren group: what we actually want to keep, we don't want the '$'
    // The rest: starts with '$', followed by minimum 1 char, ending with '$'
    $pattern1 = "/(?<!\\$)[\$]([^\$\n]+)[\$](?!\\$)/";
    if($this->math_mode=='mathjax') {
      $replacement1 = "\n<div><spaninline class='math inline'>\($1\)</spaninline></div>\n";
    } else {
      $replacement1 = "\n<div><spaninline class='math inline'>$1</spaninline></div>\n";
    }

    // match multiline $$math$$:
    // Starts/Ends with two '$'
    // Must have min 1 char in between
    $pattern2 = "/[\$]{2}([^\$]+)[\$]{2}/";
    if($this->math_mode=='mathjax') {
      $replacement2 = '<div><span class="math center">\[$1\]</span></div>';
    } else {
      $replacement2 = '<div><span class="math center">$1</span></div>';
    }

    // match multiline with \beg \end equation:
    // Must have the 's' option to check new lines
    $pattern3 = '/\\\begin{equation}(.+)\\\end{equation}/s';
    if($this->math_mode=='mathjax') {
      $replacement3 = '<div><span class="math center">\[$1\]</span></div>';
    } else {
      $replacement3 = '<div><span class="math center">$1</span></div>';
    }

    $result = preg_replace(
        array($pattern1, $pattern2, $pattern3),
        array($replacement1, $replacement2, $replacement3),
        $text
    );
    return $result;
  }


  /**
   * Katex Math
   * Add needed scripts/css to make the math work
   * Alternatively, you may want to use katex:
   */
  private function add_katex_links($text) {
    $text .= "\n";
    $text .= '<script type="text/javascript">';
    $text .= 'loadExternalCSS("https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.6.0/katex.min.css");';
    $text .= 'loadExternalJS("https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.6.0/katex.min.js");';
    $text .= 'loadExternalJS("/scripts/mathdown.js");';
    $text .= '</script>';
    return $text;
  }

}
?>
