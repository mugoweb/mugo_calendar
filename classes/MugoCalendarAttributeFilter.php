<?php

/**
 *
 */
class MugoCalendarAttributeFilter
{
    /**
     * Gets events for a given time range
     *
     * @param array $params
     *
     * @return array
     */
    public function createSqlPartsRange( $params )
    {
        $returnArray = array( 'tables' => '', 'joins'  => '', 'columns' => '' );

        $startTime = (int) $params[ 'start' ];
        $endTime = (int) $params[ 'end' ];

        if( $startTime < $endTime )
        {
            $returnArray[ 'tables' ] = 'JOIN ezcontentobject_attribute mugo_calendar_attribute ON ( ezcontentobject.current_version = mugo_calendar_attribute.version AND ezcontentobject.id = mugo_calendar_attribute.contentobject_id AND mugo_calendar_attribute.data_type_string = "mugorecurringevent" ) ';
            $returnArray[ 'tables' ] .= 'INNER JOIN mugo_calendar_event ON ( ezcontentobject.current_version = mugo_calendar_event.version AND mugo_calendar_attribute.id = mugo_calendar_event.attribute_id ) ';

            $returnArray[ 'joins' ]  = '( ( mugo_calendar_event.type = 1';
            $returnArray[ 'joins' ] .= ' AND mugo_calendar_event.start < '. $endTime;
            $returnArray[ 'joins' ] .= ' AND mugo_calendar_event.end >= '. $startTime . ')';
            $returnArray[ 'joins' ] .= ' OR';
            $returnArray[ 'joins' ] .= ' ( mugo_calendar_event.type = 2';
            $returnArray[ 'joins' ] .= ' AND mugo_calendar_event.start < ' . $endTime;
            $returnArray[ 'joins' ] .= ' AND ( mugo_calendar_event.recurrence_end >= '. $startTime .' OR mugo_calendar_event.recurrence_end IS NULL ) )';
            $returnArray[ 'joins' ] .= ' ) AND ';
        }
        else
        {
            //report
            // force empty result set
            $returnArray[ 'joins' ] = '1=2 AND';
        }

        return $returnArray;
    }

    /**
     * Fetch exceptions for given list of IDs
     *
     * @param array $params
     *
     * @return array
     */
    public function createSqlPartsByIds( $params )
    {
        $returnArray = array( 'tables' => '', 'joins'  => '', 'columns' => '' );

        $ids = $params[ 'ids' ];

        if( !empty( $ids ) )
        {
            $sqlIn = 'IN( "'. implode( '","', $ids ) .'" )';

            $returnArray[ 'tables' ] = 'JOIN ezcontentobject_attribute mugo_calendar_attribute ON ( ezcontentobject.current_version = mugo_calendar_attribute.version AND ezcontentobject.id = mugo_calendar_attribute.contentobject_id AND mugo_calendar_attribute.data_type_string = "mugorecurringevent" ) ';
            $returnArray[ 'tables' ] .= 'INNER JOIN mugo_calendar_event ON ( ezcontentobject.current_version = mugo_calendar_event.version AND mugo_calendar_attribute.id = mugo_calendar_event.attribute_id ) ';

            $returnArray[ 'joins' ]  = 'mugo_calendar_event.reference ' . $sqlIn;
            $returnArray[ 'joins' ] .= ' AND ';
        }

        return $returnArray;
    }

    /**
     * Get all recurring events in a given time period
     *
     * @param $params
     * @return array
     */
    public function createSqlPartsRangeRecurring( $params )
    {
        $returnArray = array( 'tables' => '', 'joins'  => '', 'columns' => '' );
        $startTime = (int) $params[ 'start' ];
        $endTime = (int) $params[ 'end' ];

        $returnArray[ 'tables' ] = 'JOIN ezcontentobject_attribute mugo_calendar_attribute ON ( ezcontentobject.current_version = mugo_calendar_attribute.version AND ezcontentobject.id = mugo_calendar_attribute.contentobject_id AND mugo_calendar_attribute.data_type_string = "mugorecurringevent" ) ';
        $returnArray[ 'tables' ] .= 'INNER JOIN mugo_calendar_event ON ( ezcontentobject.current_version = mugo_calendar_event.version AND mugo_calendar_attribute.id = mugo_calendar_event.attribute_id ) ';

        $returnArray[ 'joins' ]  = 'mugo_calendar_event.type = 2';
        $returnArray[ 'joins' ] .= ' AND mugo_calendar_event.start < ' . $endTime;
        $returnArray[ 'joins' ] .= ' AND ( mugo_calendar_event.recurrence_end >= '. $startTime .' OR mugo_calendar_event.recurrence_end IS NULL )';
        $returnArray[ 'joins' ] .= ' AND ';

        return $returnArray;
    }

}
