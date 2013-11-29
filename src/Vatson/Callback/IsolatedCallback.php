<?php

namespace Vatson\Callback;

use Vatson\Callback\Exception\ExceptionDataHolder;
use Vatson\Callback\Exception\IsolatedCallbackExecutionException;
use UniversalErrorCatcher_Catcher as ErrorCatcher;

/**
 * @author Vadim Tyukov <brainreflex@gmail.com>
 * @since 9/26/12
 */
class IsolatedCallback
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var resource
     */
    protected $shared_memory_segment;

    /**
     * @var int
     */
    protected static $SEGMENT_VAR_ID = 1;

    /**
     * @param callable $callback
     */
    public function __construct($callback)
    {
        if (!function_exists('shm_attach')) {
            throw new \RuntimeException('You need to enabled Shared Memory System V(see more "Semaphore")');
        }

        if (!function_exists('pcntl_fork')) {
            throw new \RuntimeException('You need to enable PCNTL');
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Given callback is not callable');
        }

        $this->callback = $callback;
        $this->shared_memory_segment = shm_attach(time() + rand(1, 1000));
    }

    /**
     * Calls a callback in a separate fork and returns the received result
     *
     * @throws \RuntimeException when fork can not be created
     *
     * @return mixed
     */
    public function __invoke()
    {
        $arguments = func_get_args();

        switch ($pid = pcntl_fork()) {
            case -1:
                throw new \RuntimeException();
            case 0:
                $this->registerChildShutdown();
                $this->handleChildProcess($arguments);
                exit;
            default:
                return $this->handleParentProcess();
        }
    }

    /**
     * Avoids the closing of resources in child process
     */
    protected function registerChildShutdown()
    {
        (new ErrorCatcher)
            ->registerCallback(function($e) {
                $this->sendChildExecutionResult(new ExceptionDataHolder($e));
            })
            ->start();

        register_shutdown_function(function () {
            posix_kill(getmypid(), SIGKILL);
        });

    }

    /**
     * @throws \Exception when child process ends with an Exception
     *
     * @return mixed
     */
    protected function handleParentProcess()
    {
        pcntl_wait($status);
        $result = $this->receiveChildExecutionResult();

        if ($result instanceof ExceptionDataHolder) {
            throw new IsolatedCallbackExecutionException($result);
        }

        return $result;
    }

    /**
     * @param array $arguments
     */
    protected function handleChildProcess(array $arguments)
    {
        $result = null;

        try {
            $result = call_user_func_array($this->callback, $arguments);
        } catch (\Exception $e) {
            $result = new ExceptionDataHolder($e);
        }

        $this->sendChildExecutionResult($result);
    }

    /**
     * @param mixed $result
     */
    protected function sendChildExecutionResult($result)
    {
        shm_put_var($this->shared_memory_segment, self::$SEGMENT_VAR_ID, $result);
    }

    /**
     * @return mixed
     */
    protected function receiveChildExecutionResult()
    {
        if (shm_has_var($this->shared_memory_segment, self::$SEGMENT_VAR_ID)) {
            $result = shm_get_var($this->shared_memory_segment, self::$SEGMENT_VAR_ID);
            shm_remove_var($this->shared_memory_segment, self::$SEGMENT_VAR_ID);

            return $result;
        }
    }

    /**
     * Removes shared memory segment
     */
    public function __destruct()
    {
        shm_remove($this->shared_memory_segment);
    }
}
