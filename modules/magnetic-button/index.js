/**
 * Editor UI for Basalio: Magnetic Button.
 *
 * Uses the shared BasalioEd panel chrome. `save` returns null, rendered
 * dynamically on the server (render.php).
 */
( function ( wp ) {
	'use strict';

	var el                = wp.element.createElement;
	var __                = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;

	var be            = wp.blockEditor;
	var useBlockProps = be.useBlockProps;

	var cmp           = wp.components;
	var TextControl   = cmp.TextControl;
	var RangeControl  = cmp.RangeControl;
	var ToggleControl = cmp.ToggleControl;
	var SelectControl = cmp.SelectControl;

	var BE    = window.BasalioEd || {};
	var Panel = BE.Panel || function ( o, b ) { return el( 'div', { className: 'basalio-ed-panel' }, b ); };
	var Tip   = BE.Tip || function ( t ) { return el( 'p', null, t ); };

	registerBlockType( 'basalio/magnetic-button', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

			var body = [
				el( TextControl, {
					key: 'text',
					label: __( 'Button text', 'basalio' ),
					value: a.text || '',
					onChange: function ( v ) { setA( { text: v } ); },
					placeholder: __( 'e.g. Join the beta', 'basalio' )
				} ),
				el( TextControl, {
					key: 'url',
					label: __( 'Link (URL)', 'basalio' ),
					value: a.url || '',
					onChange: function ( v ) { setA( { url: v } ); },
					placeholder: 'https://…',
					help: __( 'Where the button goes.', 'basalio' )
				} ),
				el( ToggleControl, {
					key: 'newtab',
					label: __( 'Open in new tab', 'basalio' ),
					checked: !! a.opensInNewTab,
					onChange: function ( v ) { setA( { opensInNewTab: v } ); }
				} ),
				el( RangeControl, {
					key: 'strength',
					label: __( 'Magnetic strength', 'basalio' ),
					value: typeof a.strength === 'number' ? a.strength : 18,
					min: 4,
					max: 40,
					onChange: function ( v ) { setA( { strength: v } ); },
					help: __( 'How far the button pulls toward the pointer.', 'basalio' )
				} ),
				el( SelectControl, {
					key: 'align',
					label: __( 'Alignment', 'basalio' ),
					value: a.alignment || 'left',
					options: [
						{ label: __( 'Left', 'basalio' ), value: 'left' },
						{ label: __( 'Center', 'basalio' ), value: 'center' },
						{ label: __( 'Right', 'basalio' ), value: 'right' }
					],
					onChange: function ( v ) { setA( { alignment: v } ); },
					help: __( 'Where the button sits within its width.', 'basalio' )
				} ),
				Tip( __( 'The pull is pointer-only. On touch screens and for anyone using reduced motion, it stays a normal, keyboard-friendly button.', 'basalio' ) )
			];

			return el(
				'div',
				blockProps,
				Panel(
					{
						name: __( 'Magnetic Button', 'basalio' ),
						description: __( 'A call-to-action button that gently pulls toward the visitor’s cursor.', 'basalio' )
					},
					body
				)
			);
		},

		save: function () { return null; }
	} );
}( window.wp ) );
