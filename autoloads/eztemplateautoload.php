<?php
/**
 * Template autoload definition for mugo_calendar
 *
 */

$eZTemplateOperatorArray = array();

$eZTemplateOperatorArray[] = array(
    'class' => 'MugoCalendarAutoLoad',
    'operator_names' => array(
        'datetime_modify',
        'is_exception', // probably needs a more concrete name is_calendar_exception
    )
);
