<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
//ini_set('error_reporting = E_ALL | E_STRICT');

?>
<!DOCTYPE html>
<html lang="en">
<head>

  <title>Proximacent</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.6.0/katex.min.css">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.6.0/katex.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>

</head>
<body>

<p id="math">hello</p>
<script>

$(function() {
  console.log( "ready!" );
  var elem = document.createElement('p');
  katex.render("c = \\pm\\sqrt{a^2 + b^2}", math, {displayMode: true});
  loadKatex();
});


function loadKatex() {
  $(".math_inline").each(function() {
    var texTxt = $(this).text();
    el = $(this).get(0);
    if(el.tagName == "DIV"){
        addDisp = "\\displaystyle";
    } else {
        addDisp = "";
    }
    try {
        katex.render(addDisp+texTxt, el);
    }
    catch(err) {
        $(this).html("<span class='err'>"+err);
    }
  }); 
};

</script>

<p><span id="mykatex1">...</span></p>
<script>
katex.render("c = \\pm\\sqrt{a^2 + b^2}", mykatex1);
</script>

<?php

//phpinfo();

$text = "The char $\theta$ is inline math, so is $13$, but $5 is just money\n";
$text .=  "\n";
$text .= "$$ hello $$\n";
$text .= "\n";
$text .= "Using begin:\n";
$text .= "\begin{equation}\n";
$text .= "\hat{y}(x_i)=\theta + x_i\theta_2 \n";
$text .= "\end{equation}\n";
$text .= "\n";
$text .= "Or using double dollars\n";
$text .= "$$\n";
$text .= "\hat{y}(x_i)=\theta + x_i\theta_2 \n";
$text .= "$$\n";

echo("inline");
$pattern = "/(?<!\\$)[\$][^\$\n]+[\$](?!\\$)/";
print_regex_groups($pattern, $text);

echo("multiline $$math$$");
$pattern = "/[\$]{2}[^\$]+[\$]{2}/";
print_regex_groups($pattern, $text);

echo("multiline with equation");
$pattern = "/\\\begin{equation}.+\\\end{equation}/s";
print_regex_groups($pattern, $text);

// Function to print regex patterns
function print_regex_groups($pattern, $text) {
  preg_match_all($pattern, $text, $matches);
  pretty_print($matches);
}

function pretty_print($text_print) {
  echo("<pre>");
  print_r($text_print);
  echo("</pre>");
}

// match inline $math$
$pattern1 = "/(?<!\\$)[\$]([^\$\n]+)[\$](?!\\$)/";
$replacement1 = '<span class="math_inline">$1</span>';

// match multiline $$math$$:
$pattern2 = "/[\$]{2}([^\$]+)[\$]{2}/";
$replacement2 = '<span class="math_center">$1</span>';

// match multiline with \beg \end equation:
$pattern3 = '/\\\begin{equation}(.+)\\\end{equation}/s';

$result = preg_replace(
    array($pattern1, $pattern2, $pattern3),
    array($replacement1, $replacement2, $replacement2),
    $text
);

pretty_print($result);

?>
</body>
</html>
