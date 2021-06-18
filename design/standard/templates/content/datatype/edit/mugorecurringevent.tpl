{ezscript_require( array(
    'jquery.mugorecurringevent.js',
    'jquery.mugoexceptionevent.js',
    'jquery.mugocalendar.js',
    'jquery.timepicker.min.js',
    'jquery.mugodatepair.js',
    'jquery.datepair.min.js',
    'datepair.js',
    'moment.min.js'
) )}
{ezcss_require( array(
    'mugo_calendar.css',
    'jquery.timepicker.css',
    'bootstrap_transition.css'
))}

{if is_unset( $attribute_base )}
    {def $attribute_base='ContentObjectAttribute'}
{/if}

{* TODO: $weekOffset not used in week days drop downs *}
{def
    $content = $attribute.content
    $weekOffset = ezini( 'Calendar', 'WeekOffset', 'mugo_calendar.ini' )
    $timeStep = ezini( 'Calendar', 'TimeStep', 'mugo_calendar.ini' )
    $weekDays = array(
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
    )
}

{* find parent node *}
{def $parent = $attribute.object.main_node.parent}

{if $parent|not()}
    {set $parent = fetch( 'content', 'node', hash( 'node_id', $attribute.object.current.main_parent_node_id ) )}
{/if}

{def $isException = $attribute.object|is_exception()}

<div class="mugorecurringevent" id="ezp-attribute-id-{$attribute.id}">

    {* same class for parent -- assume this is an exception *}
    {if $isException}

        {* exceptions only have a single event entry *}
        {if $content}
            {set $content = $content[0]}
        {/if}

        {def
            $events = fetch( 'mugo_calendar', 'resolve_recurrence', hash(
                'events', $parent.data_map[ $attribute.contentclass_attribute_identifier ].content,
                'start', currentdate()|sub( 31536000 )|datetime( 'mugocalendarday' ),
                'limit', 150,
                'with_exceptions', false(),
                ) )
            $parentContentObjectId = $parent.contentobject_id
        }

        <div class="exception bootstrap">
            <input
                type="hidden"
                name="{$attribute_base}_mugorecurringevent_{$attribute.id}[parentContentObjectId]"
                value="{$parentContentObjectId}"
            />

            {* $parent_event|dump() *}
            <p>
                Select the date you want to change

                <select class="instance" name="{$attribute_base}_mugorecurringevent_{$attribute.id}[instance]">
                    <option value="">- select -</option>
                    {foreach $events as $id => $event}
                        <option value="{$id}">{$event.start|datetime( 'mugocalendarday' )}</option>
                    {/foreach}
                </select>
            </p>

            <div>
                <p>
                    I want to
                    <select class="type" name="{$attribute_base}_mugorecurringevent_{$attribute.id}[type]">
                        <option value="skip">skip</option>
                        <option value="change">change</option>
                    </select>
                    the event
                </p>

                <div class="new-dates d-none">
                    New event dates:

                    <div class="datepair">
                        <div>
                            <input
                                type="date"
                                name="{$attribute_base}_mugorecurringevent_{$attribute.id}[exceptionstartdate]"
                                class="start date"
                                value=""
                            />
                            <input
                                name="{$attribute_base}_mugorecurringevent_{$attribute.id}[exceptionstarttime]"
                                class="start time"
                                value=""
                                data-org-value=""
                            />
                            to
                            <input
                                type="date"
                                name="{$attribute_base}_mugorecurringevent_{$attribute.id}[exceptionenddate]"
                                class="end date"
                                value=""
                            />
                            <input
                                name="{$attribute_base}_mugorecurringevent_{$attribute.id}[exceptionendtime]"
                                class="end time"
                                value=""
                                data-org-value=""
                            />
                            <div class="allDay form-group form-check">
                                <input
                                    data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[allday]"
                                    type="hidden"
                                    value=""
                                />
                                <input class="form-check-input" type="checkbox" />
                                <label class="form-check-label">All day</label>
                            </div>
                        </div>
                        <div class="duration" style="padding-left: 170px">
                            <i>Duration is <span class="timeDescription"></span></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        $( '#ezp-attribute-id-{$attribute.id} .exception' ).mugoexceptionevent(
            {ldelim}
                data: {json_encode( $content )},
                timeStep: '{$timeStep}',
            {rdelim});
        </script>
    {else}
        <div class="bootstrap">
            <div class="occurrences">
                <ul class="list-unstyled">
                    <li class="template entry mb-3">
                        <hr />
                        <div class="clearfix">
                            <div class="float-left">
                                <button type="button" class="btn btn-danger remove-occurrence">X</button>
                            </div>
                            <div class="float-left">

                                <div class="occurrence-time">
                                    <div class="occurrence-missmatch d-none">Warning: first occurrence is on <span class="date"></span>.</div>
                                    <div class="datepair">
                                        <div>
                                            <input
                                                type="date"
                                                data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[startdate][]"
                                                class="start date form-control"
                                                value=""
                                            />
                                            <input
                                                data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[starttime][]"
                                                class="start time form-control"
                                                value=""
                                            />
                                            to
                                            <input
                                                type="date"
                                                data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[enddate][]"
                                                class="end date form-control"
                                                value=""
                                            />
                                            <input
                                                data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[endtime][]"
                                                class="end time form-control"
                                                value=""
                                            />
                                            <div class="allDay form-group form-check">
                                                <input
                                                    data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[allday][]"
                                                    type="hidden"
                                                    value=""
                                                />
                                                <input class="form-check-input" type="checkbox" />
                                                <label class="form-check-label">All day</label>
                                            </div>
                                        </div>
                                        <div class="duration mb-1">
                                            <i>Duration is <span class="timeDescription"></span></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="options">

                                    <select class="type form-control" data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[recurrence_type][]">
                                        <option value="single">Single</option>
                                        <option value="recurring">Recurring</option>
                                    </select>

                                    <span class="recurrence-options d-none">
                                        every
                                        <select class="interval form-control" data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[interval][]">
                                            <option>1</option>
                                            <option>2</option>
                                            <option>3</option>
                                            <option>4</option>
                                            <option>5</option>
                                            <option>6</option>
                                            <option>7</option>
                                            <option>8</option>
                                            <option>9</option>
                                            <option>10</option>
                                        </select>

                                        <select class="recurrenceType sub-options form-control" data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[type][]">
                                            <option value="Weekly">week(s)</option>
                                            <option value="Monthly">month(s)</option>
                                        </select>

                                        <span class="sub-option sub-option-Weekly">
                                            on
                                            <select class="weeklyWeekDay form-control" data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[weeklyWeekDay][]">
                                                {foreach $weekDays as $id => $day}
                                                    <option value="{$id}">{$day}</option>
                                                {/foreach}
                                            </select>
                                        </span>

                                        <span class="sub-option sub-option-Monthly">
                                            on
                                            <select class="sub-options monthlyType form-control" data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[monthlyType][]">
                                                <option value="day">day</option>
                                                <option value="first">the first</option>
                                                <option value="second">the second</option>
                                                <option value="third">the third</option>
                                                <option value="fourth">the fourth</option>
                                                <option value="last">the last</option>
                                            </select>

                                            <span class="sub-option sub-option-day">
                                                <select class="day form-control" data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[day][]">
                                                    <option>1</option>
                                                    <option>2</option>
                                                    <option>3</option>
                                                    <option>4</option>
                                                    <option>5</option>
                                                    <option>6</option>
                                                    <option>7</option>
                                                    <option>8</option>
                                                    <option>9</option>
                                                    <option>10</option>
                                                    <option>11</option>
                                                    <option>12</option>
                                                    <option>13</option>
                                                    <option>14</option>
                                                    <option>15</option>
                                                    <option>16</option>
                                                    <option>17</option>
                                                    <option>18</option>
                                                    <option>19</option>
                                                    <option>20</option>
                                                    <option>21</option>
                                                    <option>22</option>
                                                    <option>23</option>
                                                    <option>24</option>
                                                    <option>25</option>
                                                    <option>26</option>
                                                    <option>27</option>
                                                    <option>28</option>
                                                    <option>29</option>
                                                    <option>30</option>
                                                    <option>31</option>
                                                </select>
                                            </span>

                                            <span class="sub-option sub-option-first sub-option-second sub-option-third sub-option-fourth sub-option-last">
                                                <select class="monthlyWeekDay form-control" data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[monthlyWeekDay][]">
                                                    {foreach $weekDays as $id => $day}
                                                        <option value="{$id}">{$day}</option>
                                                    {/foreach}
                                                </select>
                                            </span>
                                        </span>

                                        <span class="ends">
                                            ends
                                            <select class="rangeEndType form-control">
                                                <option value="never">never</option>
                                                <option value="on">on</option>
                                                {* <option value="number">after x events</option> *}
                                            </select>

                                            <input
                                                class="d-none rangeEnd form-control"
                                                data-name="{$attribute_base}_mugorecurringevent_{$attribute.id}[rangeEnd][]"
                                                type="date"
                                            />
                                        </span>
                                    </span>
                                </div>

                            </div>
                        </div>
                    </li>
                    <li class="empty d-none">No occurrence defined - please add one.</li>
                </ul>
                <hr />
                <button type="button" class="button add-occurrence">Add date rule</button>
            </div>
        </div>

        <script>
            $( '#ezp-attribute-id-{$attribute.id}' ).mugorecurringevent(
                {ldelim}
                    events: {json_encode( $content )},
                    timeStep: '{$timeStep}',
                {rdelim});
        </script>
    {/if}
</div>
