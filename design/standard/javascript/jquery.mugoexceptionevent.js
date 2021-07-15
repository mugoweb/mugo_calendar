;(function ( $, window, document, undefined )
{
    var pluginName = 'mugoexceptionevent',
        pluginElement = null,
        defaults =
        {
            eZBaseUrl: '',
            data: null,
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
        init : function()
        {
            var self = this;

            $(self.element).find( '.datepair' ).mugodatepair(
                {
                    as24clock: true,
                    data: self.options.data,
                    timeStep: self.options.timeStep,
                }
            );

            self.initEvents();
            self.setValues( self.options.data );
        },

        initEvents : function()
        {
            var self = this;

            // type changed
            $( self.element ).find( 'select.type' ).change( function()
            {
                var context = $(this).closest( 'div' );

                if( $( this ).val() == 'skip' )
                {
                    context.find( '.new-dates' ).addClass( 'd-none' );
                }
                else
                {
                    context.find( '.new-dates' ).removeClass( 'd-none' );
                }
            });

            // all day checkbox
            $( self.element ).find( '.allDay' ).change( function()
            {
                var $checkbox = $(this);
                var $context = $checkbox.closest( 'li.entry' );
                var targets = $context.find( '.time' );

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
                        $(this)
                            .val( $(this).attr( 'data-org-value' ) )
                            .show();
                    }

                });
            });
        },

        setValues : function( event )
        {
            var self = this;

            $(self.element).find( '.instance' ).val( event.for );

            if( event.start )
            {
                $(self.element).find( '.type' )
                    .val( 'change' )
                    .change();
            }

            return;
        },
    };

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