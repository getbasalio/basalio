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
		return el(
			'p',
			{ className: 'basalio-ed-tip' },
			el( 'span', { className: 'basalio-ed-tip-mark', 'aria-hidden': 'true' }, 'i' ),
			el( 'span', null, text )
		);
	};
}( window.wp ) );
