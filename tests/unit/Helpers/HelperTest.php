<?php

namespace Quantum\Tests\Helpers;

use Quantum\Exceptions\StopExecutionException;
use Quantum\Libraries\Session\Session;
use Quantum\Exceptions\HookException;
use Quantum\Libraries\Cookie\Cookie;
use Quantum\Router\RouteController;
use Quantum\Libraries\Asset\Asset;
use Quantum\Libraries\Lang\Lang;
use Quantum\Libraries\Csrf\Csrf;
use Quantum\Factory\ViewFactory;
use Quantum\Tests\AppTestCase;
use Quantum\Router\Router;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Di\Di;

class HelperTest extends AppTestCase
{

    private $router;
    private $request;
    private $response;
    private $sessionData = [];

    public function setUp(): void
    {
        parent::setUp();

        Response::init();

        $this->request = new Request();

        $this->response = new Response();

        $this->router = new Router($this->request, $this->response);

        $this->session = Session::getInstance($this->sessionData);
    }

    public function testRandomNumber()
    {
        $this->assertIsInt(random_number());

        $this->assertIsInt(random_number(5));
    }

    public function testBaseUrl()
    {
        $this->request->create('GET', 'https://test.com');

        $this->assertEquals('https://test.com', base_url());
    }

    public function testCurrentUrl()
    {
        $this->request->create('GET', 'http://test.com/post/12');

        $this->assertEquals('http://test.com/post/12', current_url());

        $this->request->create('GET', 'http://test.com/user/12?firstname=John&lastname=Doe');

        $this->assertEquals('http://test.com/user/12?firstname=John&lastname=Doe', current_url());

        $this->request->create('GET', 'http://test.com:8080/?firstname=John&lastname=Doe');

        $this->assertEquals('http://test.com:8080/?firstname=John&lastname=Doe', current_url());
    }

    public function testRedirecting()
    {
        $this->assertFalse($this->response->hasHeader('Location'));

        try {
            redirect('/home');
        } catch (StopExecutionException $e) {

        }

        $this->assertTrue($this->response->hasHeader('Location'));

        $this->assertEquals('/home', $this->response->getHeader('Location'));
    }

    public function testRedirectWithOldData()
    {
        $this->request->create('POST', '/', ['firstname' => 'Josh', 'lastname' => 'Doe']);

        try {
            redirectWith('/signup', $this->request->all());
        } catch (StopExecutionException $e) {

        }

        $this->assertTrue($this->response->hasHeader('Location'));

        $this->assertEquals('/signup', $this->response->getHeader('Location'));

        $this->assertEquals('Josh', old('firstname'));

        $this->assertEquals('Doe', old('lastname'));
    }

    public function testSlugify()
    {
        $this->assertEquals('text-with-spaces', slugify('Text with spaces'));

        $this->assertEquals('ebay-com-itm-dual-arm-tv-trkparms-aid-3d111001-26brand-3dunbranded-trksid-p2380057', slugify('ebay.com/itm/DUAL-ARM-TV/?_trkparms=aid%3D111001%26brand%3DUnbranded&_trksid=p2380057'));
    }

    public function testAssetUrl()
    {
        config()->set('base_url', 'http://mydomain.com');

        $this->assertEquals('http://mydomain.com/assets/css/style.css', asset()->url('css/style.css'));

        $this->assertSame('http://mydomain.com/assets/js/script.js', asset()->url('js/script.js'));
    }

    public function testPublishedAssets()
    {
        config()->set('base_url', 'http://mydomain.com');

        asset()->register([
            new Asset(Asset::CSS, 'css/style.css'),
            new Asset(Asset::CSS, 'css/responsive.css')
        ]);

        asset()->register([
            new Asset(Asset::JS, 'js/bootstrap.js'),
            new Asset(Asset::JS, 'js/bootstrap-datepicker.min.js')
        ]);

        $expectedOutput = '<link rel="stylesheet" type="text/css" href="' . asset()->url('css/style.css') . '">' . PHP_EOL .
            '<link rel="stylesheet" type="text/css" href="' . asset()->url('css/responsive.css') . '">' . PHP_EOL .
            '<script src="' . asset()->url('js/bootstrap.js') . '"></script>' . PHP_EOL .
            '<script src="' . asset()->url('js/bootstrap-datepicker.min.js') . '"></script>' . PHP_EOL;

        ob_start();

        assets('css');
        assets('js');

        $this->assertStringContainsString($expectedOutput, ob_get_contents());

        ob_get_clean();
    }

    public function testMvcHelpers()
    {
        Router::setRoutes([
            [
                "route" => "api-signin",
                "method" => "POST",
                "controller" => "SomeController",
                "action" => "signin",
                "module" => "test",
                "middlewares" => ["guest", "anonymous"]
            ],
            [
                "route" => "api-user/[id=:num]",
                "method" => "GET",
                "controller" => "SomeController",
                "action" => "signout",
                "module" => "test",
                "middlewares" => ["user"]
            ]
        ]);

        $this->request->create('POST', 'http://testdomain.com/api-signin');

        $this->router->findRoute();

        $middlewares = current_middlewares();

        $this->assertIsArray($middlewares);

        $this->assertEquals('guest', $middlewares[0]);

        $this->assertEquals('anonymous', $middlewares[1]);

        $this->assertEquals('test', current_module());

        $this->assertEquals('SomeController', current_controller());

        $this->assertEquals('signin', current_action());

        $this->assertEquals('api-signin', current_route());

        $this->assertEmpty(route_params());

        $this->request->create('GET', 'http://testdomain.com/api-user/12');

        $this->router->resetRoutes();

        $this->router->findRoute();

        $this->assertNotEmpty(route_params());

        $this->assertEquals(12, route_param('id'));

        $this->assertEquals('(\/)?api-user(\/)(?<id>[0-9]+)', route_pattern());

        $this->assertEquals('GET', route_method());

        $this->assertEquals('api-user/12', route_uri());
    }

    public function testFindRouteByName()
    {
        $this->assertNull(find_route_by_name('user', 'test'));

        Router::setRoutes([
            [
                "route" => "api-user/[id=:num]",
                "method" => "GET",
                "controller" => "SomeController",
                "action" => "signout",
                "module" => "test",
                "middlewares" => ["user"],
                "name" => "user"
            ]
        ]);

        $this->assertNotNull(find_route_by_name('user', 'test'));

        $this->assertIsArray(find_route_by_name('user', 'test'));
    }

    public function testCheckRouteGroupExists()
    {
        $this->assertFalse(route_group_exists('guest', 'test'));

        Router::setRoutes([
            [
                "route" => "api-user/[id=:num]",
                "method" => "GET",
                "controller" => "SomeController",
                "action" => "signout",
                "module" => "test",
                "middlewares" => ["user"],
                'group' => 'guest',
                "name" => "user"
            ]
        ]);

        $this->assertTrue(route_group_exists('guest', 'test'));
    }

    public function testView()
    {
        $viewFactory = Di::get(ViewFactory::class);

        $viewFactory->setLayout('layout');

        $viewFactory->render('index');

        $this->assertEquals('<p>Hello World, this is rendered view</p>', view());

        $viewFactory->setLayout(null);
    }

    public function testPartial()
    {
        $this->assertEquals('<p>Hello World, this is rendered partial view</p>', partial('partial'));

        $this->assertEquals('<p>Hello John, this is rendered partial view</p>', partial('partial', ['name' => 'John']));
    }

    public function testConfigHelper()
    {
        $this->assertFalse(config()->has('not-exists'));

        $this->assertEquals('Not found', config()->get('not-exists', 'Not found'));

        $this->assertEquals(config()->get('test', 'Testing'), 'Testing');

        $this->assertNull(config()->get('new-key'));

        config()->set('new-key', 'New value');

        $this->assertTrue(config()->has('new-key'));

        $this->assertEquals('New value', config()->get('new-key'));

        config()->delete('new-key');

        $this->assertFalse(config()->has('new-key'));
    }

    public function testGetEnvValue()
    {
        $this->assertNull(env('NEW_ENV_KEY'));

        putenv('NEW_ENV_KEY=New value');

        $this->assertEquals('New value', env('NEW_ENV_KEY'));
    }

    public function testSessionHelper()
    {
        $this->assertInstanceOf(Session::class, session());

        $this->assertFalse(session()->has('test'));

        session()->set('test', 'Testing');

        $this->assertTrue(session()->has('test'));

        $this->assertEquals('Testing', session()->get('test'));
    }

    public function testCookieHelper()
    {
        $this->assertInstanceOf(Cookie::class, cookie());

        $this->assertFalse(cookie()->has('test'));

        cookie()->set('test', 'Testing');

        $this->assertTrue(cookie()->has('test'));

        $this->assertEquals('Testing', cookie()->get('test'));
    }

    public function testValidBase64()
    {
        $validBase64String = base64_encode('test');

        $invalidBase64String = 'abc123';

        $this->assertTrue(valid_base64($validBase64String));

        $this->assertFalse(valid_base64($invalidBase64String));
    }

    public function testCurrentLang()
    {
        $this->assertEquals('en', current_lang());

        Lang::getInstance()->setLang('am');

        $this->assertEquals('am', current_lang());
    }

    public function testGetTheTranslation()
    {
        RouteController::setCurrentRoute([
            "route" => "api-signin",
            "method" => "POST",
            "controller" => "SomeController",
            "action" => "signin",
            "module" => "test",
        ]);

        $this->assertEquals('Learn more', t('custom.learn_more'));

        $this->assertEquals('Information about the new feature', t('custom.info', 'new'));

        _t('custom.learn_more');

        $this->expectOutputString('Learn more');
    }

    public function testCsrfToken()
    {
        $this->request->create('PUT', '/update', ['title' => 'Task Title', 'csrf-token' => csrf_token()]);

        $this->assertTrue(csrf()->checkToken($this->request));
    }

    public function testMessageHelper()
    {
        $this->assertEquals('Hello John', _message('Hello {%1}', 'John'));

        $this->assertEquals('Hello John, greetings from Jenny', _message('Hello {%1}, greetings from {%2}', ['John', 'Jenny']));
    }

    public function testHookOnAndFire()
    {
        hook()->on('SAVE', function () {
            echo 'Data successfully saved';
        });

        hook()->fire('SAVE');

        $this->expectOutputString('Data successfully saved');
    }

    public function testHookFireWithArgument()
    {
        hook()->on('SAVE', function ($data) {
            echo 'The file ' . $data['filename'] . ' was successfully saved';
        });

        hook()->fire('SAVE', ['filename' => 'doc.pdf']);

        $this->expectOutputString('The file doc.pdf was successfully saved');
    }

    public function testHookMultipleListeners()
    {
        hook()->on('SAVE', function ($data) {
            echo 'The file ' . $data['filename'] . ' was successfully saved' . PHP_EOL;
        });

        hook()->on('SAVE', function () {
            echo 'The email was successfully sent';
        });

        hook()->fire('SAVE', ['filename' => 'doc.pdf']);

        $this->expectOutputString('The file doc.pdf was successfully saved' . PHP_EOL . 'The email was successfully sent');
    }

    public function testUnregisteredHookAtOn()
    {
        $this->expectException(HookException::class);

        $this->expectExceptionMessage('unregistered_hook_name');

        hook()->on('SOME_EVENT', function () {
            echo 'Do someting';
        });
    }

    public function testUnregisteredHookAtFire()
    {
        $this->expectException(HookException::class);

        $this->expectExceptionMessage('unregistered_hook_name');

        hook()->fire('SOME_EVENT');
    }

}
