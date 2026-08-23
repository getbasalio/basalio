/**
 * Editor UI for Basalio: Text Reveal.
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

	var cmp             = wp.components;
	var PanelBody       = cmp.PanelBody;
	var TextControl     = cmp.TextControl;
	var TextareaControl = cmp.TextareaControl;
	var SelectControl   = cmp.SelectControl;

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

	registerBlockType( 'basalio/text-reveal', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;
			var level = a.headingLevel && /^h[1-4]$/.test( a.headingLevel ) ? a.headingLevel : 'h2';

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

			// Migrate legacy attributes to motionParams on first edit().
			var motionParams = a.motionParams || {};
			if ( Object.keys( motionParams ).length === 0 ) {
				var migrated = {};
				migrated.splitUnit = ( a.wordByWord === true ) ? 'word' : 'line';

				var dur = a.duration;
				if ( typeof dur === 'number' ) {
					if ( dur <= 350 ) {
						migrated.duration = 'short';
					} else if ( dur > 900 ) {
						migrated.duration = 'long';
					} else {
						migrated.duration = 'base';
					}
				} else {
					migrated.duration = 'base';
				}

				migrated.easing = 'glide';
				migrated.triggerThreshold = 50;
				migrated.reducedMotionFallback = 'appear instantly';

				motionParams = migrated;
				setA( { motionParams: migrated } );
			}

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Advanced', 'basalio' ), initialOpen: false },
					el( SelectControl, {
						label: __( 'Headline HTML level', 'basalio' ),
						value: level,
						options: [
							{ label: __( 'H1 (use once per page, homepage hero)', 'basalio' ), value: 'h1' },
							{ label: 'H2', value: 'h2' },
							{ label: 'H3', value: 'h3' },
							{ label: 'H4', value: 'h4' }
						],
						onChange: function ( v ) { setA( { headingLevel: v } ); },
						help: __( 'Affects document structure/SEO, not the size.', 'basalio' )
					} ),
					el( TextControl, {
						label: __( 'Button link (URL)', 'basalio' ),
						value: a.ctaUrl || '',
						onChange: function ( v ) { setA( { ctaUrl: v } ); },
						help: __( 'Where the button goes. Leave the button text empty to hide it.', 'basalio' )
					} )
				)
			);

			var body = [
				el( TextControl, {
					key: 'headline',
					label: __( 'Headline', 'basalio' ),
					value: a.headline || '',
					onChange: function ( v ) { setA( { headline: v } ); },
					placeholder: __( 'Your main line', 'basalio' )
				} ),
				el( TextareaControl, {
					key: 'subhead',
					label: __( 'Subhead (optional)', 'basalio' ),
					value: a.subhead || '',
					onChange: function ( v ) { setA( { subhead: v } ); },
					placeholder: __( 'A supporting sentence', 'basalio' ),
					rows: 2
				} ),
				el( TextControl, {
					key: 'cta',
					label: __( 'Button text (optional)', 'basalio' ),
					value: a.ctaText || '',
					onChange: function ( v ) { setA( { ctaText: v } ); },
					placeholder: __( 'e.g. Get in touch', 'basalio' ),
					help: __( 'Set the link under Advanced (sidebar).', 'basalio' )
				} )
			];

			var motionControls = [];
			if ( BE.renderMotionControls ) {
				motionControls = BE.renderMotionControls(
					BE.Schemas['basalio/text-reveal'],
					motionParams,
					function ( update ) {
						setA( { motionParams: extend( {}, motionParams, update ) } );
					}
				);
			}

			for ( var mi = 0; mi < motionControls.length; mi++ ) {
				body.push( motionControls[mi] );
			}

			body.push( Tip( __( 'The lines fade in as they scroll into view. Screen readers get the full text immediately, and under reduced-motion it appears instantly.', 'basalio' ) ) );

			return el(
				'div',
				blockProps,
				inspector,
				Panel(
					{
						name: __( 'Text Reveal', 'basalio' ),
						description: __( 'A choreographed entrance for a headline, subhead, and optional button. They fade in as they scroll into view.', 'basalio' )
					},
					body
				)
			);
		},

		save: function () { return null; }
	} );
}( window.wp ) );
