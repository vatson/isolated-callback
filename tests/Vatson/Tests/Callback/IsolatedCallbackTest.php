<?php

namespace Vatson\Tests\Callback;

use Vatson\Callback\IsolatedCallback;
use Fumocker\Fumocker;

/**
 * @author Vadim Tyukov <brainreflex@gmail.com>
 * @since 9/26/12
 */
class IsolatedCallbackTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Fumocker
     */
    protected $fumocker;

    /**
     * @var string
     */
    protected $shared_memory_segment_stub = 'stub';

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->fumocker = new Fumocker();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->fumocker->cleanup();
    }

    /**
     * @test
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage You need to enabled Shared Memory System V(see more "Semaphore")
     */
    public function throwExceptionWhenIPCIsDisabled()
    {
        $file_exists_mock = $this->fumocker->getMock('Vatson\Callback', 'function_exists');

        $file_exists_mock
            ->expects($this->once())
            ->method('function_exists')
            ->with('shm_attach')
            ->will($this->returnValue(false))
        ;

        new IsolatedCallback(function(){});
    }

    /**
     * @test
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage You need to enable PCNTL
     */
    public function throwExceptionWhenPcntlIsDisabled()
    {
        $file_exists_mock = $this->fumocker->getMock('Vatson\Callback', 'function_exists');

        $file_exists_mock
            ->expects($this->at(0))
            ->method('function_exists')
            ->will($this->returnValue(true))
        ;

        $file_exists_mock
            ->expects($this->at(1))
            ->method('function_exists')
            ->with('pcntl_fork')
            ->will($this->returnValue(false))
        ;

        new IsolatedCallback(function(){});
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidCallback
     *
     * @expectedException \InvalidArgumentException
     */
    public function throwExceptionWhenConstructWithInvalidCallback($invalid_callback)
    {
        $this->createIsolatedCallback($invalid_callback);
    }

    /**
     * @test
     *
     * @dataProvider provideValidCallback
     */
    public function shouldBeConstructedWithValidCallback($valid_callback)
    {
        $this->createIsolatedCallback($valid_callback);
    }

    /**
     * @test
     */
    public function shouldRemoveSharedMemorySegmentDuringDestruction()
    {
        $file_exists_mock = $this->fumocker->getMock('Vatson\Callback', 'function_exists');

        $file_exists_mock
            ->expects($this->at(0))
            ->method('function_exists')
            ->will($this->returnValue(true))
        ;

        $file_exists_mock
            ->expects($this->at(1))
            ->method('function_exists')
            ->will($this->returnValue(true))
        ;

        $this->fumocker
            ->getMock('Vatson\Callback', 'shm_attach')
            ->expects($this->once())
            ->method('shm_attach')
            ->will($this->returnValue($this->shared_memory_segment_stub))
        ;

        $this->fumocker
            ->getMock('Vatson\Callback', 'shm_remove')
            ->expects($this->once())
            ->method('shm_remove')
            ->with($this->shared_memory_segment_stub)
        ;

        $isolated_callback = new IsolatedCallback(function(){});
        unset($isolated_callback);
    }

    /**
     * @return array
     */
    public static function provideValidCallback()
    {
        return array(
            array(function () {}),
            array(array(__CLASS__, 'provideValidCallback')),
            array(array(new self, 'provideInvalidCallback')),
            array('rand'),
        );
    }

    /**
     * @return array
     */
    public static function provideInvalidCallback()
    {
        return array(
            array(array(new \stdClass(), 'unknownMethod')),
            array(array('stdClass', 'unknownStaticMethod')),
            array('string'),
            array(false),
            array(1),
            array(1.0),
            array(null),
        );
    }

    /**
     * Helps to automate the passing extensions checks
     *
     * @param $callback
     * @return \Vatson\Callback\IsolatedCallback
     */
    protected function createIsolatedCallback($callback)
    {
        $file_exists_mock = $this->fumocker->getMock('Vatson\Callback', 'function_exists');

        $file_exists_mock
            ->expects($this->at(0))
            ->method('function_exists')
            ->will($this->returnValue(true))
        ;

        $file_exists_mock
            ->expects($this->at(1))
            ->method('function_exists')
            ->will($this->returnValue(true))
        ;

        // Stubs shared memory attach
        $this->fumocker
            ->getMock('Vatson\Callback', 'shm_attach')
            ->expects($this->any())
            ->method('shm_attach')
            ->will($this->returnValue($this->shared_memory_segment_stub))
        ;

        // Stubs shared memory remove
        $this->fumocker
            ->getMock('Vatson\Callback', 'shm_remove')
            ->expects($this->any())
            ->method('shm_remove')
            ->with($this->shared_memory_segment_stub)
        ;

        return new IsolatedCallback($callback);
    }
}
