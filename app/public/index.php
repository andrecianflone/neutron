<?php
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);

define("APPDIR", dirname(dirname(__FILE__)));
define("BASEDIR", dirname(dirname(dirname(__FILE__))));

// Time zone
date_default_timezone_set('America/New_York');

// INCLUDE SOME SLIM CLASSES
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require BASEDIR . '/vendor/autoload.php';

// LOAD APP CLASSES
//spl_autoload_register(function ($classname) {
    //require ("../classes/" . $classname . ".php");
//});

// ============================================================================
// Configurations
// ============================================================================
// Config object
$config['displayErrorDetails'] = true; // on for dev mode

// Configure the database
// Get settings from site_config.ini with sections
$site_ini = parse_ini_file('../site_config.ini', true);
$config['db']['host']   = $site_ini['db']['host'];
$config['db']['user']   = $site_ini['db']['user'];
$config['db']['pass']   = $site_ini['db']['pass'];
$config['db']['dbname'] = $site_ini['db']['dbname'];

// New Slim app object with the configs
$app = new \Slim\App(["settings" => $config]);

// ============================================================================
// Dependencies: register components as services
// ============================================================================
// Dependency object, available throughout app by: $this
$container = $app->getContainer();

// Logging tool. All errors logged to logs/app.log
$container['logger'] = function($c) {
  $logger = new \Monolog\Logger('my_logger');
  $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
  $logger->pushHandler($file_handler);
  return $logger;
}; // Sample usage throughout app: $this->logger->addInfo("A message")

// Database Connection object, as PDO object
$container['db'] = function ($c) {
  $db = $c['settings']['db'];
  $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],
    $db['user'], $db['pass']);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
  return $pdo;
}; // Access this object in the app by: $this->db

// Configure TWIG
// Register component on container
$container['view'] = function ($container) {
  $view = new \Slim\Views\Twig('../views', [
    'cache' => false,
    'debug' => true
    //'cache' => 'path/to/cache'
  ]);
  $view->addExtension(new \Slim\Views\TwigExtension(
    $container['router'],
    $container['request']->getUri()
  ));
  return $view;
};
// Debug Twig, with below you can use dump() function in twig
//$twig = $container['view']->getEnvironment();
//$twig->addExtension(new Twig_Extension_Debug());

// Add Parsedown to app
$container['parsedown'] = function ($container) {
  $parse = new \Parsedown();
  return $parse;
};

// ============================================================================
// Models
// ============================================================================
$container['article'] = function ($container) {
  // new article model, pass app's db object
  $db = $container->get('db');
  $parse = $container->get('parsedown');
  $model = new Neutrino\Model\Article($db, $parse);
  return $model;
};

$container['session'] = function ($container) {
  $model = new Neutrino\Model\Session();
  return $model;
};

$container['login'] = function ($container) {
  // new Login , pass app's db object and session manager
  $model = new Neutrino\Model\Login($container->get('db'), $container->get('session'));
  return $model;
};
// ============================================================================
// Routes (Controllers)
// ============================================================================
/**
 * All routes callbacks accept 3 params:
 *  - Requests: this contains all the information about the incoming request, headers, variables, etc.
 *  - Response: we can add output and headers to this and, once complete, it will be turned into the HTTP response that the client receives
 *  - Arguments: the named placeholders from the URL (more on those in just a moment), this is optional and is usually omitted if there aren’t any
 */

/**
 * Twig function to check if user is logged in or not.
 * This function is available in all templates.
 */
$twig_logged = new Twig_SimpleFunction('isAuthed', function() use($container) {
  $logged = $container['login']->isAuthed();
  return $logged;
});
// Add the function to Twig via app-level Slim middleware
$app->add(function ($request, $response, $next) use($app, $twig_logged) {
  // Add login function to Twig
  $twig = $this->view->getEnvironment();
  $twig->addFunction($twig_logged);
  $response = $next($request, $response);
  return $response;
});

// Empty requests simply go to homepage
$app->get('/', function (Request $request, Response $response) {

  $title = "";
  $blurb = "";
  $articles = $this->article->getAllArticles(200);

  $newArgs = ["title" => $title, "blurb" => $blurb, "articles" => $articles];
  $response = $this->view->render($response, "index.twig", $newArgs);
  return $response;
});

// Load single article
$app->get('/article/{url}', function ($request, $response, $args) {
  $article = $this->article->getArticleByUrl($args['url'])[0];
  $rendered_content = $this->parsedown->text($article->body);
  return $this->view->render($response, 'article.twig', [
    'title' => $article->title,
    'blurb' => $article->blurb,
    'content' => $rendered_content
  ]);
});

/**
 * Handle login
 */
$app->group('/login', function() use ($app){

  // Send to login page
  $app->get('/', function ($request, $response, $args) {
    return $this->view->render($response, 'login.twig', [
      'title' => 'Login'
    ]);
  });

  // Handle login form
  $app->post('/login', function ($request, $response, $args) {
    $res = $this->login->attempt_login($_POST['username'], $_POST['password']);

    // Redirect according to success of login
    if ($res) {
      return $response->withRedirect('/publish/');
    } else {
      return $this->view->render($response, 'login.twig', [
        'title' => 'Login',
        'msg' => 'Could not login'
      ]);
    }
  });

  // Send to login page
  $app->get('/logout', function ($request, $response, $args) {
    $this->login->logout();
    return $this->view->render($response, 'login.twig', [
      'title' => 'Login',
      'msg' => 'Successfully logged out'
    ]);
  });

});


/**
 * Confirm login middleware.
 * Can be added to any group or route requiring login to view content
 */
$isAuthed = function ($request, $response, $next) use ($container) {
  $logged = $container['login']->isAuthed();
  if (!$logged) {
      return $container['view']->render($response, 'login.twig', [
        'title' => 'Login',
        'msg' => 'You need to log in'
      ]);
  } else {
    $response = $next($request, $response);
  }
  return $response;
};

/**
 * Handle article publishing
 */
//TODO articles should have an index to manage order on homepage
$app->group('/publish', function() use ($app){

  // Load publish page
  $app->get('/', function ($request, $response, $args) {
    $currentPages = $this->article->getAllArticles();
    return $this->view->render($response, 'publish.twig', [
      'currentPages' => $currentPages
    ]);
  });

  // Handle new articles, assumes request via ajax. Returns simple message
  $app->post('/new', function ($request, $response, $args) {
    $res = $this->article->addNewArticle(
      $_POST['title'], $_POST['url'], $_POST['blurb'],$_POST['body']);
    //TODO add "published", "creation date" to db
    //return $res;
    if ($res === TRUE) {
      return " Article added: " . $_POST['title'];
    } else {
      return "Error: " . $res;
    }
  });

  // Return json data for page by using its id
  $app->get('/getpage/{id}', function ($request, $response, $args) {
    $article = $this->article->getArticleById($args['id']);
    $newResponse = $response->withJson($article[0]); // return only one json
    return $newResponse;
  });

  // Return json data for page with url name
  $app->get('/delete/{id}', function ($request, $response, $args) {
    $res = $this->article->deleteArticle($args['id']);
    if ($res === TRUE) {
      return " Article deleted: ";
    } else {
      return "Error: " . $res;
    }
  });

  // Handle article update
  $app->post('/update', function ($request, $response, $args) {
    $res = $this->article->updateArticle(
            $_POST['article_sel'], $_POST['title'], $_POST['url'],
            $_POST['blurb'],$_POST['body']);
    //TODO add "published" and "update date" to db
    //return $res;
    if ($res === TRUE) {
      return " Article updated at " . date('h:i:sa');
    } else {
      return "Error: " . $res;
    }
  });

})->add($isAuthed);

// Router for hello/name pattern. "Get" for get requests
$app->get('/hello/{name}', function (Request $request, Response $response, $arg) {
  $name = $arg['name']; // Get name from arg
  $name = $request->getAttribute('name'); // Same as above, alternative
  $response->getBody()->write("Hello, $name"); // View: just print

  return $response;
});

// Router to publish data
$app->post('/publish/sample', function(Request $request, Response $response) {
  $data = $request->getParsedBody(); // parse form in body of request
  // getParsedBody can also parse JSON, given Content-Type header is set properly
  $ticket_data = [];
  // string filter strips string of tags:
  // http://php.net/manual/en/filter.filters.sanitize.php
  $ticket_data['title'] = filter_var($data['title'], FILTER_SANITIZE_STRING);
  $ticket_data['description'] = filter_var($data['description'], FILTER_SANITIZE_STRING);

});

// ============================================================================
// Start app
// ============================================================================
$app->run();
