<?php

/**
 * Class MugoCalendarFetchFunction
 * Wrapper to MugoCalendarFunctions
 * See modules/mugo_calendar/function_definition.php
 */
class MugoCalendarFetchFunction
{
    /**
	 *
     * @param int $startTime
     * @param int $endTime
     * @param int $parentNodeId
     * @param bool $subtree
     * @param array $filter
     * @param int $limit
     * @return array
     */
    public static function fetchEvents(
        $startTime,
        $endTime = null,
        $parentNodeId = 1,
        $subtree = true,
        $filter = null,
        $limit = null
    )
    {
        return array( 'result' => MugoCalendarFunctions::fetchEvents(
            MugoCalendarFunctions::strToDateTime( $startTime ),
            MugoCalendarFunctions::strToDateTime( $endTime ),
            $parentNodeId,
            $subtree,
            $filter,
            $limit
        ) );
    }

    /**
     * @param MugoCalendarEventDefinition[] $recurringEvents
     * @param int $startTime
     * @param int $endTime
     * @param int $limit
     * @param boolean $withExceptions
     * @return array
     */
    public static function resolveRecurringEvent(
        $recurringEvents,
        $startTime = null,
        $endTime = null,
        $limit = null,
        $withExceptions = true
    )
    {
        return array( 'result' => MugoCalendarFunctions::resolveRecurringEvent(
            $recurringEvents,
            MugoCalendarFunctions::strToDateTime( $startTime ),
            MugoCalendarFunctions::strToDateTime( $endTime ),
            $limit,
            $withExceptions
        ) );
    }
}
