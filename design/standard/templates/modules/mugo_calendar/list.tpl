{*
    INPUT
    start_date
*}

<h1>Example Listing</h1>
<pre>
{literal}
{def $events = fetch( 'mugo_calendar', 'events', hash(
    'start', $start_date,
    'parent_node_id', 1,
    'limit', 100,
))}
{/literal}
</pre>

<h2>Result</h2>

{include uri='design:includes/agenda.tpl' parent_node_id=1}
