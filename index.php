<?php 
    // Composer Imports
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/functions.php';

    // Environment Variables
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->overload();

    // Logger
    $log = new Monolog\Logger('index');
    $log->pushHandler(new Monolog\Handler\StreamHandler('app.log', Monolog\Logger::WARNING));
    $log->addWarning('Foo');

    $state = 'applicationState';

    // Router
    $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) use ($state) {

        // Home page
        $r->get('/', function() {
            echo '<p>Home Page (/)</p>';

            $is_authenticated = isAuthenticated() ? 'true' : 'false';
            echo "<p>Is Authenticated: $is_authenticated</p>";
        });

        // Login
        $r->get('/login', function() use ($state) {
            $query = http_build_query([
                'client_id' => getenv('CLIENT_ID'),
                'response_type' => 'code',
                'response_mode' => 'query',
                'scope' => 'openid profile',
                'redirect_uri' => 'http://localhost:8080/authorization-code/callback',
                'state' => $state,
                'nonce' => random_bytes(32)
            ]);
            header('Location: ' . getenv("ISSUER").'/v1/authorize?'.$query);
        });

        // Logout
        $r->post('/logout', function() {
            setcookie("access_token",NULL,-1,"/",false);
            header('Location: /');
        });

        // Handle authentication callback
        $r->get('/authorization-code/callback', function() use ($state) {
            if(array_key_exists('state', $_REQUEST) && $_REQUEST['state'] !== $state) {
                throw new \Exception('State does not match.');
            }
            if(array_key_exists('code', $_REQUEST)) {
                $exchange = exchangeCode($_REQUEST['code']);
                if(!isset($exchange->access_token)) {
                    die('Could not exchange code for an access token');
                }
                if(verifyJwt($exchange->access_token) == false) {
                    die('Verification of JWT failed');
                }
                setcookie("access_token","$exchange->access_token",time()+$exchange->expires_in,"/",false);
                header('Location: / ');
            }
            die('An error during login has occurred');
            
        });
    });

    function exchangeCode($code) {
        $authHeaderSecret = base64_encode( getenv('CLIENT_ID') . ':' . getenv('CLIENT_SECRET') );
        $query = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'http://localhost:8080/authorization-code/callback'
        ]);
        $headers = [
            'Authorization: Basic ' . $authHeaderSecret,
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'Connection: close',
            'Content-Length: 0'
        ];
        $url = getenv("ISSUER").'/v1/token?' . $query;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(curl_error($ch)) {
            $httpcode = 500;
        }
        curl_close($ch);
        return json_decode($output);
    }


    // Routing and dispatcher configuration.
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    // Strip query string (?foo=bar) and decode URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            die('Not Found');
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            die('Not Allowed');
            break;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            print $handler($vars);
            break;
    }

    if (!isAuthenticated()) {
        echo '<form method="get" action="login">
                <button id="login-button" type="submit">Login</button>
            </form>';
    } else {
        echo '<form method="post" action="logout">
                <button id="logout-button" type="submit">Logout</button>
            </form>';
    }

?>

