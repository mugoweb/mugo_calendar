<?php

class MugoCalendarFetchFunction
{
    /**
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
        $endTime,
        int $parentNodeId,
        bool $subtree = true,
        array $filter = null,
        int $limit = null
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
     * @param MugoCalendarEvent[] $recurringEvents
     * @param int $startTime
     * @param int $endTime
     * @param int $limit
     * @param boolean $withExceptions
     * @return array
     */
    public static function resolveRecurringEvent(
        $recurringEvents,
        $startTime,
        $endTime,
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
