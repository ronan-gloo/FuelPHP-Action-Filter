<?php

return array(
	'fuelphp' => array(
		
		// run before filters 
    'controller_started' => function()
    {
      Filter::instance()->run('before');
    },
    
    // run after filters
    'controller_finished' => function()
    {
      Filter::instance()->run('after');
    },
	),
);