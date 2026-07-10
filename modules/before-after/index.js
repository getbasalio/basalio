/**
 * Editor UI for Basalio: Before / After.
 *
 * Uses the shared BasalioEd panel chrome with two clearly-labelled image slots.
 * `save` returns null, rendered dynamically on the server (render.php).
 */
( function ( wp ) {
	'use strict';

	var el                = wp.element.createElement;
	var __                = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;

	var be               = wp.blockEditor;
	var useBlockProps    = be.useBlockProps;
	var InspectorControls = be.InspectorControls;
	var MediaUpload      = be.MediaUpload;
	var MediaUploadCheck = be.MediaUploadCheck;

	var cmp          = wp.components;
	var PanelBody    = cmp.PanelBody;
	var Button       = cmp.Button;
	var TextControl  = cmp.TextControl;
	var RangeControl = cmp.RangeControl;

	var BE    = window.BasalioEd || {};
	var Panel = BE.Panel || function ( o, b ) { return el( 'div', { className: 'basalio-ed-panel' }, b ); };
	var Tip   = BE.Tip || function ( t ) { return el( 'p', null, t ); };

	registerBlockType( 'basalio/before-after', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;
			var pos  = typeof a.startPosition === 'number' ? a.startPosition : 50;

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

			function slot( key, label, url, alt, onSelect ) {
				return el(
					'div',
					{ key: key, className: 'basalio-ed-field' },
					el( 'span', { className: 'basalio-ed-label' }, label ),
					url ? el( 'img', { className: 'basalio-ed-preview', src: url, alt: alt || '', style: { marginBottom: '8px' } } ) : null,
					el( MediaUploadCheck, null,
						el( MediaUpload, {
							onSelect: onSelect,
							allowedTypes: [ 'image' ],
							render: function ( o ) {
								return el( Button, { variant: 'secondary', onClick: o.open }, url ? __( 'Replace image', 'basalio' ) : __( 'Upload image', 'basalio' ) );
							}
						} )
					)
				);
			}

			var body = [
				el( 'div', { key: 'imgs', className: 'basalio-ed-media-row' },
					slot( 'before', __( 'Before image', 'basalio' ), a.beforeImageUrl, a.beforeImageAlt, function ( m ) { setA( { beforeImageId: m.id, beforeImageUrl: m.url, beforeImageAlt: m.alt || '' } ); } ),
					slot( 'after', __( 'After image', 'basalio' ), a.afterImageUrl, a.afterImageAlt, function ( m ) { setA( { afterImageId: m.id, afterImageUrl: m.url, afterImageAlt: m.alt || '' } ); } )
				),
				el( TextControl, {
					key: 'blabel',
					label: __( 'Before label', 'basalio' ),
					value: a.beforeLabel || '',
					onChange: function ( v ) { setA( { beforeLabel: v } ); },
					placeholder: __( 'Before', 'basalio' )
				} ),
				el( TextControl, {
					key: 'alabel',
					label: __( 'After label', 'basalio' ),
					value: a.afterLabel || '',
					onChange: function ( v ) { setA( { afterLabel: v } ); },
					placeholder: __( 'After', 'basalio' )
				} ),
				el( RangeControl, {
					key: 'start',
					label: __( 'Start position (%)', 'basalio' ),
					value: pos,
					min: 0,
					max: 100,
					onChange: function ( v ) { setA( { startPosition: v } ); },
					help: __( 'Where the divider sits when the page loads.', 'basalio' )
				} ),
				Tip( __( 'Visitors drag the divider, or focus it and use the arrow keys, to compare the two images. Works with a mouse, touch, and keyboard.', 'basalio' ) )
			];

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Accessibility', 'basalio' ), initialOpen: false },
					el( TextControl, {
						label: __( 'Before image alt text', 'basalio' ),
						value: a.beforeImageAlt || '',
						onChange: function ( v ) { setA( { beforeImageAlt: v } ); }
					} ),
					el( TextControl, {
						label: __( 'After image alt text', 'basalio' ),
						value: a.afterImageAlt || '',
						onChange: function ( v ) { setA( { afterImageAlt: v } ); }
					} )
				)
			);

			return el(
				'div',
				blockProps,
				inspector,
				Panel(
					{
						name: __( 'Before / After', 'basalio' ),
						description: __( 'An interactive slider that reveals a “before” image over an “after” image.', 'basalio' )
					},
					body
				)
			);
		},

		save: function () { return null; }
	} );
}( window.wp ) );
