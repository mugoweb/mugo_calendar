<?php
$parentNodeId = isset( $_REQUEST[ 'parent_node_id' ] ) ? (int) $_REQUEST[ 'parent_node_id' ] : 2;
$parentNodeId = $parentNodeId ?: 2;

$tpl = eZTemplate::factory();
$tpl->setVariable( 'parent_node_id', $parentNodeId );

$Result[ 'content' ] = $tpl->fetch( 'design:modules/mugo_calendar/view.tpl' );
