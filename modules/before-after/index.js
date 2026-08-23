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

	var cmp           = wp.components;
	var PanelBody     = cmp.PanelBody;
	var Button        = cmp.Button;
	var TextControl   = cmp.TextControl;
	var RangeControl  = cmp.RangeControl;
	var SelectControl = cmp.SelectControl;

	var RATIOS = [
		{ label: 'Original (match the images)', value: 'auto' },
		{ label: 'Landscape, 16:9', value: '16:9' },
		{ label: 'Landscape, 3:2', value: '3:2' },
		{ label: 'Landscape, 4:3', value: '4:3' },
		{ label: 'Ultrawide, 21:9', value: '21:9' },
		{ label: 'Square, 1:1', value: '1:1' },
		{ label: 'Portrait, 4:5', value: '4:5' },
		{ label: 'Portrait, 3:4', value: '3:4' },
		{ label: 'Portrait, 2:3', value: '2:3' },
		{ label: 'Tall, 9:16', value: '9:16' }
	];

	var BE    = window.BasalioEd || {};
	var Panel = BE.Panel || function ( o, b ) { return el( 'div', { className: 'basalio-ed-panel' }, b ); };
	var Tip   = BE.Tip || function ( t ) { return el( 'p', null, t ); };

	/**
	 * ES5 shallow extend helper (replaces Object.assign).
	 *
	 * @param {Object} target
	 * @param {...Object} sources
	 * @return {Object}
	 */
	function extend( target ) {
		for ( var i = 1; i < arguments.length; i++ ) {
			var source = arguments[i];
			for ( var key in source ) {
				if ( source.hasOwnProperty( key ) ) {
					target[key] = source[key];
				}
			}
		}
		return target;
	}

	registerBlockType( 'basalio/before-after', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

			// Migrate legacy attributes to motionParams on first edit().
			var motionParams = a.motionParams || {};
			if ( Object.keys( motionParams ).length === 0 ) {
				var migrated = {};
				migrated.startPosition = ( typeof a.startPosition === 'number' ) ? a.startPosition : 50;
				migrated.dragStep = 5;
				migrated.reducedMotionFallback = 'instant';

				motionParams = migrated;
				setA( { motionParams: migrated } );
			}

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
				el( SelectControl, {
					key: 'ratio',
					label: __( 'Aspect ratio', 'basalio' ),
					value: a.aspectRatio || 'auto',
					options: RATIOS,
					onChange: function ( v ) { setA( { aspectRatio: v } ); },
					help: __( 'Crop the comparison to a shape. “Original” keeps the images’ own proportions.', 'basalio' )
				} ),
				Tip( __( 'Visitors drag the divider, or focus it and use the arrow keys, to compare the two images. Works with a mouse, touch, and keyboard.', 'basalio' ) )
			];

			var motionControls = [];
			if ( BE.renderMotionControls ) {
				motionControls = BE.renderMotionControls(
					BE.Schemas['basalio/before-after'],
					motionParams,
					function ( update ) {
						setA( { motionParams: extend( {}, motionParams, update ) } );
					}
				);
			}

			for ( var mi = 0; mi < motionControls.length; mi++ ) {
				body.push( motionControls[mi] );
			}

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
