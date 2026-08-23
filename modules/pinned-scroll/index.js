/**
 * Editor UI for Basalio: Pinned Scroll.
 *
 * A pinned image plus an InnerBlocks story. `save` returns InnerBlocks.Content
 * so the story persists; render.php builds the front end (the pinning is CSS).
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
	var MediaPlaceholder     = be.MediaPlaceholder;
	var MediaUpload          = be.MediaUpload;
	var MediaUploadCheck     = be.MediaUploadCheck;

	var cmp           = wp.components;
	var Button        = cmp.Button;
	var SelectControl = cmp.SelectControl;
	var TextControl   = cmp.TextControl;
	var RangeControl  = cmp.RangeControl;

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

	registerBlockType( 'basalio/pinned-scroll', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;
			var hasImage = !! ( a.pinnedImageUrl || a.pinnedImageId );

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

			// Migrate legacy attributes to motionParams on first edit().
			var motionParams = a.motionParams || {};
			if ( Object.keys( motionParams ).length === 0 ) {
				var migrated = {};
				migrated.reducedMotionFallback = 'no pin';

				motionParams = migrated;
				setA( { motionParams: migrated } );
			}

			var innerProps = useInnerBlocksProps(
				{ className: 'basalio-pinned-scroll__story' },
				{ template: [ [ 'core/heading', { level: 3, placeholder: __( 'Story heading…', 'basalio' ) } ], [ 'core/paragraph', { placeholder: __( 'Tell the story here. The image stays pinned while this scrolls.', 'basalio' ) } ] ] }
			);

			function onSelectImage( m ) {
				setA( { pinnedImageId: m.id, pinnedImageUrl: m.url, pinnedImageAlt: m.alt || '' } );
			}

			var mediaField = ! hasImage
				? el( MediaPlaceholder, {
					icon: 'format-image',
					labels: { title: __( 'Pinned image', 'basalio' ), instructions: __( 'Choose the image that stays in view while the story scrolls.', 'basalio' ) },
					accept: 'image/*',
					allowedTypes: [ 'image' ],
					onSelect: onSelectImage
				} )
				: el( 'div', { key: 'imgwrap' },
					el( 'img', { className: 'basalio-ed-preview', src: a.pinnedImageUrl, alt: a.pinnedImageAlt || '', style: { marginBottom: '8px' } } ),
					el( MediaUploadCheck, null,
						el( MediaUpload, {
							onSelect: onSelectImage,
							allowedTypes: [ 'image' ],
							value: a.pinnedImageId,
							render: function ( o ) { return el( Button, { variant: 'secondary', onClick: o.open }, __( 'Replace image', 'basalio' ) ); }
						} )
					)
				);

			var body = [
				el( 'div', { key: 'media', className: 'basalio-ed-field' },
					el( 'span', { className: 'basalio-ed-label' }, __( 'Pinned image', 'basalio' ) ),
					mediaField
				),
				el( SelectControl, {
					key: 'pos',
					label: __( 'Image side', 'basalio' ),
					value: a.mediaPosition === 'right' ? 'right' : 'left',
					options: [
						{ label: __( 'Left', 'basalio' ), value: 'left' },
						{ label: __( 'Right', 'basalio' ), value: 'right' }
					],
					onChange: function ( v ) { setA( { mediaPosition: v } ); }
				} ),
				el( RangeControl, {
					key: 'mw',
					label: __( 'Image width (%)', 'basalio' ),
					value: a.mediaWidth == null ? 50 : a.mediaWidth,
					min: 25,
					max: 75,
					onChange: function ( v ) { setA( { mediaWidth: v } ); },
					help: __( 'How much width the image takes versus the story. Higher makes the image larger.', 'basalio' )
				} )
			].concat(
				BE.ImageOptions ? BE.ImageOptions( {
					radius: a.borderRadius == null ? 4 : a.borderRadius,
					onRadius: function ( v ) { setA( { borderRadius: v } ); },
					linkUrl: a.linkUrl,
					onLink: function ( v ) { setA( { linkUrl: v } ); },
					lightbox: a.lightbox,
					onLightbox: function ( v ) { setA( { lightbox: v } ); }
				} ) : []
			);

			var motionControls = [];
			if ( BE.renderMotionControls ) {
				motionControls = BE.renderMotionControls(
					BE.Schemas['basalio/pinned-scroll'],
					motionParams,
					function ( update ) {
						setA( { motionParams: extend( {}, motionParams, update ) } );
					}
				);
			}

			for ( var mi = 0; mi < motionControls.length; mi++ ) {
				body.push( motionControls[mi] );
			}

			body = body.concat( [
				el( TextControl, {
					key: 'alt',
					label: __( 'Image alt text', 'basalio' ),
					value: a.pinnedImageAlt || '',
					onChange: function ( v ) { setA( { pinnedImageAlt: v } ); }
				} ),
				Tip( __( 'The image pins in place while the story scrolls past it. On phones, and for anyone using reduced motion, it becomes a normal stacked layout.', 'basalio' ) ),
				el( 'div', { key: 'story', className: 'basalio-ed-field' },
					el( 'span', { className: 'basalio-ed-label' }, __( 'Story', 'basalio' ) ),
					el( 'div', innerProps )
				)
			] );

			return el(
				'div',
				blockProps,
				Panel(
					{
						name: __( 'Pinned Scroll', 'basalio' ),
						description: __( 'Pins an image beside a story that scrolls past it.', 'basalio' )
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
