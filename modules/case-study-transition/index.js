/**
 * Editor UI for Basalio: Case Study Transition.
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
	var SelectControl = cmp.SelectControl;
	var TextControl   = cmp.TextControl;
	var ToggleControl = cmp.ToggleControl;
	var RangeControl  = cmp.RangeControl;
	var ToolbarGroup  = cmp.ToolbarGroup;
	var ToolbarButton = cmp.ToolbarButton;
	var Notice        = cmp.Notice;

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

	registerBlockType( 'basalio/case-study-transition', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;
			var role = a.role === 'hero' ? 'hero' : 'source';
			var hasImage = !! ( a.imageUrl || a.imageId );

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

			// Migrate legacy attributes to motionParams on first edit().
			var motionParams = a.motionParams || {};
			if ( Object.keys( motionParams ).length === 0 ) {
				var migrated = {};
				migrated.focusTarget = 'next panel';
				migrated.announceChange = 'polite';
				migrated.reducedMotionFallback = 'crossfade';

				motionParams = migrated;
				setA( { motionParams: migrated } );
			}

			function onSelectImage( m ) {
				setA( { imageId: m.id, imageUrl: m.url, imageAlt: m.alt || '' } );
			}

			var body = [];

			body.push( el( SelectControl, {
				key: 'role',
				label: __( 'This block is the…', 'basalio' ),
				value: role,
				options: [
					{ label: __( 'Grid tile (the transition start)', 'basalio' ), value: 'source' },
					{ label: __( 'Case-study hero (the transition end)', 'basalio' ), value: 'hero' }
				],
				onChange: function ( v ) { setA( { role: v } ); },
				help: __( 'Put a “tile” on your grid page and a matching “hero” on the case-study page.', 'basalio' )
			} ) );

			if ( ! hasImage ) {
				body.push( el( MediaPlaceholder, {
					key: 'media',
					icon: 'images-alt',
					labels: {
						title: __( 'Image', 'basalio' ),
						instructions: role === 'hero'
							? __( 'Choose the case-study hero image (the transition end).', 'basalio' )
							: __( 'Choose the grid tile image (the transition start).', 'basalio' )
					},
					accept: 'image/*',
					allowedTypes: [ 'image' ],
					onSelect: onSelectImage
				} ) );
			} else {
				body.push( el( 'img', { key: 'img', className: 'basalio-ed-preview', src: a.imageUrl, alt: a.imageAlt || '' } ) );
			}

			if ( role === 'source' ) {
				body.push( el( TextControl, {
					key: 'link',
					label: __( 'Links to (case study URL)', 'basalio' ),
					value: a.linkUrl || '',
					onChange: function ( v ) { setA( { linkUrl: v } ); },
					placeholder: 'https://…',
					help: __( 'The case-study page this tile opens.', 'basalio' )
				} ) );

				// Auto-pairing relies on PHP's url_to_postid(), which can fail for
				// custom post types with non-standard permalinks or external URLs.
				// Surface the risk so authors set a Pair ID when auto-matching is
				// uncertain.
				if ( ( a.linkUrl || '' ) !== '' && ( a.pairId || '' ) === '' ) {
					var linkLower = a.linkUrl.toLowerCase();
					var isExternal = linkLower.indexOf( '://' ) !== -1 && linkLower.indexOf( window.location.hostname ) === -1;
					if ( isExternal ) {
						body.push( el(
							Notice,
							{ key: 'pair-warn', status: 'warning', isDismissible: false },
							__( 'This link points outside the site. Auto-pairing will not work; set a Pair ID to match this tile with its hero.', 'basalio' )
						) );
					}
				}
			}

			body.push( el( TextControl, {
				key: 'pair',
				label: __( 'Pair ID (optional)', 'basalio' ),
				value: a.pairId || '',
				onChange: function ( v ) { setA( { pairId: v } ); },
				help: __( 'Leave blank to auto-match. Or set the SAME unique word on the tile and its hero to pair them.', 'basalio' )
			} ) );

			body.push( el( TextControl, {
				key: 'radius',
				type: 'number',
				label: __( 'Corner radius (px)', 'basalio' ),
				value: ( a.borderRadius == null ? 0 : a.borderRadius ),
				min: 0,
				onChange: function ( v ) { setA( { borderRadius: '' === v ? 0 : parseInt( v, 10 ) || 0 } ); },
				help: __( 'Rounds the image corners. 0 is square.', 'basalio' )
			} ) );

			var duration = ( a.transitionDuration == null ? 300 : a.transitionDuration );

			body.push( el( RangeControl, {
				key: 'dur',
				label: __( 'Transition speed (ms)', 'basalio' ),
				value: duration,
				min: 150,
				max: 600,
				step: 25,
				onChange: function ( v ) { setA( { transitionDuration: v } ); },
				help: __( 'How long the morph takes on supporting browsers. 300ms suits most work.', 'basalio' )
			} ) );

			// A shared-element morph animates a large area and changes scale, which
			// is the riskier end of what provokes a vestibular response. Warn rather
			// than forbid: the author may have a reason, but should make the choice
			// knowingly. Anyone with prefers-reduced-motion set never sees the morph
			// at all, but most people with vestibular sensitivity have never heard
			// of that setting, so the default has to be comfortable on its own.
			if ( duration > 400 ) {
				body.push( el(
					Notice,
					{ key: 'dur-warn', status: 'warning', isDismissible: false },
					__( 'Above 400ms a full-image morph can feel slow, and large moving areas are more likely to cause discomfort for visitors sensitive to motion. 300ms is the recommended default.', 'basalio' )
				) );
			}

			if ( role === 'source' ) {
				body.push( el( ToggleControl, {
					key: 'lb',
					label: __( 'Open image in a lightbox instead', 'basalio' ),
					checked: !! a.lightbox,
					onChange: function ( v ) { setA( { lightbox: v } ); },
					help: __( 'When on, clicking expands the image in an overlay rather than navigating to the case study.', 'basalio' )
				} ) );
			}

			var motionControls = [];
			if ( BE.renderMotionControls ) {
				motionControls = BE.renderMotionControls(
					BE.Schemas['basalio/case-study-transition'],
					motionParams,
					function ( update ) {
						setA( { motionParams: extend( {}, motionParams, update ) } );
					}
				);
			}

			for ( var mi = 0; mi < motionControls.length; mi++ ) {
				body.push( motionControls[mi] );
			}

			body.push( Tip( __( 'On supporting browsers the tile morphs into the hero when clicked. Elsewhere it’s a normal link, and the back button always works.', 'basalio' ) ) );

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Accessibility', 'basalio' ), initialOpen: false },
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
						name: __( 'Case Study Transition', 'basalio' ),
						description: __( 'A smooth shared-element transition from a grid tile to its case-study page.', 'basalio' )
					},
					body
				)
			);
		},

		save: function () { return null; }
	} );
}( window.wp ) );
