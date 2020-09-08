;(function ( $, window, document, undefined )
{
    var pluginName = 'mugodatepair',
        pluginElement = null,
        defaults =
        {
            data: null,
            as24clock: true,
            mugoRecurringEvent: null,
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
        datePairInstance : null,

        init : function()
        {
            var self = this;

            // fancy time pickers
            $(self.element).find( '.time' ).timepicker(
            {
                showDuration: true,
                timeFormat: self.options.as24clock ? 'H:i' : 'h:ia',
                step: parseInt( self.options.timeStep ),
            });

            self.datePairInstance = $(self.element).datepair(
                {
                    // fires on all date/time changes
                    parseTime: function( input )
                    {
                        var m = moment(
                            input.value, self.options.as24clock ? 'HH:mm' : 'h:mm a'
                        );
                        return m.toDate();
                    },
                    parseDate: function(input)
                    {
                        return moment( $(input).val() ).toDate();
                    },
                    // only trigger on start time input
                    updateTime: function( input, dateObj )
                    {
                        // do not update input if it is not visible (all day events)
                        if( $( input ).is( ':visible' ) )
                        {
                            var m = moment( dateObj );
                            input.value = m.format( self.options.as24clock ? 'HH:mm' : 'h:mm a' );

                            $( input ).fadeOut( 100, function()
                            {
                                $(this).fadeIn( 1000 );
                            });

                            self.calculateDuration();
                        }
                    },
                    // only trigger on start day input
                    updateDate: function( input, dateObj )
                    {
                        // updating start date
                        if( $(input).hasClass( 'end' ) )
                        {
                            $(input).val( moment( dateObj ).format( 'YYYY-MM-DD' ) );

                            $( input ).fadeOut( 100, function()
                            {
                                $(this).fadeIn( 1000 );
                            });
                        }

                        self.calculateDuration();
                    }
                });

            self.datePairInstance.datepair().on( 'rangeSelected', function( plugin )
            {
                self.calculateDuration();
            });

            self.initEvents();
            self.setValues( self.options.data );
        },

        initEvents : function()
        {
            var self = this;

            // all day checkbox
            $( self.element ).find( '.allDay input:checkbox' ).change( function()
            {
                var $checkbox = $(this);
                var $input = $(self.element).find( '.allDay input:hidden' );

                if( $checkbox.prop( 'checked' ) )
                {
                    $input.val( '1' );
                }
                else
                {
                    $input.val( '0' );
                }

                // handle time fields
                var targets = $( self.element ).find( '.time' );

                $.each( targets, function()
                {
                    if( $checkbox.prop( 'checked' ) )
                    {
                        $(this)
                            .attr( 'data-org-value', $(this).val() )
                            .hide()
                            .val( '00:00' );
                    }
                    else
                    {
                        if( $(this).attr( 'data-org-value' ) )
                        {
                            //$(this).timepicker( 'setTime', new Date( $(this).attr( 'data-org-value' ) ) );
                            $(this).val( $(this).attr( 'data-org-value' ) )

                        }
                        $(this).show();
                    }

                });

                self.calculateDuration();
            });

            // recalculate date missmatch
            $( self.element ).find( '.start.date' ).change( function()
            {
                self.options.mugoRecurringEvent.checkForFirstOccurrenceMismatch(
                    $(self.element).closest( 'li.entry' )
                );
            });
        },

        setValues: function( values )
        {
            var self = this;

            if( values && values.start )
            {
                var startDate = values.start;
            }
            else
            {
                var currentDate = new Date();
                currentDate = roundMinutes( currentDate );

                var startDate = { date: currentDate.toISOString() };
            }

            if( values && values.end )
            {
                var endDate = values.end;
            }
            else
            {
                var currentDate = new Date();
                currentDate = roundMinutes( currentDate );
                currentDate.setTime( currentDate.getTime() + ( 60 * 60 * 1000 ) );
                var endDate = { date: currentDate.toISOString() };
            }

            $(self.element).find( '.start.date' ).val( parseDateDay( startDate ) );
            $(self.element).find( '.start.time' ).timepicker( 'setTime', new Date( startDate.date ) );

            $(self.element).find( '.end.date' ).val( parseDateDay( endDate ) );
            $(self.element).find( '.end.time' ).timepicker( 'setTime', new Date( endDate.date ) );

            if( values )
            {
                $(self.element).find( '.allDay input:checkbox' )
                    .prop( 'checked', values.isAllDay )
                    .change();
            }

            // triggers time diff calc
            $(self.element).find( '.end.date' ).change();
        },

        calculateDuration : function()
        {
            var self = this;

            var isAllDay = $(self.element).find( '.allDay input:checkbox' ).prop( 'checked' );

            var diff = self.datePairInstance.datepair( 'getTimeDiff' );

            // normalize return value
            if( typeof diff !== 'number' )
            {
                diff = 0;
            }

            // an invalid diff returns jquery instances
            $(self.element).find( '.timeDescription' ).text(
                msToTime(
                    diff,
                    isAllDay
                )
            );
        },

    };

    function parseDateDay( dateObj )
    {
        return dateObj.date.substr( 0, 10 )
    }

    function roundMinutes(date)
    {

        date.setHours(date.getHours() + Math.round(date.getMinutes()/60));
        date.setMinutes(0);

        return date;
    }

    /**
     *
     * @param int duration
     * @return string
     */
    function msToTime( duration, allDay )
    {
        var minutes = parseInt( (duration / (1000 * 60) ) % 60 );
        var hours = parseInt( ( duration / (1000 * 60 * 60) ) % 24 );
        var days = parseInt( ( duration / (1000 * 60 * 60 * 24 ) ) );

        if( allDay )
        {
            days++;
            days = (days == 0) ? '' : days + ' day' + ( (days > 1) ? 's' : '' );

            return days;
        }
        else
        {
            days = (days == 0) ? '' : days + ' day' + ( (days > 1) ? 's' : '' );
            hours = (hours == 0) ? '' : hours + ' hour' + ( (hours > 1) ? 's' : '' );
            minutes = (minutes == 0) ? '' : minutes + ' minutes';

            return days + ' ' + hours + ' ' + minutes;
        }
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