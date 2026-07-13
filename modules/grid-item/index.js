/**
 * Editor UI for a Basalio: Grid Item (child of Filterable Grid).
 *
 * Uses the shared BasalioEd panel chrome. `save` returns null, rendered
 * dynamically on the server (render.php).
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
	var ToggleControl = cmp.ToggleControl;
	var ToolbarGroup  = cmp.ToolbarGroup;
	var ToolbarButton = cmp.ToolbarButton;

	var BE    = window.BasalioEd || {};
	var Panel = BE.Panel || function ( o, b ) { return el( 'div', { className: 'basalio-ed-panel' }, b ); };
	var Tip   = BE.Tip || function ( t ) { return el( 'p', null, t ); };

	registerBlockType( 'basalio/grid-item', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;
			var hasImage = !! ( a.imageUrl || a.imageId );

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

			function onSelectImage( m ) {
				setA( { imageId: m.id, imageUrl: m.url, imageAlt: m.alt || '' } );
			}

			var body;
			if ( ! hasImage ) {
				body = el( MediaPlaceholder, {
					icon: 'format-image',
					labels: { title: __( 'Item image', 'basalio' ), instructions: __( 'Upload or choose this item’s image.', 'basalio' ) },
					accept: 'image/*',
					allowedTypes: [ 'image' ],
					onSelect: onSelectImage
				} );
			} else {
				body = [
					el( 'img', { key: 'img', className: 'basalio-ed-preview', src: a.imageUrl, alt: a.imageAlt || '' } ),
					el( TextControl, {
						key: 'title',
						label: __( 'Title', 'basalio' ),
						value: a.title || '',
						onChange: function ( v ) { setA( { title: v } ); },
						placeholder: __( 'e.g. Faro Identity', 'basalio' )
					} ),
					el( TextControl, {
						key: 'cat',
						label: __( 'Category', 'basalio' ),
						value: a.category || '',
						onChange: function ( v ) { setA( { category: v } ); },
						placeholder: __( 'e.g. Branding', 'basalio' ),
						help: __( 'Becomes a filter button on the grid. Reuse the same wording across items to group them.', 'basalio' )
					} ),
					Tip( __( 'Items with the same Category are shown together when a visitor clicks that filter.', 'basalio' ) )
				];
			}

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Link & accessibility', 'basalio' ), initialOpen: false },
					el( TextControl, {
						label: __( 'Link (URL)', 'basalio' ),
						value: a.url || '',
						onChange: function ( v ) { setA( { url: v } ); },
						placeholder: 'https://…',
						help: __( 'Optional. Where the item links to.', 'basalio' )
					} ),
					el( ToggleControl, {
						label: __( 'Open image in a lightbox', 'basalio' ),
						checked: !! a.lightbox,
						onChange: function ( v ) { setA( { lightbox: v } ); },
						help: __( 'Shows the full image in an overlay. Takes priority over the link.', 'basalio' )
					} ),
					el( TextControl, {
						type: 'number',
						label: __( 'Corner radius (px)', 'basalio' ),
						value: a.borderRadius == null ? 0 : a.borderRadius,
						min: 0,
						onChange: function ( v ) { setA( { borderRadius: '' === v ? 0 : parseInt( v, 10 ) || 0 } ); }
					} ),
					el( TextControl, {
						label: __( 'Image alt text', 'basalio' ),
						value: a.imageAlt || '',
						onChange: function ( v ) { setA( { imageAlt: v } ); }
					} )
				)
			);

			var toolbar = hasImage ? el(
				BlockControls,
				null,
				el( ToolbarGroup, null,
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
						name: __( 'Grid Item', 'basalio' ),
						description: __( 'One item in the Filterable Grid. Its Category powers the grid’s filter buttons.', 'basalio' )
					},
					body
				)
			);
		},

		save: function () { return null; }
	} );
}( window.wp ) );
