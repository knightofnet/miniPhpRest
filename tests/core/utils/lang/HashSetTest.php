<?php

namespace MiniPhpRest\Tests\core\utils\lang;

use MiniPhpRest\core\utils\lang\HashSet;
use PHPUnit\Framework\TestCase;

class HashSetTest extends TestCase
{
    public function testAddContainsAndCount(): void
    {
        $set = new HashSet();

        $this->assertTrue($set->isEmpty());
        $this->assertFalse($set->contains('alpha'));

        $set->add('alpha');
        $set->push('beta');

        $this->assertTrue($set->contains('alpha'));
        $this->assertTrue($set->contains('beta'));
        $this->assertFalse($set->contains('gamma'));
        $this->assertSame(2, $set->count());
        $this->assertFalse($set->isEmpty());
    }

    public function testDuplicateValuesDoNotIncreaseCount(): void
    {
        $set = new HashSet();

        $set->add('alpha');
        $set->add('alpha');
        $set->push('alpha');

        $this->assertSame(1, $set->count());
        $this->assertEquals(['alpha'], $set->getEntriesAsArray());
    }

    public function testRemoveAndClear(): void
    {
        $set = new HashSet();

        $set->add('alpha');
        $set->add('beta');
        $set->remove('alpha');

        $this->assertFalse($set->contains('alpha'));
        $this->assertTrue($set->contains('beta'));
        $this->assertSame(1, $set->count());

        $set->clear();

        $this->assertTrue($set->isEmpty());
        $this->assertSame(0, $set->count());
        $this->assertSame([], $set->getEntriesAsArray());
    }

    public function testWorksWithNonStringValues(): void
    {
        $set = new HashSet();
        $value = ['id' => 1, 'name' => 'John'];

        $set->add($value);

        $this->assertTrue($set->contains(['id' => 1, 'name' => 'John']));
        $this->assertFalse($set->contains(['id' => 2, 'name' => 'John']));
        $this->assertSame([$value], $set->getEntriesAsArray());
    }
}

