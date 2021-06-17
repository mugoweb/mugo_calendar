// Script that helps to render a full calendar view, it's a wrapper to 'fullcalendar.min.js'

;(function( $, window, document, undefined )
{
    var pluginName = 'mugofullcalendar',
        pluginElement = null,
        defaults =
            {
                dataServiceUrl: '/mugo_calendar/fetch',
                parentNodeId: 2,
                weekOffset: 0,
                defaultParams: {},
                calendarConfigOverride: {},
            };

    function Plugin( element, options )
    {
        pluginElement = element;
        this.element = element;
        this.options = $.extend( {}, defaults, options );
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }

    Plugin.prototype =
        {
            init: function()
            {
                var self = this;
                var config = $.extend( {}, self.getDefaultConfig(), self.options.calendarConfigOverride );
                // Language identifier mapping
                if( config.locale == 'fr_CA' )
                {
                    config.locale = 'fr-ca';
                }
                $( self.element ).fullCalendar( config );
            },

            getDefaultConfig: function()
            {
                var self = this;

                return {
                    locale: 'fr-ca',
                    editable: false,
                    header:
                        {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'month,agendaWeek,agendaDay'
                        },
                    defaultView: 'month',
                    firstDay: self.options.weekOffset,
                    events: function( eventStart, eventEnd, timezone, callback )
                    {
                        var params = {};
                        params[ 'start' ] = eventStart.format();
                        params[ 'end' ] = eventEnd.format();
                        params[ 'parent_node_id' ] = self.options.parentNodeId;
                        for( var k in self.options.defaultParams ) params[ k ] = self.options.defaultParams[ k ];
                        $.ajax(
                            {
                                url: self.options.dataServiceUrl,
                                dataType: 'json',
                                data: params,
                                success: callback,
                            } );
                    },
                };
            },
        };

    $.fn[ pluginName ] = function( options )
    {
        var args = arguments;
        if( options === undefined || typeof options === 'object' )
        {
            return this.each( function()
            {
                if( !$.data( this, 'plugin_' + pluginName ) )
                {
                    $.data( this, 'plugin_' + pluginName, new Plugin( this, options ) );
                }
            } );
        }
        else if( typeof options === 'string' && options[ 0 ] !== '_' && options !== 'init' )
        {
            var returns;
            this.each( function()
            {
                var instance = $.data( this, 'plugin_' + pluginName );
                if( instance instanceof Plugin && typeof instance[ options ] === 'function' )
                {
                    returns = instance[ options ].apply( instance, Array.prototype.slice.call( args, 1 ) );
                }
                // Allow instances to be destroyed via the 'destroy' method
                if( options === 'destroy' )
                {
                    $.data( this, 'plugin_' + pluginName, null );
                }
            } );
            return returns !== undefined ? returns : this;
        }
    };
}( jQuery, window, document ));