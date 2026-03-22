<?php

namespace MiniPhpRest\Tests\FullRun;

use MiniPhpRest\core\MiniPhpRestConfig;
use MiniPhpRest\core\ServerParamObject;
use MiniPhpRest\Runner;
use MiniPhpRest\Tests\Support\Controllers\TestMathController;
use PHPUnit\Framework\TestCase;

class NominalRunTest extends TestCase
{

    private MiniPhpRestConfig $config;
    private array $routes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routes = [
            'GET' => [
                // Movies
                '/api/v1/movies/getAll' => [TestMathController::class, 'simpleGet'],
            ]
        ];

        define("MiniPhpRest\Tests\FullRun\SERVER_ROOT", __DIR__);


        $this->config = new \MiniPhpRest\core\MiniPhpRestConfig();
        $this->config->setIsDebug(true)
            ->setServerRootPath(SERVER_ROOT)
            ->setUriSliceOffset(1)
            ->setAppClassFolders([
                'NasOneMovies' => 'php'
            ])->setServerParamObject(new ServerParamObject(
                'GET', '/api/v1/movies/getAll'
            ));
        //$config->setUriSliceOffset($siteConf->getValue('miniPhpFw', 'uriSliceOffset', 2));


    }


    /**
     * @runInSeparateProcess
     *
     *
     */
    public function testNominalRun(): void
    {


        ob_start();
        $response = Runner::followRoute($this->routes, $this->config);

        $retJson = ob_get_contents();
        ob_end_clean();

        $retArray = json_decode($retJson, true);
        echo $retJson;

        /*
        $this->assertTrue(isset($retArray['result']), "Le retour ne contient pas 'result'");
        $this->assertTrue($retArray['result'], "Le résultat n'est pas OK(true)");
        */

        $this->assertTrue(isset($retArray['message']), "Le retour ne contient pas 'message'");
        $this->assertEquals("Hello, world!", $retArray['message'], "Le message de retour n'est pas correct");

        $this->assertEquals(200, $response->getStatusCode(), "Le code de statut HTTP n'est pas 200");


    }

}
