/**
 * Basalio, shared editor UI helpers (block editor only).
 *
 * Every module's editor script depends on this file (see each index.asset.php),
 * so `window.BasalioEd` is always available before a block's edit() runs. It
 * gives every Basalio block the same friendly, self-explanatory chrome:
 *
 *   BasalioEd.Panel( { name, description, badge }, bodyChildren )
 *   BasalioEd.Field( 'Label', <control>, 'helper hint' )
 *   BasalioEd.Tip( 'A one-line tip shown in the block.' )
 *
 * Styling lives in assets/css/basalio-editor.css (.basalio-ed-* classes).
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var BasalioEd = ( window.BasalioEd = window.BasalioEd || {} );

	/**
	 * Expose the localized motion schemas under the shared BasalioEd namespace.
	 *
	 * Phase A localizes `window.BasalioSchemas` from PHP; Phase B will consume
	 * this object to render parameter controls without hardcoding block-specific
	 * field names.
	 */
	BasalioEd.Schemas = window.BasalioSchemas || {};

	/**
	 * Branded block wrapper: a header (mark + name + optional badge) and a
	 * plain-English description, above the block's own fields.
	 *
	 * @param {Object} opts { name, description, badge }
	 * @param {*}      body  Child node(s) for the panel body.
	 * @return {Object} React element.
	 */
	BasalioEd.Panel = function ( opts, body ) {
		opts = opts || {};
		return el(
			'div',
			{ className: 'basalio-ed-panel' },
			el(
				'div',
				{ className: 'basalio-ed-head' },
				el( 'span', { className: 'basalio-ed-mark', 'aria-hidden': 'true' } ),
				el( 'span', { className: 'basalio-ed-name' }, opts.name || 'Basalio' ),
				opts.badge ? el( 'span', { className: 'basalio-ed-badge' }, opts.badge ) : null
			),
			opts.description
				? el( 'p', { className: 'basalio-ed-desc' }, opts.description )
				: null,
			el( 'div', { className: 'basalio-ed-body' }, body )
		);
	};

	/**
	 * A labelled field row: an uppercase label, the control, and an optional
	 * helper hint below it, so it's clear what every input does in the canvas,
	 * not just the sidebar.
	 *
	 * @param {string} label    Field label.
	 * @param {*}      children  The control.
	 * @param {string} [hint]    Optional helper text.
	 * @return {Object} React element.
	 */
	BasalioEd.Field = function ( label, children, hint ) {
		return el(
			'div',
			{ className: 'basalio-ed-field' },
			label ? el( 'span', { className: 'basalio-ed-label' }, label ) : null,
			children,
			hint ? el( 'span', { className: 'basalio-ed-hint' }, hint ) : null
		);
	};

	/**
	 * A quiet, dashed "tip" line for guidance inside the block body.
	 *
	 * @param {*} text Tip content.
	 * @return {Object} React element.
	 */
	BasalioEd.Tip = function ( text ) {
		// The icon glyph is drawn in CSS (::before). Keeping the span empty means
		// that if the stylesheet ever fails to load, there is no stray letter.
		return el(
			'p',
			{ className: 'basalio-ed-tip' },
			el( 'span', { className: 'basalio-ed-tip-mark', 'aria-hidden': 'true' } ),
			el( 'span', { className: 'basalio-ed-tip-text' }, text )
		);
	};

	/**
	 * Shared image-behavior controls, reused by every block that shows an image:
	 * a numeric corner-radius field, an optional Link URL, and a lightbox toggle.
	 * Returns an array of controls to drop into a panel body.
	 *
	 * @param {Object} o { radius, onRadius, linkUrl, onLink, lightbox, onLightbox }
	 * @return {Array} React elements.
	 */
	BasalioEd.ImageOptions = function ( o ) {
		var wp = window.wp;
		var __ = wp.i18n.__;
		var cmp = wp.components;
		return [
			el( cmp.TextControl, {
				key: 'radius',
				type: 'number',
				label: __( 'Corner radius (px)', 'basalio' ),
				value: ( o.radius == null ? '' : o.radius ),
				min: 0,
				onChange: function ( v ) { o.onRadius( '' === v ? 0 : parseInt( v, 10 ) || 0 ); },
				help: __( 'Rounds the image corners. 0 is square.', 'basalio' )
			} ),
			el( cmp.TextControl, {
				key: 'link',
				type: 'url',
				label: __( 'Link URL', 'basalio' ),
				value: o.linkUrl || '',
				placeholder: 'https://',
				onChange: o.onLink,
				help: __( 'Optional. Clicking the image opens this page.', 'basalio' )
			} ),
			el( cmp.ToggleControl, {
				key: 'lb',
				label: __( 'Open image in a lightbox', 'basalio' ),
				checked: !! o.lightbox,
				onChange: o.onLightbox,
				help: __( 'Shows the full image in an overlay. Takes priority over the link when both are set.', 'basalio' )
			} )
		];
	};

	/**
	 * Shared animation-speed control (milliseconds), for the blocks whose
	 * entrance is otherwise too quick to notice.
	 *
	 * @param {string}   label
	 * @param {number}   value
	 * @param {Function} onChange
	 * @param {string}   [help]
	 * @return {Object} React element.
	 */
	BasalioEd.DurationField = function ( label, value, onChange, help ) {
		var wp = window.wp;
		var cmp = wp.components;
		return el( cmp.RangeControl, {
			label: label,
			value: value,
			min: 200,
			max: 4000,
			step: 100,
			onChange: onChange,
			help: help
		} );
	};

	/**
	 * Render motion-parameter controls from a module's motion schema.
	 *
	 * Phase B: generic schema-driven form renderer. No block-specific labels or
	 * parameter names are hardcoded here.
	 *
	 * @param {Object}   schema       Motion schema object with a `parameters` key.
	 * @param {Object}   motionParams Current saved motionParams attribute value.
	 * @param {Function} onChange     Callback receiving a partial motionParams object to merge.
	 * @return {Array} Array of React elements.
	 */
	BasalioEd.renderMotionControls = function ( schema, motionParams, onChange ) {
		var wp = window.wp;
		var cmp = wp.components;
		var element = wp.element;
		var Fragment = element.Fragment;
		motionParams = motionParams || {};

		if ( ! schema || ! schema.parameters ) {
			return [];
		}

		var params = schema.parameters;
		var keys = Object.keys( params );
		var controls = [];

		for ( var i = 0; i < keys.length; i++ ) {
			var paramName = keys[i];
			var param = params[paramName];

			// A select with only one option offers no real choice. Keep the param
			// in the schema (fallback class emission depends on it) but do not
			// render a useless control.
			if ( param.type === 'select' && param.options && param.options.length <= 1 ) {
				continue;
			}

			var rawValue = motionParams[paramName];
			var value = ( rawValue === undefined || rawValue === null ) ? param.default : rawValue;
			var helpOrHint = param.help || param.hint || '';
			var control = null;

			if ( param.type === 'select' ) {
				var selectProps = {
					key: paramName + '-control',
					options: param.options,
					value: value,
					onChange: ( function ( k ) {
						return function ( v ) {
							var update = {};
							update[k] = v;
							onChange( update );
						};
					}( paramName ) )
				};
				if ( ! helpOrHint ) {
					selectProps.label = param.label;
					selectProps.help = param.help || undefined;
				}
				control = el( cmp.SelectControl, selectProps );
			} else if ( param.type === 'range' ) {
				var rangeHelp = param.help || '';
				if ( param.unit ) {
					if ( rangeHelp ) {
						rangeHelp += ' (' + param.unit + ')';
					} else {
						rangeHelp = param.unit;
					}
				}
				var rangeProps = {
					key: paramName + '-control',
					min: param.min,
					max: param.max,
					step: param.step,
					value: value,
					onChange: ( function ( k ) {
						return function ( v ) {
							var update = {};
							update[k] = parseFloat( v );
							onChange( update );
						};
					}( paramName ) )
				};
				if ( ! helpOrHint ) {
					rangeProps.label = param.label;
					rangeProps.help = rangeHelp || undefined;
				}
				control = el( cmp.RangeControl, rangeProps );
			} else if ( param.type === 'toggle' ) {
				var toggleProps = {
					key: paramName + '-control',
					checked: !! value,
					onChange: ( function ( k ) {
						return function ( v ) {
							var update = {};
							update[k] = !! v;
							onChange( update );
						};
					}( paramName ) )
				};
				if ( ! helpOrHint ) {
					toggleProps.label = param.label;
				}
				control = el( cmp.ToggleControl, toggleProps );
			} else if ( param.type === 'text' ) {
				var textProps = {
					key: paramName + '-control',
					value: value || '',
					onChange: ( function ( k ) {
						return function ( v ) {
							var update = {};
							update[k] = v;
							onChange( update );
						};
					}( paramName ) )
				};
				if ( ! helpOrHint ) {
					textProps.label = param.label;
				}
				control = el( cmp.TextControl, textProps );
			}

			if ( ! control ) {
				continue;
			}

			if ( helpOrHint ) {
				controls.push( el( Fragment, { key: paramName }, BasalioEd.Field( param.label, control, helpOrHint ) ) );
			} else {
				controls.push( control );
			}
		}

		return controls;
	};
}( window.wp ) );
