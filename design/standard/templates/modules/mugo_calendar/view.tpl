{*
    INPUT

        parent_node_id
*}

{def
    $node = fetch( 'content', 'node', hash( 'node_id', $parent_node_id ) )
    $calendarConfig = '{ "header": { "left": "prev,next today", "center": "title", "right": "month,agendaWeek,agendaDay" \} \}'
}

<h2>Calendar: {$node.name|wash()}</h2>
<br />

{include
    uri='design:includes/calendar.tpl'
    parent_node=$node
    calendarConfig=$calendarConfig
}
