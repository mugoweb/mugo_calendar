{if is_unset( $attribute_base )}
	{def $attribute_base='ContentObjectAttribute'}
{/if}

{def
	$content = $attribute.content
	$parent = $attribute.object.main_node.parent
	$isException = $attribute.object|is_exception()
}

<div class="eventView">
	{if $isException}
		{def $exception = $content.0}

		{* updating event *}
		{if $exception.start}
			{def $parts = $exception.instance|explode( '-' )}
			Exception for event on {$parts.0|datetime( 'mugocalendarday' )}<br />

			Event times:<br />
			{if $exception.all_day_event}
				{$exception.start|datetime( 'mugocalendarday' )}
				{if ne( $exception.start, $exception.end )}
					-
					{$exception.end|datetime( 'mugocalendarday' )}
				{/if}
			{else}
				{$exception.start|datetime( 'mugocalendarday' )}
				{$exception.start|datetime( 'mugocalendartime' )}
				-
				{if ne( $event.start|datetime( 'mugocalendarday' ), $event.end|datetime( 'mugocalendarday' ) )}
					{$exception.end|datetime( 'mugocalendarday' )}
				{/if}
				{$exception.end|datetime( 'mugocalendartime' )}
			{/if}
		{* skipping event *}
		{else}
			{if is_null( $exception.instance )|not()}
				{def $parts = $exception.instance|explode( '-' )}
				Skipping event on {$parts.0|datetime( 'mugocalendarday' )}
			{/if}
		{/if}
	{else}
		<ul>
			{foreach $content as $event}
				<li>
					{if eq( $event.type, 'recurring' )}
						<h4>Recurring event:</h4>
						{$event.recurrence.description|wash()}<br />
					{else}
						<h4>Single event:</h4>
					{/if}

					{if $event.all_day_event}
						{$event.start|datetime( 'mugocalendarday' )}
						{if ne( $event.start, $event.end )}
							to
							{$event.end|datetime( 'mugocalendarday' )}
						{/if}
					{else}
						{$event.start|datetime( 'mugocalendarday' )}
						{$event.start|datetime( 'mugocalendartime' )}
						-
						{* Do not repeat day if it's the same day *}
						{if ne( $event.start|datetime( 'mugocalendarday' ), $event.end|datetime( 'mugocalendarday' ) )}
							{$event.end|datetime( 'mugocalendarday' )}
						{/if}
						{$event.end|datetime( 'mugocalendartime' )}
					{/if}
				</li>
			{/foreach}
		</ul>
	{/if}
</div>
