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

	registerBlockType( 'basalio/grid-reveal', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;
			var style = a.revealStyle || 'crossfade';
			var hasImage = !! ( a.imageUrl || a.imageId );

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

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
						key: 'style',
						label: __( 'Reveal style', 'basalio' ),
						value: style,
						options: [
							{ label: __( 'Crossfade (fade and slide up)', 'basalio' ), value: 'crossfade' },
							{ label: __( 'Simple (fade only)', 'basalio' ), value: 'opacity' }
						],
						onChange: function ( v ) { setA( { revealStyle: v } ); }
					} ),
					Tip( __( 'On the live page the caption stays hidden until a visitor hovers, focuses, or taps this tile.', 'basalio' ) )
				];
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
