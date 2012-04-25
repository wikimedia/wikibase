/**
 * JavasSript for a part of an editable property value
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.Interface.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Serves the input interface for a part of a value like a property value and also takes care of the
 * conversion between the pure html representation and the interface itself in both directions
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.Interface = function( subject, editableValue ) {
	if( typeof subject != 'undefined' && typeof editableValue != 'undefined' ) {
		this._init( subject, editableValue );
	}
};
window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype = {
	/**
	 * @const
	 * Class which marks the element within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittoolbar-editablevalueinterface',

	/**
	 * Reference to parent editableValue
	 * @var wikibase.ui.PropertyEditTool.EditableValue
	 */
	_editableValue: null,

	/**
	 * Element representing the editable value. This element will either hold the value or the input
	 * box in case it is activated for edit.
	 * @var jQuery
	 */
	_subject: null,

	/**
	 * This is true if the input interface is initialized at the time.
	 * @var bool
	 */
	_isInEditMode: false,
	
	/**
	 * If true, the input interface will be loaded on startEditing(), otherwise the value will remain
	 * uneditable.
	 * @var bool
	 */
	_isActive: true,

	/**
	 * Holds the input element in case this is in edit mode
	 * @var null|jQuery
	 */
	_inputElem: null,

	_currentWidth: null,

	/**
	 * Initializes the editable value.
	 * This should normally be called directly by the constructor.
	 *
	 * @param jQuery subject
	 */
	_init: function( subject, editableValue ) {
		if( this._subject !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._subject = $( subject );
		this._editableValue = editableValue;
		this._currentWidth = 0;
	},

	destroy: function() {
		if( this._isInEditMode ) {
			this.stopEditing( false );
		}
	},

	/**
	 * By calling this, the editable value will be made editable for the user.
	 * Call stopEditing() to save or cancel the editing process.
	 * Basically this initializes the input box as sub element of the subject and uses the
	 * elements content as initial text.
	 *
	 * @return bool will return false if edit mode is active already.
	 */
	startEditing: function() {
		if( this.isInEditMode() || !this.isActive() ) {
			return false;
		}
		
		// initializes the input element into the DOM and removes the html representation
		this._initInputElement();

		// auto expand dummy element to messure text lenght:
		if ( this.autoExpand ) {
			var ruler = $( '<span/>', {
				'class': 'ruler'
			} );
			this._inputElem.after( ruler );
		}

        this._isInEditMode = true;
		
		this._onInputRegistered(); // do this after setting _isInEditMode !
        this.setFocus();
		
		return true;
	},
	
	/**
	 * Initializes the input element and appends it into the DOM when needed.
	 */
	_initInputElement: function() {
		this._inputElem = this._buildInputElement();
		if( this.isDisabled() ) {
			// disable element properly if disabled from before edit mode
			this._disableInputElement();
		}
		
		// store original text value from before input box insertion:
		this._inputElem.data( this.UI_CLASS + '-initial-value', this.getValue() );
		
		var inputParent = this._getValueContainer();
		inputParent.text( '' );
		this._inputElem.appendTo( inputParent );
	},
	
	/**
	 * returns the input element for editing
	 * @return jQuery
	 */
	_buildInputElement: function() {
		return $( '<input/>', {
			'class': this.UI_CLASS,
			'type': 'text',
			'name': this._key,
			'value': this.getValue(),
			'placeholder': this.inputPlaceholder,
			'keypress': jQuery.proxy( this._onKeyPressed, this ), // TODO: this shouldn't be used, keyup should work fine!
			'keyup':    jQuery.proxy( this._onKeyUp, this ), //       we have both for escape key browser compability
			'keydown':  jQuery.proxy( this._onKeyDown, this ),
			'focus':    jQuery.proxy( this._onFocus, this ),
			'blur':     jQuery.proxy( this._onBlur, this )
		} );
	},
	
	/**
	 * Returns the node holding the value. This node will also hold the input box when in edit mode.
	 * @return jQuery
	 */
	_getValueContainer: function() {
		return this._subject;
	},

	/**
	 * Called when the input changes in general for example on its initialization when setting
	 * its initial value.
	 */
	_onInputRegistered: function() {
		if( this.onInputRegistered !== null && this.onInputRegistered() === false ) { // callback
			return false; // cancel
		}
	},

	_expand: function() {
		if ( this.autoExpand ) {
			var ruler = this._subject.find( '.ruler' );
			//console.log( '"'+ this._inputElem.attr( 'value' ).replace( / /g, '&nbsp;') + '"' );

			var currentValue = this._inputElem.val();
			if ( currentValue === '' ) {
				currentValue = this._inputElem.attr( 'placeholder' );
			}
			ruler.html( currentValue.replace( / /g, '&nbsp;' ).replace( /</g, '&lt;' ) ); // TODO prevent insane HTML from being placed in the ruler
			var inputWidth = this._inputElem.width();

			// get max width
			var maxWidth = this._subject.parent().width();

			// get new current width
			this._subject.parent().css( 'display', 'inline-block' );
			var currentWidth = this._subject.parent().width();
			this._subject.parent().css( 'display', 'block' );

			//console.log(maxWidth);
			//console.log(currentWidth);
			//console.log(this._inputElem.width());
			/*
			if ( currentWidth > maxWidth && !(ruler.width() < maxWidth) ) {
				this._inputElem.css( 'width', maxWidth - 1 );
			} else {
				this._inputElem.css( 'width', ( ruler.width() + 25 ) + 'px' );
			}*/

			// TODO use additional parent element to measer width of (input + toolbar)
			if ( this._inputElem.width() > this._subject.parent().width() - 250 && !(ruler.width() < this._subject.parent().width() - 250) ) {
				this._inputElem.css( 'width', this._subject.parent().width() - 249 );
			} else {
				this._inputElem.css( 'width', ( ruler.width() + 25 ) + 'px' ); // TODO better resize mechanism (maybe by temporarily replacing text input)
			}


			if ( typeof this._editableValue._toolbar._items[0].tooltip._tipsy.$tip != 'undefined' ) {
				var tooltipLeft = parseInt( this._editableValue._toolbar._items[0].tooltip._tipsy.$tip.css( 'left' ) );
				this._editableValue._toolbar._items[0].tooltip._tipsy.$tip.css( 'left', ( tooltipLeft + this._inputElem.width() - inputWidth ) + 'px' );
			}

		}
	},

	/**
	 * Called when a key is pressed inside the input interface
	 */
	_onKeyPressed: function( event ) {
		this._onInputRegistered(); // TODO: do not fire this if input hasn't changed
		if( this.onKeyPressed !== null && this.onKeyPressed( event ) === false ) { // callback
			return false; // cancel
		}
	},

	_onKeyUp: function( event ) {
		this._onInputRegistered(); // TODO: do not fire this if input hasn't changed
		this._expand();
		if( this.onKeyUp !== null && this.onKeyUp( event ) === false ) { // callback
			return false; // cancel
		}
	},

	_onKeyDown: function( event ) {
		if( this.onKeyDown !== null && this.onKeyDown( event ) === false ) { // callback
			return false; // cancel
		}
	},

	_onFocus: function( event ) {
		this._expand();
		if( this.onFocus !== null ) {
			this.onFocus( event ); // callback
		}
	},
	_onBlur: function( event ) {
		if( this.onBlur !== null ) {
			this.onBlur( event ); // callback
		}
	},

	/**
	 * Destroys the edit box and displays the original text or the inputs new value.
	 *
	 * @param bool save whether to save the new user given value
	 * @return bool whether the value has changed compared to the original value
	 */
	stopEditing: function( save ) {
		if( ! this.isInEditMode() ) {
			return false;
		}
		var initialValue = this.getInitialValue();
		
		var $value = save ? this.getValue() : initialValue;
		
		this._inputElem.empty().remove(); // remove input interface
		this._inputElem = null;
		
		this._isInEditMode = false;
		this.setValue( $value );
		
		// any change at all compared to initial value?
		return initialValue !== $value;
	},

	/**
	 * Sets the focus to the input interface
	 */
	setFocus: function() {
		if( this._inputElem !== null ) {
			this._inputElem.focus();
		}
	},

	/**
	 * Removes the focus from the input interface
	 */
	removeFocus: function() {
		if( this._inputElem !== null ) {
			this._inputElem.blur();
		}
	},

	/**
	 * Returns whether the input interface is loaded currently
	 *
	 * @return bool
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * Returns the current value
	 *
	 * @return string
	 */
	getValue: function() {
		var value = '';
		if( this.isInEditMode() ) {
			value = $( this._getValueContainer().children( '.' + this.UI_CLASS )[0] ).attr( 'value' );
		} else {
			value = this._getValueContainer().text();
		}
		return $.trim( value );
	},
	
	/**
	 * Sets a value
	 */
	setValue: function( value ) {
		if( this.isInEditMode() ) {
			this._inputElem.attr( 'value', value );
		} else {
			this._getValueContainer().text( value );
		}
	},
	
	/**
	 * Returns true if the interface is disabled.
	 * 
	 * @return bool
	 */
	isDisabled: function() {
		return this._subject.hasClass( this.UI_CLASS + '-disabled' );
	},
	
	/**
	 * Disables or enables the element. Disabled is still visible but will be presented differently
	 * and might behave differently in some cases.
	 * 
	 * @param bool disable true for disabling, false for enabling the element
	 * @return bool whether the state was changed or not.
	 */
	setDisabled: function( disable ) {
		// TODO!
		if( disable ) {
			this._subject.addClass( this.UI_CLASS + '-disabled' );			
			if( this.isInEditMode() ) {
				this._disableInputElement();
			}
		} else {
			this._subject.removeClass( this.UI_CLASS + '-disabled' );
			if( this.isInEditMode() ) {
				this._enableInputelement();
			}
		}
	},
	
	/**
	 * Returns whether the interface is deactivated or active. If it is deactivated, the input
	 * interface will not be made available on startEditing()
	 * 
	 * @return bool
	 */
	isActive: function() {
		return this._isActive;
	},
	
	/**
	 * Sets the interface active or inactive. If inactive, the interface will not be made available
	 * when startEditing() is called. If called to deactivate the interface but still in edit mode,
	 * the edit mode will be closed without saving.
	 * 
	 * @return bool whether the state was changed or not.
	 */
	setActive: function( active ) {		
		if( !active && this.isInEditMode() ) {
			this.stopEditing( false );
		}
		this._isActive = active;
	},
	
	_disableInputElement: function() {
		this._inputElem.attr( 'disabled', 'true' );
	},
	
	_enableInputelement: function() {
		this._inputElem.removeAttr( 'disabled' );
	},

	/**
	 * If the input is in edit mode, this will return the value active before the edit mode was entered.
	 * If its not in edit mode, the current value will be returned.
	 * @return string
	 */
	getInitialValue: function() {
		if( ! this.isInEditMode() ) {
			return this.getValue();
		}
		return this._inputElem.data( this.UI_CLASS + '-initial-value' );
	},

	/**
	 * Returns true if there is currently no value assigned
	 *
	 * @return bool
	 */
	isEmpty: function() {
		return this.getValue() === '';
	},

	/**
	 * Velidates whether a certain value would be valid for this editable value.
	 *
	 * @param string text
	 * @return bool
	 */
	validate: function( value ) {
		return $.trim( value ) !== '';
	},

	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * Allows to define a default value appearing in the input box in case there is no value given
	 * @var string
	 */
	inputPlaceholder: '',

	/**
	 * When true, automatically expands width of input element according to containing text
	 * @var bool
	 */
	autoExpand: false,

	///////////
	// EVENTS:
	///////////

	/**
	 * Callback called when the input changes in general for example on its initialization when
	 * setting its initial value.
	 */
	onInputRegistered: null,
	
	onKeyPressed: null,

	onKeyUp: null,

	onKeyDown: null,
	
	onFocus: null,
	
	onBlur: null
};
