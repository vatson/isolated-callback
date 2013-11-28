Isolated Callback [![Build Status](https://secure.travis-ci.org/vatson/isolated-callback.png)](http://travis-ci.org/vatson/isolated-callback)
=================

Allows to execute a callable in a fork



```
        $callback = function() {
            return 'result!!!';
        };

        $isolatedCallback = new IsolatedCallback($callback);
        $this->assertEquals($callback(), $isolatedCallback()); // $isolatedCallback() execute callback in the fork
```        
