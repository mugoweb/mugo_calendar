{if is_unset( $attribute_base )}
    {def $attribute_base='ContentObjectAttribute'}
{/if}

{def
    $content = $attribute.content
    $parent = $attribute.object.main_node.parent
    $isException = $attribute.object|is_exception()
    $eventTypes = hash(
        0, 'unknown'|i18n( 'mugo_calendar' ),
        1, 'Single Event'|i18n( 'mugo_calendar' ),
        2, 'Recurring Event'|i18n( 'mugo_calendar' ),
        3, 'Event Exception'|i18n( 'mugo_calendar' ),
    )
}

<div class="eventView">
    {if $isException}
        {def $exception_definition = $content.0}

        {* updating event *}
        {if $exception_definition.start}
            {def $parts = $exception_definition.for|explode( '-' )}
            Exception for event on {$parts.1|datetime( 'mugocalendarday' )}<br />

            Event times:<br />
            {if $exception_definition.all_day_event}
                {$exception_definition.start|datetime( 'mugocalendarday' )}
                {if ne( $exception_definition.start, $exception_definition.end )}
                    -
                    {$exception_definition.end|datetime( 'mugocalendarday' )}
                {/if}
            {else}
                {$exception_definition.start|datetime( 'mugocalendarday' )}
                {$exception_definition.start|datetime( 'mugocalendartime' )}
                -
                {if ne( $event.start|datetime( 'mugocalendarday' ), $event.end|datetime( 'mugocalendarday' ) )}
                    {$exception_definition.end|datetime( 'mugocalendarday' )}
                {/if}
                {$exception_definition.end|datetime( 'mugocalendartime' )}
            {/if}
        {* skipping event *}
        {else}
            {if is_null( $exception_definition.instance )|not()}
                {def $parts = $exception_definition.instance|explode( '-' )}
                Skipping event on {$parts.1|datetime( 'mugocalendarday' )}
            {/if}
        {/if}
    {else}
        <ul>
            {foreach $content as $event_definition}
                <li>
                    {if eq( $event_definition.type, 2 )}
                        <h4>Recurring occurrences:</h4>
                        {$event_definition|wash()}<br />
                    {else}
                        <h4>Single occurrence:</h4>
                    {/if}

                    {if $event_definition.all_day_event}
                        {$event_definition.start|datetime( 'mugocalendarday' )}
                        {if ne( $event_definition.start, $event_definition.end )}
                            to
                            {$event_definition.end|datetime( 'mugocalendarday' )}
                        {else}
                            all day
                        {/if}
                    {else}
                        {$event_definition.start|datetime( 'mugocalendarday' )}
                        {$event_definition.start|datetime( 'mugocalendartime' )}
                        -
                        {* Do not repeat day if it's the same day *}
                        {if ne( $event_definition.start|datetime( 'mugocalendarday' ), $event_definition.end|datetime( 'mugocalendarday' ) )}
                            {$event_definition.end|datetime( 'mugocalendarday' )}
                        {/if}
                        {$event_definition.end|datetime( 'mugocalendartime' )}
                    {/if}
                </li>
            {/foreach}
        </ul>
    {/if}
</div>
