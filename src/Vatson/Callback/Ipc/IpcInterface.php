<?php

namespace Vatson\Callback\Ipc;


interface IpcInterface
{
    public function get();

    public function put($data);
}