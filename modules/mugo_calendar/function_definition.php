<?php

$FunctionList = array();

$FunctionList[ 'events' ] = array(
	'name' => 'events',
	'operation_types' => array( 'read' ),
	'call_method' => array(
		'class' => 'MugoCalendarFetchFunction',
		'method' => 'fetchEvents'
	),
	'parameter_type' => 'standard',
	'parameters' => array(
		array(
			'name' => 'start',
			'type' => 'string',
			'required' => true,
			'default' => null,
		),
		array(
			'name' => 'end',
			'type' => 'string',
			'required' =>false,
			'default' => null,
		),
		array(
			'name' => 'parent_node_id',
			'type' => 'string',
			'required' => false,
			'default' => null,
		),
		array(
			'name' => 'subtree',
			'type' => 'integer',
			'required' => false,
			'default' => null,
		),
		array(
			'name' => 'filters',
			'type' => 'array',
			'required' => false,
			'default' => null,
		),
		array(
			'name' => 'limit',
			'type' => 'integer',
			'required' => false,
			'default' => null,
		)
	),
);

$FunctionList[ 'resolve_recurrence' ] = array(
	'name' => 'events',
	'operation_types' => array( 'read' ),
	'call_method' => array(
		'class' => 'MugoCalendarFetchFunction',
		'method' => 'resolveRecurringEvent'
	),
	'parameter_type' => 'standard',
	'parameters' => array(
		array(
			'name' => 'events',
			'type' => 'array',
			'required' => true,
			'default' => null,
		),
		array(
			'name' => 'start',
			'type' => 'string',
			'required' => false,
			'default' => null,
		),
		array(
			'name' => 'end',
			'type' => 'string',
			'required' => false,
			'default' => null,
		),
		array(
			'name' => 'limit',
			'type' => 'integer',
			'required' => false,
			'default' => false,
		),
		array(
			'name' => 'with_exceptions',
			'type' => 'boolean',
			'required' => false,
			'default' => true,
		),
	)
);
