{*
    INPUT

        $start_date: REQUIRED

        $end_date: string representation of a date - for exampel 2016-06-01
        $parent_node_id : entry point
        $view : default is 'line'
        $limit: default is '100'
        $css_classes: class string
	$show_fetched: default is true
*}

{if is_unset( $parent_node_id )}
    {def $parent_node_id = 2}
{/if}
{if is_unset( $limit )}
    {def $limit = 100}
{/if}
{if is_unset( $view )}
    {def $view = 'line'}
{/if}
{if is_unset( $css_classes )}
    {def $css_classes = ''}
{/if}
{if is_unset( $end_date )}
    {* cannot set it to null *}
{/if}
{if is_unset( $show_fetched )}
    {set $show_fetched = true()}
{/if}

{def $fetch_events_parameters = hash(
    'start',          $start_date,
    'parent_node_id', $parent_node_id,
    'limit',          $limit,
)}
{if is_set( $end_date )}
    {set $fetch_events_parameters = $fetch_events_parameters|merge( hash( 'end', $end_date ) )}
{/if}

{if $start_date}
    {if $parent_node_id}
        {def $events = fetch( 'mugo_calendar', 'events', $fetch_events_parameters)}

        {if and( $events|count(), $show_fetched )}
            <ul class="{$css_classes}">
                {foreach $events as $event}
                    <li>
                        {node_view_gui
                            content_node=$event.object.main_node
                            view=$view
                            event=$event
                        }
                    </li>
                {/foreach}
            </ul>
        {else}
           No events found
        {/if}
    {/if}
{/if}
