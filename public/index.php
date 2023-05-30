<?php

use DI\Container;
use Slim\Views\Twig;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);

$container->set('db', function () {
    $db = new \PDO("sqlite:" . __DIR__ . '/../database/database.sqlite');
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(\PDO::ATTR_TIMEOUT, 5000);
    $db->exec("PRAGMA journal_mode = WAL");
    return $db;
});

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$twig = Twig::create(__DIR__ . '/../twig', ['cache' => false]);

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->get('/products', function (Request $request, Response $response, $args) {
   $db = $this->get('db');
   $sth = $db->prepare("SELECT * FROM products");
   $sth->execute();
   $products = $sth->fetchAll(\PDO::FETCH_OBJ);

   $view = Twig::fromRequest($request);

   return $view->render($response, 'products.html', [
       'products' => $products
   ]);
});

$app->post('/add-cart', function (Request $request, Response $response, $args) {
   if(isset($_POST['product_id'])) {
       $product_id = $_POST['product_id'];
       $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : array();

       if(isset($cart[$product_id])) {
           $cart[$product_id]['quantity']++;
       } else {
           $cart[$product_id] = array('product_id' => $product_id, 'quantity' => 1);
       }
   
       setcookie('cart', json_encode($cart), time() + 10000, '/');
       return $response->withStatus(201);
   }
   return $response->withStatus(400);
});

$app->post('/remove-cart', function (Request $request, Response $response, $args) {
    if(isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : array();
    
        if(isset($cart[$product_id])) {
            if($cart[$product_id]['quantity'] > 1) {
                $cart[$product_id]['quantity']--;
            } else {
                unset($cart[$product_id]);
            }
        }
    
        setcookie('cart', json_encode($cart), time() + 10000, '/');
         
        header('Location: /cart');
        exit();
    }
    return $response->withStatus(204);
});
 

$app->get('/cart', function (Request $request, Response $response, $args) {
   $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : array();

   $cart_products = array();
   $db = $this->get('db');
   
   foreach ($cart as $product_id => $cart_item) {
       $sth = $db->prepare("SELECT * FROM products WHERE id = ?");
       $sth->execute([$product_id]);
       $product = $sth->fetch(\PDO::FETCH_OBJ);
       if ($product) {
           $cart_products[] = array(
               'product' => $product,
               'id' => $product_id,
               'name' => $product->name,
               'price' => $product->price,
               'image' => $product->image,
               'quantity' => $cart_item['quantity']
           );
       }
   }

   $view = Twig::fromRequest($request);
   return $view->render($response, 'cart.html', [
       'cart_products' => $cart_products
   ]);
});


$methodOverrideMiddleware = new MethodOverrideMiddleware();
$app->add($methodOverrideMiddleware);

$app->add(TwigMiddleware::create($app, $twig));
$app->run();
