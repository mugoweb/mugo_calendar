<?php
$tpl = eZTemplate::factory();
$tpl->setVariable( 'start_date', isset( $_REQUEST[ 'startdate' ] ) ? $_REQUEST[ 'startdate' ] : date( 'c' ) );

$Result[ 'content' ] = $tpl->fetch( 'design:modules/mugo_calendar/list.tpl' );
