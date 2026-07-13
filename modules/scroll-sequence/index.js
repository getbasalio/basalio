/**
 * Editor UI for Basalio: Scroll Sequence.
 *
 * Frames are stored as an attribute array (id, url, alt), chosen with a
 * multi-select media picker. `save` returns null; render.php builds the front end.
 */
( function ( wp ) {
	'use strict';

	var el                = wp.element.createElement;
	var __                = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;

	var be               = wp.blockEditor;
	var useBlockProps    = be.useBlockProps;
	var MediaPlaceholder = be.MediaPlaceholder;
	var MediaUpload      = be.MediaUpload;
	var MediaUploadCheck = be.MediaUploadCheck;

	var cmp           = wp.components;
	var Button        = cmp.Button;
	var RangeControl  = cmp.RangeControl;
	var ToggleControl = cmp.ToggleControl;
	var TextControl   = cmp.TextControl;

	var BE    = window.BasalioEd || {};
	var Panel = BE.Panel || function ( o, b ) { return el( 'div', { className: 'basalio-ed-panel' }, b ); };
	var Tip   = BE.Tip || function ( t ) { return el( 'p', null, t ); };

	function toFrames( media ) {
		var list = Array.isArray( media ) ? media : [ media ];
		return list.map( function ( m ) { return { id: m.id, url: m.url, alt: m.alt || '' }; } );
	}

	registerBlockType( 'basalio/scroll-sequence', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;
			var frames = Array.isArray( a.frames ) ? a.frames : [];
			var screens = typeof a.scrollScreens === 'number' ? a.scrollScreens : 3;

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

			var body;
			if ( ! frames.length ) {
				body = el( MediaPlaceholder, {
					icon: 'images-alt2',
					labels: {
						title: __( 'Frame sequence', 'basalio' ),
						instructions: __( 'Select the frames in order. They play as the visitor scrolls.', 'basalio' )
					},
					accept: 'image/*',
					allowedTypes: [ 'image' ],
					multiple: true,
					onSelect: function ( media ) { setA( { frames: toFrames( media ) } ); }
				} );
			} else {
				var thumbs = el(
					'div',
					{ key: 'thumbs', className: 'basalio-ed-seq-thumbs', style: { display: 'flex', flexWrap: 'wrap', gap: '6px', marginBottom: '12px' } },
					frames.map( function ( f, i ) {
						return el( 'img', { key: i, src: f.url, alt: f.alt || '', style: { width: '54px', height: '40px', objectFit: 'cover', borderRadius: '3px', border: '1px solid #e5e1d8' } } );
					} )
				);
				body = [
					el( 'p', { key: 'count', className: 'basalio-ed-label' }, frames.length + ' ' + __( 'frames', 'basalio' ) ),
					thumbs,
					el( MediaUploadCheck, { key: 'edit' },
						el( MediaUpload, {
							onSelect: function ( media ) { setA( { frames: toFrames( media ) } ); },
							allowedTypes: [ 'image' ],
							multiple: true,
							gallery: true,
							value: frames.map( function ( f ) { return f.id; } ),
							render: function ( o ) { return el( Button, { variant: 'secondary', onClick: o.open }, __( 'Edit frames', 'basalio' ) ); }
						} )
					),
					el( RangeControl, {
						key: 'screens',
						label: __( 'Scroll length (screens)', 'basalio' ),
						value: screens,
						min: 2,
						max: 8,
						onChange: function ( v ) { setA( { scrollScreens: v } ); },
						help: __( 'How much scrolling it takes to play the whole sequence.', 'basalio' )
					} ),
					el( ToggleControl, {
						key: 'controls',
						label: __( 'Show the step controls', 'basalio' ),
						checked: a.showControls == null ? true : !! a.showControls,
						onChange: function ( v ) { setA( { showControls: v } ); },
						help: __( 'The Previous / Next buttons and counter. Turning them off leaves scroll as the only way to advance.', 'basalio' )
					} ),
					( ( a.showControls == null ? true : a.showControls ) ? el( TextControl, {
						key: 'fmt',
						label: __( 'Counter text', 'basalio' ),
						value: a.paginationFormat == null ? 'Frame {current} of {total}' : a.paginationFormat,
						onChange: function ( v ) { setA( { paginationFormat: v } ); },
						help: __( 'Use {current} and {total} as placeholders, e.g. “{current} / {total}” or “Step {current}”.', 'basalio' )
					} ) : null ),
					Tip( __( 'The sequence sticks to the screen and steps through frames as the visitor scrolls. The Previous and Next buttons let anyone step through it without scrolling.', 'basalio' ) )
				];
			}

			return el(
				'div',
				blockProps,
				Panel(
					{
						name: __( 'Scroll Sequence', 'basalio' ),
						description: __( 'Plays a set of frames tied to scroll, like a flipbook, with manual buttons as well.', 'basalio' )
					},
					body
				)
			);
		},

		save: function () { return null; }
	} );
}( window.wp ) );
