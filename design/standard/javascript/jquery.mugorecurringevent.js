;(function ( $, window, document, undefined )
{
    var pluginName = 'mugorecurringevent',
        pluginElement = null,
        defaults =
        {
            eZBaseUrl: '',
            events: null,
            isAllDay: false,
            as24clock: true,
            timeStep: 30,
        };

    function Plugin( element, options )
    {
        pluginElement = element;
        this.element = element;
        this.options = $.extend( {}, defaults, options) ;
        this._defaults = defaults;
        this._name = pluginName;

        this.init();
    }

    Plugin.prototype =
    {
        loading: true,

        init : function()
        {
            var self = this;

            self.initEvents();
            self.setValues();

            // add empty occurrence
            if( $( '.occurrences .entry:not( .template )' ).length == 0 )
            {
                $( self.element ).find( '.add-occurrence' ).click();
            }

            self.loading = false;
        },

        initEvents : function()
        {
            var self = this;

            // occurrence type changed
            $( self.element ).find( 'select.type' ).change( function()
            {
                var context = $(this).closest( 'li' );

                if( $( this ).val() == 'recurring' )
                {
                    context.find( '.recurrence-options' ).removeClass( 'd-none' );
                    context.find( '.recurrence-type' ).change();
                }
                else
                {
                    context.find( '.recurrence-options' ).addClass( 'd-none' );
                }
            });

            // ends
            $( self.element ).find( '.ends select' ).change( function()
            {
                var context = $(this).closest( '.ends' );

                if( $(this).val() == 'on' )
                {
                    context.find( '.rangeEnd' ).removeClass( 'd-none' );
                }
                else
                {
                    context.find( '.rangeEnd' )
                        .val( '' )
                        .addClass( 'd-none' );
                }
            });

            // sub-options - generic
            $( self.element ).find( '.sub-options' ).change( function()
            {
                $( this ).parent().find( '> .sub-option' ).hide();
                $( this ).parent().find( '> .sub-option-' + $( this ).val() ).show();
                $( this ).parent().find( '> .sub-option .sub-options' ).change();
            });

            // TODO: check if selector is concrete enough
            // definition changes
            $( self.element ).find( '.recurrence-options input, .recurrence-options select' ).change( function()
            {
                 var context = $(this).closest( 'li.entry' );
                 self.checkForFirstOccurrenceMismatch( context );
            });

            $( self.element ).find( '.remove-occurrence' ).click( function()
            {
                $( this ).closest( 'li' ).remove();
                self._afterOccuranceChanged();
            });

            $( self.element ).find( '.add-occurrence' ).click( function()
            {
                self._addOccurrence();
            });
        },

        setValues : function()
        {
            var self = this;

            $.each( self.options.events, function()
            {
                self._addOccurrence( this );
            });
        },

        _addOccurrence : function( values )
        {
            var self = this;

            var occurrence = $( self.element ).find( '.occurrences li.template' ).clone( true );

            $.each( occurrence.find( '[data-name]' ), function()
            {
                $( this )
                    .attr( 'name', $( this ).attr( 'data-name' ) )
                    .removeAttr( 'data-name' );
            });

            occurrence
                .removeClass( 'template' )
                .appendTo( $( self.element ).find( '.occurrences ul' ) );

            occurrence.find( '.datepair' ).mugodatepair(
            {
                as24clock: self.options.as24clock,
                data: values,
                mugoRecurringEvent : self,
                timeStep: self.options.timeStep,
            } );

            // recurring occurrences
            if( values && values.type == 2 )
            {
                occurrence.find( '.type' )
                    .val( 'recurring' ) //TODO: work with int values
                    .change();

                occurrence.find( '.interval' ).val( values.interval );
                occurrence.find( '.recurrenceType' )
                    .val( values.recurrenceType )
                    .change();

                occurrence.find( '.weeklyWeekDay' ).val( values.weeklyWeekDay );
                occurrence.find( '.monthlyType' )
                    .val( values.monthlyType )
                    .change();

                occurrence.find( '.day' ).val( values.day );
                occurrence.find( '.monthlyWeekDay' ).val( values.monthlyWeekDay );

                if( values.rangeEnd )
                {
                    occurrence.find( '.rangeEnd' ).val( parseDateDay( values.rangeEnd ) );
                    occurrence.find( '.rangeEndType' )
                        .val( 'on' )
                        .change();
                }
            }
            else
            {
                // init selection
                occurrence.find( '.recurrenceType' ).change();
            }

            self.checkForFirstOccurrenceMismatch( occurrence );
            self._afterOccuranceChanged();
        },

        _afterOccuranceChanged : function()
        {
            var self = this;

            if( $( self.element ).find( '.occurrences li' ).length > 2 )
            {
                $( self.element ).find( '.occurrences li.empty' ).addClass( 'd-none');
            }
            else
            {
                $( self.element ).find( '.occurrences li.empty' ).removeClass( 'd-none');
            }
        },

        checkForFirstOccurrenceMismatch : function( $context )
        {
            var self = this;

            if( $context.find( '.type' ).val() == 'recurring' )
            {
                var recurrenceDefinition = self.getDefinition( $context );

                if( !self.loading )
                {
                    self.loading = true;

                    recurrenceDefinition[ 'limit' ] = 1;

                    var request = $.ajax(
                    {
                        url: self.options.eZBaseUrl + '/mugo_calendar/resolve_recurrence',
                        dataType: 'json',
                        data: recurrenceDefinition,
                    });

                    request.done( function( response )
                    {
                        if( response && response.length )
                        {
                            var event = response[ 0 ];

                            var firstOccurrence = parseDateDay( event.start );
                            var selectStartDay = parseDateDay( { date : recurrenceDefinition[ 'start' ] } );

                            if( firstOccurrence != selectStartDay )
                            {
                                $context.find( '.occurrence-missmatch .date' ).text( firstOccurrence );
                                $context.find( '.occurrence-missmatch' ).removeClass( 'd-none' );
                            }
                            else
                            {
                                $context.find( '.occurrence-missmatch' ).addClass( 'd-none' );
                            }
                        }
                    });

                    request.always( function()
                    {
                        self.loading = false;
                    });
                }
            }
        },

        getDefinition : function( context )
        {
            var self = this;

            var definition =
            {
                'type': context.find( '.recurrenceType' ).val(),
                'interval': context.find( '.interval' ).val(),
                'weeklyWeekDay' : context.find( '.weeklyWeekDay' ).val(),
                'monthlyType' : context.find( '.monthlyType' ).val(),
                'day' : context.find( '.day' ).val(),
                'monthlyWeekDay' : context.find( '.monthlyWeekDay' ).val(),
                'start' : context.find( '.start.date' ).val() + ' ' +  context.find( '.start.time' ).val(),
                'end' : context.find( '.end.date' ).val() + ' ' +  context.find( '.end.time' ).val(),
                'rangeEnd' : context.find( '.rangeEnd' ).val()
            };

            return definition;
        },
    };

    function parseDateDay( dateObj )
    {
        return dateObj.date.substr( 0, 10 )
    }

    $.fn[pluginName] = function ( options ) {
        var args = arguments;

        if (options === undefined || typeof options === 'object') {
            return this.each(function ()
            {
                if (!$.data(this, 'plugin_' + pluginName)) {

                    $.data(this, 'plugin_' + pluginName, new Plugin( this, options ));
                }
            });

        } else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {

            var returns;

            this.each(function () {
                var instance = $.data(this, 'plugin_' + pluginName);

                if (instance instanceof Plugin && typeof instance[options] === 'function') {

                    returns = instance[options].apply( instance, Array.prototype.slice.call( args, 1 ) );
                }

                // Allow instances to be destroyed via the 'destroy' method
                if (options === 'destroy') {
                    $.data(this, 'plugin_' + pluginName, null);
                }
            });

            return returns !== undefined ? returns : this;
        }
    };

}(jQuery, window, document));