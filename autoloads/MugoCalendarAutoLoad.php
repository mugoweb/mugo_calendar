<?php


class MugoCalendarAutoLoad
{

    public function operatorList()
    {
        return array(
            'datetime_modify',
            'is_exception',
        );
    }

    public function namedParameterPerOperator()
    {
        return false;
    }

    public function namedParameterList()
    {
        return array( 
            'datetime_modify' => array(
                'modify' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                ),
            )
        );
    }

    public function modify(
        $tpl,
        $operatorName,
        $operatorParameters,
        $rootNamespace,
        $currentNamespace,
        &$operatorValue,
        $namedParameters
    )
    {
        switch ( $operatorName )
        {
            case 'datetime_modify':
            {
                $dateTime = MugoCalendarFunctions::strToDateTime( $operatorValue );

                $modifyString = $namedParameters[ 'datetime_modify' ];

                if( $modifyString )
                {
                    $dateTime->modify( $modifyString );
                    $operatorValue = $dateTime->getTimestamp();
                }
            }
            break;

            case 'is_exception':
            {
                $operatorValue = MugoCalendarFunctions::isRecurrenceException( $operatorValue );
            }
            break;
        }
    }
}
