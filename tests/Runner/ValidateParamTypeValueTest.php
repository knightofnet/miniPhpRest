<?php

namespace MiniPhpRest\Tests\Runner;

use MiniPhpRest\Runner;
use PHPUnit\Framework\TestCase;

class ValidateParamTypeValueTest extends TestCase
{

    public function test_int_validation_Ok(): void
    {
        $argType = 'int';

        $argStrValue = '0';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

        $argStrValue = '1';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

        $argStrValue = '-2';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

    }

    public function test_int_validation_fail(): void
    {

        $argType = 'int';

        $argStrValue = '0.0';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = 'str';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = '1a';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = 'a1';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = ' 1';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = 'true';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);
    }

    public function test_string_validation_Ok(): void
    {
        $argType = 'string';

        $argStrValue = '0';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

        $argStrValue = 'test';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

        $argStrValue = '';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

    }

    public function test_string_validation_fail(): void
    {
        $argType = 'string';

        $argStrValue = null;
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);
    }

    public function test_bool_validation_Ok(): void
    {
        $argType = 'bool';

        $argStrValue = '0';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

        $argStrValue = '1';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

        $argStrValue = 'true';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

        $argStrValue = 'false';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);
    }

    public function test_bool_validation_fail(): void
    {
        $argType = 'bool';

        $argStrValue = '0.0';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = 'str';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = '1true';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = '1false';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = ' true';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = ' false';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);
    }

    public function test_float_validation_Ok(): void
    {
        $argType = 'float';

        $argStrValue = '0';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

        $argStrValue = '0.0';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

        $argStrValue = '1.0';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);

        $argStrValue = '-2.0225';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertTrue($validationResult);
    }

    public function test_float_validation_fail(): void
    {
        $argType = 'float';

        $argStrValue = 'str';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = '1a';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = 'a1';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = ' 1';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);

        $argStrValue = 'true';
        $validationResult = Runner::validateParamTypeValue($argType, $argStrValue);
        $this->assertFalse($validationResult);
    }


}
