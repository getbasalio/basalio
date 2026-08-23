/**
 * Editor UI for Basalio: Grid Reveal.
 *
 * Uses the shared BasalioEd panel chrome for a clear, self-explanatory editor.
 * `save` returns null, the block renders dynamically on the server (render.php).
 */
( function ( wp ) {
	'use strict';

	var el                = wp.element.createElement;
	var __                = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;

	var be                = wp.blockEditor;
	var useBlockProps     = be.useBlockProps;
	var InspectorControls = be.InspectorControls;
	var BlockControls     = be.BlockControls;
	var MediaPlaceholder  = be.MediaPlaceholder;
	var MediaUpload       = be.MediaUpload;
	var MediaUploadCheck  = be.MediaUploadCheck;

	var cmp           = wp.components;
	var PanelBody     = cmp.PanelBody;
	var TextControl   = cmp.TextControl;
	var SelectControl = cmp.SelectControl;
	var ToolbarGroup  = cmp.ToolbarGroup;
	var ToolbarButton = cmp.ToolbarButton;

	// Shared editor helpers (with tiny fallbacks so the block still renders even
	// if the helper script somehow didn't load).
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

	registerBlockType( 'basalio/grid-reveal', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;
			var hasImage = !! ( a.imageUrl || a.imageId );

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

			// Migrate legacy attributes to motionParams on first edit().
			var motionParams = a.motionParams || {};
			if ( Object.keys( motionParams ).length === 0 ) {
				var migrated = {};
				migrated.revealStyle = ( a.revealStyle === 'opacity' ) ? 'opacity' : 'crossfade';
				migrated.reducedMotionFallback = 'appear instantly';

				motionParams = migrated;
				setA( { motionParams: migrated } );
			}

			function onSelectImage( media ) {
				setA( { imageId: media.id, imageUrl: media.url, imageAlt: media.alt || '' } );
			}

			var body;
			if ( ! hasImage ) {
				body = el( MediaPlaceholder, {
					icon: 'format-image',
					labels: {
						title: __( 'Tile image', 'basalio' ),
						instructions: __( 'Upload or choose the portfolio image for this tile.', 'basalio' )
					},
					accept: 'image/*',
					allowedTypes: [ 'image' ],
					onSelect: onSelectImage
				} );
			} else {
				body = [
					el( 'img', { key: 'img', className: 'basalio-ed-preview', src: a.imageUrl, alt: a.imageAlt || '' } ),
					el( TextControl, {
						key: 'title',
						label: __( 'Caption title', 'basalio' ),
						value: a.title || '',
						onChange: function ( v ) { setA( { title: v } ); },
						placeholder: __( 'e.g. Faro Identity', 'basalio' ),
						help: __( 'Shown large in the caption.', 'basalio' )
					} ),
					el( TextControl, {
						key: 'tag',
						label: __( 'Category / tag', 'basalio' ),
						value: a.tag || '',
						onChange: function ( v ) { setA( { tag: v } ); },
						placeholder: __( 'e.g. Branding · 2026', 'basalio' ),
						help: __( 'Small line shown above the title.', 'basalio' )
					} ),
					el( SelectControl, {
						key: 'ratio',
						label: __( 'Tile shape (aspect ratio)', 'basalio' ),
						value: a.aspectRatio || 'auto',
						options: [
							{ label: __( 'Original image shape', 'basalio' ), value: 'auto' },
							{ label: __( 'Square 1:1', 'basalio' ), value: '1:1' },
							{ label: __( 'Landscape 4:3', 'basalio' ), value: '4:3' },
							{ label: __( 'Landscape 3:2', 'basalio' ), value: '3:2' },
							{ label: __( 'Wide 16:9', 'basalio' ), value: '16:9' },
							{ label: __( 'Portrait 4:5', 'basalio' ), value: '4:5' },
							{ label: __( 'Portrait 3:4', 'basalio' ), value: '3:4' },
							{ label: __( 'Tall 2:3', 'basalio' ), value: '2:3' }
						],
						onChange: function ( v ) { setA( { aspectRatio: v } ); },
						help: __( 'Sets the tile’s shape/size. Width still follows the column it sits in.', 'basalio' )
					} )
				].concat(
					BE.ImageOptions ? BE.ImageOptions( {
						radius: a.borderRadius == null ? 2 : a.borderRadius,
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
						BE.Schemas['basalio/grid-reveal'],
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
					Tip( __( 'On the live page the caption stays hidden until a visitor hovers, focuses, or taps this tile.', 'basalio' ) )
				] );
			}

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Accessibility', 'basalio' ), initialOpen: false },
					el( TextControl, {
						label: __( 'Image alt text', 'basalio' ),
						value: a.imageAlt || '',
						onChange: function ( v ) { setA( { imageAlt: v } ); },
						help: __( 'Describes the image for screen readers.', 'basalio' )
					} )
				)
			);

			var toolbar = hasImage ? el(
				BlockControls,
				null,
				el(
					ToolbarGroup,
					null,
					el( MediaUploadCheck, null,
						el( MediaUpload, {
							onSelect: onSelectImage,
							allowedTypes: [ 'image' ],
							value: a.imageId,
							render: function ( o ) { return el( ToolbarButton, { onClick: o.open }, __( 'Replace image', 'basalio' ) ); }
						} )
					)
				)
			) : null;

			return el(
				'div',
				blockProps,
				inspector,
				toolbar,
				Panel(
					{
						name: __( 'Grid Reveal', 'basalio' ),
						description: __( 'A portfolio tile that reveals its caption when a visitor hovers, focuses, or taps it.', 'basalio' )
					},
					body
				)
			);
		},

		save: function () { return null; }
	} );
}( window.wp ) );
