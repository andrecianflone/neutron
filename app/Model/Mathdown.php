<?php

namespace Neutron\Model;

class Mathdown {

  /**
   * Scan text for Latex math and convert for use with Katex
   */
  public function parsemath($text) {
    $result = $this->add_tags($text);
    $result = $this->add_links($result);
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
    $replacement1 = '<span class="math inline">$1</span>';

    // match multiline $$math$$:
    // Starts/Ends with two '$'
    // Must have min 1 char in between
    $pattern2 = "/[\$]{2}([^\$]+)[\$]{2}/";
    $replacement2 = '<div><span class="math center">$1</span></div>';

    // match multiline with \beg \end equation:
    // Must have the 's' option to check new lines
    $pattern3 = '/\\\begin{equation}(.+)\\\end{equation}/s';
    $replacement3 = '<div><span class="math center">$1</span></div>';

    $result = preg_replace(
        array($pattern1, $pattern2, $pattern3),
        array($replacement1, $replacement2, $replacement3),
        $text
    );
    return $result;
  }

  /**
   * Add needed scripts/css to make the math work
   * Alternatively, you may want to use katex:
   * https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.6.0/katex.min.css
   * https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.6.0/katex.min.js
   */
  private function add_links($text) {
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
