<?php

class MugoCalendarPersistentObject extends eZPersistentObject
{
    const TYPE_SINGLE = 1;
    const TYPE_RECURRING = 2;
    const TYPE_EXCEPTION = 3;

    /**
     * @return array
     */
    public static function definition()
    {
        static $def = array(
            'fields' => array(
                'id' => array(
                    'name' => 'id',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true,
                ),
                'attribute_id' => array(
                    'name' => 'attribute_id',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true,
                ),
                'version' => array(
                    'name' => 'version',
                    'datatype' => 'integer',
                    'default' => null,
                    'required' => true,
                ),
                'start' => array(
                    'name' => 'start',
                    'datatype' => 'integer',
                    'default' => null,
                    'required' => true,
                ),
                'end' => array(
                    'name' => 'end',
                    'datatype' => 'integer',
                    'default' => null,
                    'required' => true,
                ),
                'type' => array(
                    'name' => 'type',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true,
                ),
                'recurrence_end' => array(
                    'name' => 'recurrence_end',
                    'datatype' => 'integer',
                    'default' => null,
                    'required' => false,
                ),
                'reference'  => array(
                    'name' => 'reference',
                    'datatype' => 'string',
                    'default' => '',
                    'required' => false,
                ),
                'data' => array(
                    'name' => 'data',
                    'datatype' => 'string',
                    'default' => '',
                    'required' => false,
                ),
            ),
            'keys' => array( 'id' ),
            'function_attributes' => array(  ),
            'class_name' => 'MugoCalendarPersistentObject',
            'name' => 'mugo_calendar_event'
        );

        return $def;
    }

    /**
     * Fetch entries by attribute
     *
     * @param int $attributeId
     * @param int $version
     * @param bool $asObject
     * @return MugoCalendarPersistentObject[]
     */
    public static function fetchListByAttribute( $attributeId, $version, $asObject = true )
    {
        return eZPersistentObject::fetchObjectList(
            self::definition(),
            null,
            array( 'attribute_id' => $attributeId, 'version' => $version  ),
            null,
            null,
            $asObject
        );
    }

    /**
     * @param int $versionNumber
     * @param int $attributeId
     * @return boolean
     */
    public function createNewVersion( $versionNumber, $attributeId )
    {
        $this->setAttribute( 'version', $versionNumber );
        $this->setAttribute( 'attribute_id', $attributeId );
        $this->setAttribute( 'id', '' );

        $this->store();

        return true;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if( !isset( $this->start ) || is_null( $this->start ) )
        {
            return false;
        }

        return true;
    }

    /**
     * !! NOT TESTED !!
     *
     * @param int $id
     * @param bool $asObject
     * @return array|eZPersistentObject|null
     */
    static function fetch( $id, $asObject = true )
    {
        return eZPersistentObject::fetchObject( self::definition(),
            null,
            array( 'id' => $id ),
            $asObject
        );
    }

}
