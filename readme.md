FuelPHP action filters
======================

Custom implemetation of rails filters for fuelPHP framework.

### Filters

Filter’s name can be a embedded controller’s method or a class.  

-   **before**: filter is called on controller\_started event
-   **after**: filter is called on controller\_finished
-   **around**: filter is called on both controller\_started and
    controller\_finished events

### Filters configuration

-   **only**: run filter only on defined actions
-   **except**: skip filter on defined actions
-   **on**: run filter on defined http methods (get, post, put …etc) and 'ajax'

1. Setup filters as class properties
------------------------------------

Run ‘ajax\_only’ method on get or post, except for action\_index()

    public $before_filter = array(
        'ajax_only' => array(
            'except'    => 'index',
            'on'        => array('get', 'post')
        )
    );

    public function ajax_only()
    {
        if( ! Input::is_ajax())
        {
            throw new HttpNotFoundException;
        }
    }

2. Setup filters under filter() method
--------------------------------------

You can set filters by using Filter class

    public function filter()
    {
        Filter::register('after', 'updated')->only('add', 'edit', 'delete')->on('post');
    }

    public function updated()
    {
        // do something here...
    }

3. Calling a class instead of embedded controller’s method
----------------------------------------------------------

It’s possible to use a class as filter, and optionnaly specify a
method.  
 The controller instance and event name are passed to the constructor,
or the method if provided in filter name.

*Controller*:

    public $around_filter = array('Bench::action')

*class Bench*:

    class Bench {
        
        public static function action($controller, $event)
        {
            $msg = get_class($controller).' '.$event.' '.$controller->request->action;
            
            Profiler::mark_memory($controller, $msg);
        }
        
    }
