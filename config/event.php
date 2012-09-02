<?php

return array(
	'fuelphp' => array(
		
		// run before filters 
    'controller_started' => function()
    {
      Filter::run('before');
    },
    
    // run after filters
    'controller_finished' => function()
    {
      Filter::run('after');
    },
	),
);