<?php

namespace MiniPhpRest\Tests\core\utils\lang;

use MiniPhpRest\core\utils\lang\ArrayUtils;
use NasOneMovies\app\utils\AppUtils;
use PHPUnit\Framework\TestCase;

class ArrayUtilsTest extends TestCase
{


    public function testOneDimensionArray()
    {

        $testArray = [
            "a" => 1,
            "b" => 2,
            "c" => 3
        ];

        $resultArray = ArrayUtils::removeKeys($testArray, ['b']);

        self::assertEquals([
            "a" => 1,
            "c" => 3
        ], $resultArray);

        $resultArray = ArrayUtils::removeKeys($testArray, ['/b']);

        self::assertEquals([
            "a" => 1,
            "c" => 3
        ], $resultArray);

    }

    public function testMixedDimensionArray()
    {
        $testArray = [
            "a" => 1,
            "b" => 2,
            "c" => [3, 4],
            "d" => 4

        ];
        $resultArray = ArrayUtils::removeKeys($testArray, ['d']);

        self::assertEquals([
            "a" => 1,
            "b" => 2,
            "c" => [3, 4]
        ], $resultArray);
    }


    public function testRemoveByPath() {

        $array = [
            'a' => 1,
            'b' => 2,
            'c' => [
                'cA' => [
                    'cAa' => 3,
                ],
                'cB' => 4
            ],
            'd' => 5
        ];

        $resultArray = ArrayUtils::removeKeys($array, ['/c/cA/cAa']);

        self::assertEquals([
            'a' => 1,
            'b' => 2,
            'c' => [
                'cA' => [

                ],
                'cB' => 4
            ],
            'd' => 5
        ], $resultArray);
    }


    public function testAbsolutePath()
    {
        $data = ['a' => ['b' => 1], 'b' => 2];
        $res = ArrayUtils::removeKeys($data, ['/b']);
        $this->assertArrayNotHasKey('b', $res); // racine b supprimée
    }

    public function testWildcardStart()
    {
        $data = ['xid' => 1, 'y' => ['subid' => 2, 'keep' => 3]];
        $res = ArrayUtils::removeKeys($data, ['*id']);
        $this->assertArrayNotHasKey('xid', $res);
        $this->assertArrayNotHasKey('subid', $res['y']);
    }

    public function testWildcardEnd()
    {
        $data = ['user1' => 1, 'user2' => 2, 'other' => 3];
        $res = ArrayUtils::removeKeys($data, ['user*']);
        $this->assertArrayNotHasKey('user1', $res);
        $this->assertArrayNotHasKey('user2', $res);
    }

    public function testTest() {
        $strArray =  <<<'JSON'
[{"idFilm":1,"filmUid":"a9cb79b31bcfe92680830c36fc2fc92a6c7d2dbebdd4cb5a753411dd41a36078","title":"12 Hommes en col\u00e8re","year":1957,"duration":96,"description":null,"ficFilmIdFilm":1,"dateAdded":[],"isEnabled":true,"genres":[],"ficFilm":{"idFilm":1,"fileName":"12_Hommes_en_col\u00e8re_(1957)_MULTI_AC3_1080p_mHD_x264-SANTACRUZ-Wawacity.makeup.mkv","directoryName":"M:\\Vid\u00e9os\\Films\\AClasser","length":3639960747,"creationTime":[],"filmUid":"a9cb79b31bcfe92680830c36fc2fc92a6c7d2dbebdd4cb5a753411dd41a36078","batchUniqueId":1,"duration":96}},{"idFilm":2,"filmUid":"5e60bd1cbc782f89b07411aa319348f594f1ca8c1fb801a4af642f48facdf067","title":"American Reunion","year":null,"duration":114,"description":null,"ficFilmIdFilm":2,"dateAdded":[],"isEnabled":true,"genres":[],"ficFilm":{"idFilm":2,"fileName":"American.Reunion.avi","directoryName":"M:\\Vid\u00e9os\\Films\\AClasser","length":732600321,"creationTime":[],"filmUid":"5e60bd1cbc782f89b07411aa319348f594f1ca8c1fb801a4af642f48facdf067","batchUniqueId":1,"duration":114}}]
JSON;
        $array = json_decode($strArray, true);

        $res = ArrayUtils::removeKeys($array,
            ['*/ficFilmIdFilm', '*/ficFilm/filmUuid', '*/ficFilm/batchUniqueId']
        );

        $this->assertArrayHasKey('ficFilmIdFilm', $res);

    }

}
