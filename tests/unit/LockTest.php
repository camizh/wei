<?php

namespace WeiTest;

class LockTest extends TestCase
{
    public function tearDown()
    {
        $this->lock->releaseAll();
    }

    public function testLock()
    {
        $this->assertTrue($this->lock('key'));
        $this->assertFalse($this->lock('key'));
        $this->assertFalse($this->lock('key'));
        $this->assertFalse($this->lock('key'));
        $this->assertFalse($this->lock('key'));
    }

    public function testRelease()
    {
        $this->assertTrue($this->lock('key'));
        $this->assertTrue($this->lock->release('key'));

        $this->assertTrue($this->lock('key'));
        $this->assertTrue($this->lock->release('key'));
        $this->assertFalse($this->lock->release('key'));
    }

    public function testReleaseAll()
    {
        $this->assertTrue($this->lock('key1'));
        $this->assertTrue($this->lock('key2'));

        $this->assertFalse($this->lock('key1'));
        $this->assertFalse($this->lock('key2'));

        $this->lock->releaseAll();

        $this->assertTrue($this->lock('key1'));
        $this->assertTrue($this->lock('key2'));
    }
}