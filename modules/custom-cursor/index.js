/**
 * Editor UI for Basalio: Custom Cursor.
 *
 * A container (InnerBlocks) with cursor settings. `save` returns InnerBlocks.Content
 * so the inner content persists; render.php wraps it on the front end.
 */
( function ( wp ) {
	'use strict';

	var el                  = wp.element.createElement;
	var __                  = wp.i18n.__;
	var registerBlockType   = wp.blocks.registerBlockType;

	var be                  = wp.blockEditor;
	var useBlockProps        = be.useBlockProps;
	var useInnerBlocksProps  = be.useInnerBlocksProps;
	var InnerBlocks          = be.InnerBlocks;
	var MediaUpload          = be.MediaUpload;
	var MediaUploadCheck     = be.MediaUploadCheck;

	var cmp           = wp.components;
	var RangeControl  = cmp.RangeControl;
	var TextControl   = cmp.TextControl;
	var SelectControl = cmp.SelectControl;
	var Button        = cmp.Button;

	var BE    = window.BasalioEd || {};
	var Panel = BE.Panel || function ( o, b ) { return el( 'div', { className: 'basalio-ed-panel' }, b ); };
	var Tip   = BE.Tip || function ( t ) { return el( 'p', null, t ); };

	registerBlockType( 'basalio/custom-cursor', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;
			var size = typeof a.size === 'number' ? a.size : 30;

			var blockProps = useBlockProps( { className: 'basalio-editor' } );
			var innerProps = useInnerBlocksProps(
				{ className: 'basalio-custom-cursor__content' },
				{ template: [ [ 'core/paragraph', { placeholder: __( 'Add the content this cursor should apply to…', 'basalio' ) } ] ] }
			);

			var body = [
				el( RangeControl, {
					key: 'size',
					label: __( 'Accent size (px)', 'basalio' ),
					value: size,
					min: 10,
					max: 80,
					onChange: function ( v ) { setA( { size: v } ); }
				} ),
				el( TextControl, {
					key: 'color',
					label: __( 'Accent color (hex)', 'basalio' ),
					value: a.color || '',
					onChange: function ( v ) { setA( { color: v } ); },
					placeholder: '#DFFF00'
				} ),
				el( SelectControl, {
					key: 'style',
					label: __( 'Accent shape', 'basalio' ),
					value: a.cursorStyle === 'dot' ? 'dot' : 'ring',
					options: [
						{ label: __( 'Ring (hollow, sees through)', 'basalio' ), value: 'ring' },
						{ label: __( 'Dot (solid)', 'basalio' ), value: 'dot' }
					],
					onChange: function ( v ) { setA( { cursorStyle: v } ); },
					help: a.cursorImageUrl ? __( 'Ignored while a cursor image is set.', 'basalio' ) : undefined
				} ),
				el( 'div', { key: 'curimg', className: 'basalio-ed-field' },
					el( 'span', { className: 'basalio-ed-label' }, __( 'Cursor image / SVG (optional)', 'basalio' ) ),
					a.cursorImageUrl ? el( 'img', { src: a.cursorImageUrl, alt: '', style: { width: '44px', height: '44px', objectFit: 'contain', display: 'block', marginBottom: '8px' } } ) : null,
					el( MediaUploadCheck, null,
						el( MediaUpload, {
							onSelect: function ( m ) { setA( { cursorImageId: m.id, cursorImageUrl: m.url } ); },
							allowedTypes: [ 'image' ],
							value: a.cursorImageId,
							render: function ( o ) { return el( Button, { variant: 'secondary', onClick: o.open }, a.cursorImageUrl ? __( 'Replace image', 'basalio' ) : __( 'Upload image', 'basalio' ) ); }
						} )
					),
					a.cursorImageUrl ? el( Button, { variant: 'link', isDestructive: true, style: { marginTop: '6px' }, onClick: function () { setA( { cursorImageId: undefined, cursorImageUrl: '' } ); } }, __( 'Remove image', 'basalio' ) ) : null,
					el( 'span', { className: 'basalio-ed-hint' }, __( 'Replaces the ring/dot. To use an .svg, your site must allow SVG uploads.', 'basalio' ) )
				),
				Tip( __( 'The accent appears on the live page when a visitor moves the mouse inside this area. It is off on touch and under reduced motion, and never blocks a click.', 'basalio' ) ),
				el( 'div', { key: 'inner', style: { marginTop: '14px' } }, el( 'div', innerProps ) )
			];

			return el(
				'div',
				blockProps,
				Panel(
					{
						name: __( 'Custom Cursor', 'basalio' ),
						description: __( 'Shows a branded cursor accent that follows the pointer inside this area.', 'basalio' )
					},
					body
				)
			);
		},

		save: function () {
			return el( InnerBlocks.Content );
		}
	} );
}( window.wp ) );
