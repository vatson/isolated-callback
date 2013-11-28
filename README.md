Isolated Callback [![Build Status](https://secure.travis-ci.org/vatson/isolated-callback.png)](http://travis-ci.org/vatson/isolated-callback)
=================

Allows to execute a callable in a fork


Allows to avoid memory leaks

```
        $callback = function() {
            return 'result!!!';
        };

        $isolatedCallback = new IsolatedCallback($callback);
        $this->assertEquals($callback(), $isolatedCallback()); 
        // $isolatedCallback() executes callback in a forked process
```        
