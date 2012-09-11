<?php

class Filter {
	
	/**
	 * self class instance
	 * 
	 * (default value: null)
	 * 
	 * @var mixed
	 * @access protected
	 * @static
	 */
	protected static $instance = null;
	
	/**
	 * _events
	 * 
	 * (default value: null)
	 * 
	 * @var mixed
	 * @access protected
	 * @static
	 */
	protected static $events = array('before', 'after', 'around');
	
	/**
	 * Filter valid arguments if filters are register as properties.
	 * 
	 * (default value: array('on', 'only', 'except'))
	 * 
	 * @var string
	 * @access protected
	 * @static
	 */
	protected static $args = array('on', 'only', 'except');
	
	/**
	 * Current filter, set when register() is called
	 * 
	 * (default value: null)
	 * 
	 * @var mixed
	 * @access protected
	 * @static
	 */
	protected static $filter = null;
	
	/**
	 * Valid Filters
	 * 
	 * (default value: array('before', 'after'))
	 * 
	 * @var string
	 * @access protected
	 * @static
	 */
	protected static $filters = array();
	
	/**
	 * Controller method to call for embed filters
	 * 
	 * @const
	 */
	const filter_method = 'filter';
	
	/**
	 * Create a new Class instance, and register controller's filters.
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function instance()
	{
		if (is_null(self::$instance))
		{
			// store a class instance
			static::$instance = new self();
			
			// Get the controller instance
			$controller = Request::active()->controller_instance;
			
			// register controller's properties
			static::register_from_properties($controller);

			// There is a self::filter_method in our controller
			if (method_exists($controller, self::filter_method))
			{
				$controller->{self::filter_method}();
			}
		}
		return static::$instance;
	}
	
	/**
	 * Register a filter method.
	 * 
	 * @access public
	 * @static
	 * @param mixed $action
	 * @return void
	 */
	public static function register($type, $method)
	{
		static::$filter = $method;
		static::$filters[$method] = array('event' => $type, 'action' => $method);
				
		return static::$instance;
	}
	
	/**
	 * Specify actions .
	 * 
	 * @access public
	 * @return void
	 */
	public static function only()
	{
		static::$filters[static::$filter]['only'] = func_get_args();
		
		return static::$instance;
	}
	
	/**
	 * Exclude actions form filtering.
	 * 
	 * @access public
	 * @return void
	 */
	public function except()
	{
		static::$filters[static::$filter]['except'] = func_get_args();
		
		return static::$instance;
	}
	
	/**
	 * Http method inclusions (post, get, put ...etc)
	 * 
	 * @access public
	 * @param mixed $method
	 * @return void
	 */
	public static function on($method)
	{
		static::$filters[static::$filter]['method'] = func_get_args();
		
		return static::$instance;
	}
	
	/**
	 * Call registerd filters on specific func args events.
	 * 
	 * @access public
	 * @static
	 * @param mixed $action
	 * @return void
	 */
	public static function run($fevent)
	{
		foreach (array($fevent, 'around') as $event)
		{
			foreach (static::$filters as $method => $filter)
			{
				if (static::has_input_method($filter) and static::has_request_action($filter, $event))
				{
					$controller = Request::active()->controller_instance;
					
					// Method is found
					if (method_exists($controller, $method))
					{
						$controller->$method();
					}
					else
					{
						// Try to load a foreign class
						$class = explode('::', $method);
						
						if (class_exists($class[0]))
						{
							// no method provided
							if (empty($class[1]))
							{
								new $method($controller, $fevent);
							}
							// run the clas method, and whitout to check it
							else
							{
								list($c, $m) = $class;
								$c::{$m}($controller, $fevent);
							}
						}
						else
						{
							// throw an exception to notify user
							throw new InvalidArgumentException("filter doesn't exists: ".$method);
						}	
					}
				}
			}
		}
		return null;
	}
	
	
	/**
	 * Check if controller has $before_filter and $after_filter properties,
	 * then register them.
	 * 
	 * @access protected
	 * @return void
	 */
	protected static function register_from_properties($controller)
	{
		foreach (static::$events as $event)
		{
			$property = $event.'_filter';
			
			// there is no such property, so try next one...
			if (! property_exists($controller, $property) or empty($controller->$property))
			{
				continue;
			}
			
			// Inspect ans register properties
			foreach( $controller->$property as $name => $args)
			{
				// set conditions ?
				if (is_string($args))
				{
					static::register($event, $args);
					continue;
				}
				
				// Register our event
				static::register($event, $name);
				
				// Filter args and run methods
				foreach (array_intersect_key($args, array_flip(static::$args)) as $key => $val)
				{
					call_user_func_array(array(static::$instance, $key), (array)$val);
				}
			}
		}
	}
	
	/**
	 * Check if we should to run filter.
	 * 
	 * @access protected
	 * @param mixed $event
	 * @return Bool
	 */
	protected static function has_request_action($filter, $event)
	{
		// filter event === current requested event
		if ($filter['event'] == $event)
		{
			$action	= Request::active()->action;
			$output	= true;
			
			// only on specific actions
			if (isset($filter['only']))
			{
				$output = in_array($action, $filter['only']);
			}
			// skip specific action
			if (isset($filter['except']))
			{
				$output = ! in_array($action, $filter['except']);
			}
			return $output;
		}
		return false;
	}
	
	/**
	 * Check if request fit inputs methods conditions.
	 * Also check for ajax request
	 * 
	 * @access protected
	 * @param mixed $event
	 * @return Bool
	 */
	protected static function has_input_method($filter)
	{
		$methods[] = strtolower(Input::method());
		
		if (isset($filter['method']) and in_array('ajax', $filter['method']))
		{
			$methods[] = Input::is_ajax() ? 'ajax' : null;
		}
		return (! isset($filter['method']) or ! array_intersect($filter['method'], $methods));
	}
}