<?php

class ArrayIndexedArrayTest extends PHPUnit_Framework_TestCase
{
    public function testOffsetSetOverrideExistingValue() {
        $aia = new ArrayIndexedArray();
        $aia[[1]] = 'test1';
        $this->assertEquals('test1', $aia[[1]]);
        $aia[[1]] = 'test2';
        $this->assertEquals('test2', $aia[[1]]);
    }

    public function testOffsetGetReturnsReference() {
        $aia = new ArrayIndexedArray();
        $aia[[1]] = 100000;
        $aia[[1]] += 500;
        $this->assertEquals(100500, $aia[[1]]);
    }

    public function testCountOfUniqueKeys() {
        $aia = new ArrayIndexedArray();
        $aia[[1]] = 'test';
        $aia[[2]] = 'test';
        $aia[[3]] = 'test';
        $aia[[4]] = 'test';

        $this->assertEquals(4, count($aia));
    }

    public function testCountIfKeysDuplication() {
        $aia = new ArrayIndexedArray();
        $aia[[1]] = 'test1';
        $aia[[1]] = 'test2';

        $this->assertEquals(1, count($aia));
    }

    public function testSerialization() {
        $data = [
            [
                'key' => ['keyA' => 1, 'keyB' => '2'],
                'value' => 100500,
            ],
            [
                'key' => ['keyA' => 1, 'keyB' => 2],
                'value' => ['foo', ['bar', 300]],
            ],
            [
                'key' => ['keyA' => 3, 'keyB' => 2],
                'value' => ['baz', new stdClass()],
            ],
        ];

        $aia = new ArrayIndexedArray();

        foreach ($data as $datum) {
            $aia[$datum['key']] = $datum['value'];
        }

        $serialized = serialize($aia);

        return [$serialized, $data];
    }

    /**
     * @param array $input
     * @depends testSerialization
     */
    public function testDeserialization($input) {
        list($serialized, $data) = $input;

        /** @var ArrayIndexedArray $aia */
        $aia = unserialize($serialized);

        $this->assertEquals(
            count($data),
            count($aia),
            'Size of the container should match amount of $data'
        );

        foreach ($aia as $key) {
            $value = $aia->getInfo();

            foreach ($data as $datum) {
                if ($datum['key'] === $key && $datum['value'] == $value) {
                    continue(2);
                }
            }

            $this->fail('Container contains key/value which not exists in $data');
        }
    }

    public function testFindShouldMatchKeysOfKeyArraysStrictly() {
        $aia = new ArrayIndexedArray();
        $aia[['0' => 1, 'bar' => '2']] = 'test1';
        $aia[[''  => 1, 'bar' => '2']] = 'test2';

        $this->assertEquals(
            1,
            count($aia->find(['0' => 1])),
            'Method find() should match keys of key-arrays strictly'
        );
        $this->assertEquals(
            1,
            count($aia->find(['' => 1])),
            'Method find() should match keys of key-arrays strictly'
        );
    }

    public function testFindShouldMatchValueOfKeyArraysStrictly() {
        $aia = new ArrayIndexedArray();
        $aia[['foo' =>   1, 'bar' => '2']] = 'test1';
        $aia[['foo' => '1', 'bar' => '2']] = 'test2';

        $filtered = $aia->find(['foo' => 1]);

        $this->assertEquals(
            1,
            count($filtered),
            'Method find() should match values of key-arrays strictly'
        );
    }

    public function testGetFlattenArrayReturnsAllElements() {
        $aia = new ArrayIndexedArray();
        $aia[[1]] = 'test1';
        $aia[[2]] = 'test2';

        $this->assertEquals(
            2,
            count($aia->getFlattenArray()),
            'Method should return same amount of records to content of the container'
        );
    }
}
