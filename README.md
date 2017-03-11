# Neutron
Neutron is a minimalist CMS for the lover of Markdown, Latex, and Vim. The targeted user is the programmer who likes to write in Markdown, dislikes the anarchy of WYSIWYGs, and wishes their website backend did not have endless options and clicks to go through. Written in: PHP, JavaScript, Twig

While developed as a CMS, Neutron can be quite useful for other purposes, such as for efficient notetaking.

The goal is to keep the site as small as possible while meeting a few non-negotiable principles. These are:

1. Simplicity, yet modular
2. Secure
3. Efficient content editing with built-in support for Latex math
4. Outsource non-fundamentals

## Architecture
Neutron is built on top of the [Slim](https://www.slimframework.com/) microframework, and follows a web MVC pattern. Installation is easy (see below)

The root directory `neutron` is the entire website including Slim and other dependencies. `neutron/app` is the website. `neutron/app/public` contains everything directly accessible through the web. Web traffic will only have access to this directory, assuming you've setup `.htaccess` properly. This directory includes the `index.php` file, your css and scipts.

### MVC
Neutron uses the Slim framework as a "controller". Think of Slim as a library. The entire controller is written in a single PHP file: `index.php`.

You will find the models in the app/Model folder. Models must be uppercase.

Views are rendered with Twig. Twig is a template engine for PHP

If you want to add or change a namespace, you must do so in `neutron/composer.json`. Then run the following in the source directory:
``` bash
php composer.phar dump-autoload
```

## Content Manager
The content manager is the pride and joy of Neutron. What do we mean by efficient? We mean efficient from a coder's perspective. We want minimal "euclidean-distance" from idea to result. Quick writing with Markdown. There are no redirects, everything Ajax.

You want a new page? Then write one and click "create". You then see a simple confirmation "new page created". There is no popup, no redirects, everything is done in the background asynchronously. As you write, occasionally click "update" to save your work, and a message will confirm your work is saved. Want to delete a page? Select the page and click "delete". Simple. Everything is done on that page.

### Editor
HTML is syntax chaos. So WYSIWYGS became popular. WYSIWYGS are nice since they abstract away code, and let you write exactly what will be published. But they never act the way you want them too. And more importantly, they add HTML code you don't want. When you want to inspect what the WYSIWYG is actually generating, you look at the HTML. You're back at problem #1, syntax chaos.

The solution is a markup language. Neutron adopts Markdown as the ONLY way to write content. Markdown in the editor, and Markdown in the database. When content is requested by the controller (`index.php`), a request is sent to model `Model/Article.php`. `Article.php` will automatically parse Markdown to HTML via the [Parsedown](https://github.com/erusev/parsedown) library. 

Neutron also recognizes your need to write math Latex style directly in Markdown. But Markdown doesn't support Latex you say! Fear not. Neutron recognizes Latex math commands directly in your Markdown. Behind the scenes Neutron injects HTML tags for math, which are then picked by Javascript when rendering the math. We named this tool "Mathdown", see `Model/Mathdown.php`. To render math, you need to check "has math" above the code editor. This loads the appropriate javascript and css.

You can write math with the following patterns:
``` markdown
The equation $h = W_hx + b_h$ is written inline and renders in your text.

The following equation will render centered on your page:
$$
h = W_hx + b_h
$$

The following equation is also centered:
\begin{equation}
h = W_hx + b_h
\end{equation}
```

### Preview
So you liked WYSIWYGS because you can actually see the end result before publishing? Then hit the preview button and look at the results in that box. BOOM! No silly popups, just a preview next to your code. The preview pane is written to be dynamic. Depending on the width of your window, it will appear to the right side or under the editor. You can also click "split" or "stack" to force a specifc layout.

### Outsourcing
So you build your website to fit your needs. But you just want to write content, you don't want to focus on CSS and all that.

#### Bootstrap
The website adopts Bootstrap as a CSS framework. This makes the website adaptive (you never need to worry about
* Ace
* Prism

## Setup
Setup is easy, run the following in a shell
``` bash
bash setup.bash
```
The script will download and run [composer](https://getcomposer.org/). Composer is a dependency manager for PHP. It will read the required dependencies in the `composer.json` file and download and setup everything necessary for the website.

Setup will create a template settings file. Update the `site_config.ini` file with your own database settings:
``` bash
; Database configuration
[db]
host   = "your_host_name"
user   = "database_user_name"
pass   = "user_password"
dbname = "database_name"
```

A sample `.htaccess` file is also created with some necessary and recommended settings. Edit the file if needed and move it to the correct location on your server.
