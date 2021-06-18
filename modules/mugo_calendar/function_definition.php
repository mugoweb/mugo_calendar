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
        ),
        array(
            'name' => 'end',
            'type' => 'string',
            'required' =>false,
            'default' => null,
        ),
        array(
            'name' => 'parent_node_id',
            'type' => 'int',
            'required' => false,
            'default' => 1,
        ),
        array(
            'name' => 'subtree',
            'type' => 'bool',
            'required' => false,
            'default' => true,
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
            'default' => null,
        ),
        array(
            'name' => 'with_exceptions',
            'type' => 'boolean',
            'required' => false,
            'default' => true,
        ),
    )
);
