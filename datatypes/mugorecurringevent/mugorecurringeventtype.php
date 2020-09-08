<?php

/**
 * @author pkamps@mugo.ca
 *
 */
class MugoRecurringEventType extends eZDataType
{
    const DATA_TYPE_STRING = 'mugorecurringevent';

    function __construct()
    {
        parent::__construct(
            self::DATA_TYPE_STRING, ezpI18n::tr( 'mugo_calendar/datatype', 'Mugo Recurring Event', 'Datatype name' ),
            array( 'serialize_supported' => true )
        );
    }

    /**
     * returning 'true', nothing else to do
     *
     * @param $contentObjectAttribute
     * @return bool
     */
    function storeObjectAttribute( $contentObjectAttribute )
    {
        $content = $contentObjectAttribute->content();
        $contentObjectAttribute->setAttribute( 'data_text', $content );

        return true;
    }

    /* (non-PHPdoc)
     * @see eZDataType::validateClassAttributeHTTPInput()
     */
    function validateClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    /**
     *
     * @param $http
     * @param $base
     * @param $contentObjectAttribute
     * @return bool
     */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $postName = $base . '_mugorecurringevent_' . $contentObjectAttribute->attribute( 'id' );

        $existingMugoCalendarPersistentObjects = MugoCalendarPersistentObject::fetchListByAttribute(
            $contentObjectAttribute->attribute( 'id' ),
            $contentObjectAttribute->attribute( 'version' )
        );

        if( !empty( $existingMugoCalendarPersistentObjects ) )
        {
            foreach( $existingMugoCalendarPersistentObjects as $entry )
            {
                $entry->remove();
            }
        }

        if( $http->hasPostVariable( $postName ) )
        {
            $postData = $http->postVariable( $postName );

            // is an exception?
            if( isset( $postData[ 'parentContentObjectId' ] ) )
            {
                $eventRow = array(
                    'reference' => $postData[ 'instance' ],
                    'attribute_id' => $contentObjectAttribute->attribute( 'id' ),
                    'version' => $contentObjectAttribute->attribute( 'version' ),
                    'type' => MugoCalendarPersistentObject::TYPE_EXCEPTION,
                );

                if( $postData[ 'type' ] == 'change' )
                {
                    if( $postData[ 'exceptionstartdate' ] && $postData[ 'exceptionstarttime' ] )
                    {
                        $eventRow[ 'start' ] = strtotime( $postData[ 'exceptionstartdate' ] . ' ' . $postData[ 'exceptionstarttime' ] );
                    }
                    if( $postData[ 'exceptionenddate' ] && $postData[ 'exceptionendtime' ] )
                    {
                        $eventRow[ 'end' ] = strtotime( $postData[ 'exceptionenddate' ] . ' ' . $postData[ 'exceptionendtime' ] );
                    }
                }

                $mugoCalendarPersistentObject = new MugoCalendarPersistentObject( $eventRow );
                $mugoCalendarPersistentObject->store();
            }
            else
            {
                $postData = $this->reArrangePostVars( $postData );

                foreach( $postData as $entry )
                {
                    $eventRow = array(
                        'start' => strtotime( $entry[ 'startdate' ] . ' ' . $entry[ 'starttime' ] ),
                        'end' => strtotime( $entry[ 'enddate' ] . ' ' . $entry[ 'endtime' ] ),
                        'attribute_id' => $contentObjectAttribute->attribute( 'id' ),
                        'version' => $contentObjectAttribute->attribute( 'version' ),
                        'type' => MugoCalendarPersistentObject::TYPE_SINGLE,
                        'data' => isset( $entry[ 'data' ] ) ? $entry[ 'data' ] : null,
                    );

                    if( $entry[ 'recurrence_type' ] == 'recurring' )
                    {
                        $eventRow[ 'type' ] = MugoCalendarPersistentObject::TYPE_RECURRING;
                        $eventRow[ 'recurrence_end' ] = strtotime( $entry[ 'rangeEnd' ] );
                        $eventRow[ 'reference' ] = json_encode(
                            array(
                                'type' => $entry[ 'type' ],
                                'interval' => $entry[ 'interval' ],
                                'weeklyWeekDay' => $entry[ 'weeklyWeekDay' ],
                                'monthlyType' => $entry[ 'monthlyType' ],
                                'day' => $entry[ 'day' ],
                                'monthlyWeekDay' => $entry[ 'monthlyWeekDay' ],
                            )
                        );
                    }

                    $mugoCalendarPersistentObject = new MugoCalendarPersistentObject( $eventRow );
                    if( $mugoCalendarPersistentObject->isValid() )
                    {
                        $mugoCalendarPersistentObject->store();
                    }
                }
            }
        }
        else
        {
            $contentObjectAttribute->setContent(
                $contentObjectAttribute->attribute( 'id' ) .
                '_' .
                $contentObjectAttribute->attribute( 'version' )
            );
        }

        return true;
    }

    /**
     *
     * @param eZContentObjectAttribute $objectAttribute
     * @return array
     */
    public function objectAttributeContent( $objectAttribute )
    {
        $return = [];

        $eventsData = MugoCalendarPersistentObject::fetchListByAttribute(
            $objectAttribute->attribute( 'id' ),
            $objectAttribute->attribute( 'version' )
        );

        if( !empty( $eventsData ) )
        {
            foreach( $eventsData as $entry )
            {
                //TODO: remove next line
                $entry->contentobject = $objectAttribute->attribute( 'object' );
                $entry->objectAttribute = $objectAttribute;

                $return[] = new MugoCalendarEvent( $entry );
            }
        }

        return $return;
    }

    /*
     * PEK: it's kinda strange: this function gets called when a new content class
     * version get initialized (even before the user hits apply/ok).
     */
    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        /*
        $hasPost = false;

        $options = array(
            'max_filesize'   => 0,
            'allow_multiple' => false,
            'auto_upload'    => false
        );

        foreach( $options as $key => $value )
        {
            $name = $base . '_dam_images_'. $key .'_' . $classAttribute->attribute( 'id' );
            if ( $http->hasPostVariable( $name ) )
            {
                $hasPost = true;
                $options[ $key ] = $http->postVariable( $name );
            }
        }

        if( $hasPost )
        {
            $classAttribute->setAttribute( 'data_text4', implode( '-', $options ) );
        }
        */

        return true;
    }

    /**
     * Init attribute ( also handles version to version copy, and attribute to attribute copy )
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param int|null $currentVersion
     * @param eZContentObjectAttribute $originalContentObjectAttribute
     * @return MugoRecurringEventType
     */
    public function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {
        if (  $currentVersion !== false && $contentObjectAttribute->attribute( 'id' ) != 0  )
        {
            $events = MugoCalendarPersistentObject::fetchListByAttribute(
                $originalContentObjectAttribute->attribute( 'id' ),
                $originalContentObjectAttribute->attribute( 'version' )
            );

            if( !empty( $events ) )
            {
                foreach( $events as $event )
                {
                    $event->createNewVersion(
                        $contentObjectAttribute->attribute( 'version' ),
                        $contentObjectAttribute->attribute( 'id' )
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Delete occurrences in the DB table
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param int|null $version (Optional, deletes all versions if null)
     * @return MugoRecurringEventType
     */
    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
        if (  $version !== false && $contentObjectAttribute->attribute( 'id' ) != 0  )
        {
            $events = MugoCalendarPersistentObject::fetchListByAttribute(
                $contentObjectAttribute->attribute( 'id' ),
                $version
            );

            if( !empty( $events ) )
            {
                foreach( $events as $event )
                {
                    $event->remove();
                }
            }
        }

        return $this;
    }

    /**
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return bool
     */
    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $events = MugoCalendarPersistentObject::fetchListByAttribute(
            $contentObjectAttribute->attribute( 'id' ),
            $contentObjectAttribute->attribute( 'version' )
        );

        return count( $events ) > 0;
    }

    /* (non-PHPdoc)
     * @see eZDataType::validateClassAttributeHTTPInput()
     */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if( $this->validateForRequiredContent( $http, $base, $contentObjectAttribute ) )
        {
            return eZInputValidator::STATE_ACCEPTED;
        }
        else
        {
            $contentObjectAttribute->setValidationError(
                ezpI18n::tr( 'kernel/classes/datatypes',
                    'Input required.' )
            );

            return eZInputValidator::STATE_INVALID;
        }
    }

    /**
     * Check if attribute is required and has content
     *
     * @param type $http
     * @param string $base
     * @param type $contentObjectAttribute
     * @return boolean
     */
    protected function validateForRequiredContent( $http, $base, $contentObjectAttribute )
    {
        // Return true if attribute is marked as not required
        if( !$contentObjectAttribute->validateIsRequired() )
        {
            return true;
        }

        $return = false;

        $postName = $base . '_mugorecurringevent_' . $contentObjectAttribute->attribute( 'id' );

        //TODO: incomplete
        if( $http->hasPostVariable( $postName ) )
        {
            $data = $http->postVariable( $postName );

            $return = isset( $data );
        }

        return $return;
    }

    /**
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return string
     */
    public function toString( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( 'data_text' );
    }

    /**
     *
     * @param eZContentObjectAttribute $objectAttribute
     * @param string $string
     * @return boolean
     */
    public function fromString( $contentObjectAttribute, $string )
    {
        $definition = unserialize( $string );

        if( !empty( $definition ) )
        {
            $contentObjectAttribute->setAttribute( 'data_int', strtotime( $definition[ 'rangeStart' ] ) );
            $contentObjectAttribute->setAttribute( 'data_float', strtotime( $definition[ 'rangeEnd' ] ) );
            $contentObjectAttribute->setAttribute( 'data_text', $string );
        }

        return true;
    }

    /**
     * Untested
     *
     * @param type $contentObjectAttribute
     * @return type
     */
    public function metaData( $contentObjectAttribute )
    {
        //return unserialize( $contentObjectAttribute->attribute( 'data_text' ) );
    }

    public function isIndexable()
    {
        return true;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function reArrangePostVars( $data )
    {
        $return = array();

        if( !empty( $data ) )
        {
            foreach( $data[ 'interval' ] as $index => $interval )
            {
                $entry = array();
                foreach( $data as $field => $values )
                {
                    $entry[ $field ] = $values[ $index ];
                }

                $return[] = $entry;
            }
        }

        return $return;
    }
}

eZDataType::register( MugoRecurringEventType::DATA_TYPE_STRING, 'MugoRecurringEventType' );