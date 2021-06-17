{*
    INPUT

        $parent_node_id : parent_node of calendar events to lookup
        $calendarConfig : config in json format
*}

{ezcss_require( 'fullcalendar.min.css' )}

{ezscript_require( 'ezjsc::jquery' )}
{ezscript_require( 'moment.min.js' )}
{ezscript_require( 'fullcalendar.min.js' )}
{ezscript_require( 'jquery.mugofullcalendar.js' )}

{if is_unset( $calendarConfig )}
    {def $calendarConfig = '{ "header": { "left": "prev,next today", "center": "title", "right": "" \} \}' }
{/if}

{if $parent_node_id}
    <div id="calendar"></div>

    <script type="text/javascript">
        {literal}
        $(function()
        {
            var dataUrl = {/literal}"{'/mugo_calendar/fetch'|ezurl( 'no' )}"{/literal};
            var weekOffset = {/literal}{ezini( 'Calendar', 'WeekOffset', 'mugo_calendar.ini' )}{literal};
            var parentNodeId = {/literal}{$parent_node_id}{literal};
            var config = JSON.parse( '{/literal}{$calendarConfig}{literal}' );

            $( '#calendar' ).mugofullcalendar(
            {
                dataServiceUrl: dataUrl,
                parentNodeId: parentNodeId,
                weekOffset: weekOffset,
                calendarConfigOverride: config,
            });
        });
        {/literal}
    </script>
{/if}
