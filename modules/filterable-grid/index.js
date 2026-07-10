/**
 * Editor UI for Basalio: Filterable Grid (parent block).
 *
 * Uses the shared BasalioEd panel chrome around an InnerBlocks list of
 * `basalio/grid-item` children. `save` returns InnerBlocks.Content so the items
 * persist; render.php builds the front end (filters derived from item categories).
 */
( function ( wp ) {
	'use strict';

	var el                  = wp.element.createElement;
	var __                  = wp.i18n.__;
	var registerBlockType   = wp.blocks.registerBlockType;

	var be                  = wp.blockEditor;
	var useBlockProps       = be.useBlockProps;
	var useInnerBlocksProps = be.useInnerBlocksProps;
	var InnerBlocks         = be.InnerBlocks;

	var cmp          = wp.components;
	var RangeControl = cmp.RangeControl;
	var TextControl  = cmp.TextControl;

	var BE    = window.BasalioEd || {};
	var Panel = BE.Panel || function ( o, b ) { return el( 'div', { className: 'basalio-ed-panel' }, b ); };
	var Tip   = BE.Tip || function ( t ) { return el( 'p', null, t ); };

	var ALLOWED  = [ 'basalio/grid-item' ];
	var TEMPLATE = [ [ 'basalio/grid-item' ], [ 'basalio/grid-item' ], [ 'basalio/grid-item' ] ];

	registerBlockType( 'basalio/filterable-grid', {
		edit: function ( props ) {
			var a    = props.attributes;
			var setA = props.setAttributes;
			var cols = typeof a.columns === 'number' ? a.columns : 3;

			var blockProps = useBlockProps( { className: 'basalio-editor' } );

			var innerProps = useInnerBlocksProps(
				{ className: 'basalio-filterable-grid__grid', style: { '--basalio-fg-cols': cols } },
				{ allowedBlocks: ALLOWED, template: TEMPLATE, templateLock: false }
			);

			var body = [
				el( RangeControl, {
					key: 'cols',
					label: __( 'Columns', 'basalio' ),
					value: cols,
					min: 2,
					max: 4,
					onChange: function ( v ) { setA( { columns: v } ); }
				} ),
				el( TextControl, {
					key: 'all',
					label: __( '“Show all” button label', 'basalio' ),
					value: a.allLabel || '',
					onChange: function ( v ) { setA( { allLabel: v } ); },
					placeholder: __( 'All', 'basalio' )
				} ),
				Tip( __( 'Add items below and give every item a Category. The filter buttons on the live page are built automatically from those categories.', 'basalio' ) ),
				el( 'div', { key: 'grid', style: { marginTop: '14px' } }, el( 'div', innerProps ) )
			];

			return el(
				'div',
				blockProps,
				Panel(
					{
						name: __( 'Filterable Grid', 'basalio' ),
						description: __( 'A portfolio grid visitors can filter by discipline. Filter buttons are generated from each item’s Category.', 'basalio' )
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
