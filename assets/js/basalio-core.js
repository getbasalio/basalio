/**
 * Basalio core, the one shared script for the whole plugin.
 *
 * The spec mandates a single shared JS file. To keep modules from stepping on
 * each other inside that one file, the core is a tiny behavior registry:
 *
 *   - Basalio.register( name, initFn ) adds a self-contained behavior.
 *   - Basalio.boot() runs every registered behavior once the DOM is ready.
 *
 * Each behavior only touches its own elements (scoped by a data attribute), so
 * adding Module 2/3 means appending a new Basalio.register(...) block below , 
 * Module 1's behavior is never edited.
 *
 * CSS does all the actual revealing (:hover / :focus-within). JS is used only
 * for the two things CSS genuinely cannot do:
 *   - touch tap-to-reveal (coarse pointers have no hover)
 *   - Escape-to-dismiss (WCAG 1.4.13 "Dismissable")
 */
( function () {
	'use strict';

	var Basalio = window.Basalio || ( window.Basalio = {} );
	Basalio.behaviors = Basalio.behaviors || {};

	/**
	 * Register a named behavior. Later modules call this without touching
	 * existing behaviors.
	 *
	 * @param {string}   name Unique behavior name.
	 * @param {Function} init Receives a root node to scope its queries to.
	 */
	Basalio.register = function ( name, init ) {
		Basalio.behaviors[ name ] = init;
	};

	/**
	 * Run every registered behavior. Behaviors are isolated: one throwing does
	 * not stop the others.
	 *
	 * @param {Document|Element} [root=document] Scope to initialise within.
	 */
	Basalio.boot = function ( root ) {
		root = root || document;
		Object.keys( Basalio.behaviors ).forEach( function ( name ) {
			try {
				Basalio.behaviors[ name ]( root );
			} catch ( e ) {
				if ( window.console && window.console.warn ) {
					window.console.warn( 'Basalio behavior "' + name + '" failed:', e );
				}
			}
		} );
	};

	var forEach = function ( list, fn ) {
		Array.prototype.forEach.call( list, fn );
	};

	/**
	 * Translation access. wp-i18n is a declared dependency of this script, so it
	 * is present in practice; the guard only keeps a mis-enqueued page from
	 * throwing and taking every behavior down with it. Falling back to the English
	 * source string matches the pre-i18n behavior exactly.
	 */
	var i18n = ( window.wp && window.wp.i18n ) ? window.wp.i18n : null;

	/* ---------------------------------------------------------------------
	 * Module 1, Grid Reveal
	 * ------------------------------------------------------------------- */
	Basalio.register( 'grid-reveal', function ( root ) {
		var items = root.querySelectorAll(
			'[data-basalio-grid-reveal]:not([data-basalio-ready])'
		);

		forEach( items, function ( fig ) {
			var toggle = fig.querySelector( '.basalio-grid-reveal__toggle' );
			if ( ! toggle ) {
				return;
			}

			fig.setAttribute( 'data-basalio-ready', '' );

			var setExpanded = function ( expanded ) {
				toggle.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
			};

			// Touch / coarse-pointer tap-to-reveal. Hover doesn't exist here,
			// so a tap toggles the revealed state. (On fine pointers CSS :hover
			// already handles reveal; this click just mirrors the state.)
			// Skipped when the tile is a link or a lightbox trigger, so a tap
			// navigates / expands instead of just toggling the caption.
			var isNav = 'A' === toggle.tagName || toggle.hasAttribute( 'data-basalio-lightbox' );
			if ( ! isNav ) {
				toggle.addEventListener( 'click', function () {
					var revealed = fig.classList.toggle( 'is-revealed' );
					fig.removeAttribute( 'data-basalio-dismissed' );
					setExpanded( revealed );
				} );
			}

			// Keyboard focus reveals via CSS :focus-within; we only keep the
			// ARIA state and clear any prior dismissal in sync.
			toggle.addEventListener( 'focus', function () {
				fig.removeAttribute( 'data-basalio-dismissed' );
				setExpanded( true );
			} );

			toggle.addEventListener( 'blur', function () {
				fig.classList.remove( 'is-revealed' );
				fig.removeAttribute( 'data-basalio-dismissed' );
				setExpanded( false );
			} );

			// WCAG 1.4.13 Dismissable: Escape hides the revealed caption
			// without moving the pointer or focus. data-basalio-dismissed wins
			// over :hover / :focus-within in CSS.
			fig.addEventListener( 'keydown', function ( e ) {
				if ( 'Escape' === e.key || 'Esc' === e.key ) {
					fig.setAttribute( 'data-basalio-dismissed', '' );
					fig.classList.remove( 'is-revealed' );
					setExpanded( false );
				}
			} );

			// Once the pointer leaves, clear the dismissal so a later hover
			// works again.
			fig.addEventListener( 'mouseleave', function () {
				fig.removeAttribute( 'data-basalio-dismissed' );
			} );
		} );
	} );

	/* ---------------------------------------------------------------------
	 * Module 2, Case Study Transition
	 *
	 * The visual morph is pure CSS (cross-document View Transitions). This
	 * behavior only does the two things CSS cannot:
	 *   - remember an intentional source click, so the destination knows the
	 *     visitor arrived via a transition (a per-tab sessionStorage handshake);
	 *   - move focus to the destination page's H1 after arrival (WCAG 2.4.3),
	 *     waiting for the transition to finish when one is running.
	 *
	 * It no-ops on pages that have neither a source nor a pending handshake, so
	 * living in the shared file costs effectively nothing where it isn't used.
	 * ------------------------------------------------------------------- */
	Basalio.register( 'case-study-transition', function ( root ) {
		var FLAG = 'basalio-cst-pending';

		// --- Source side: record a real primary click before navigating. ---
		var sources = root.querySelectorAll(
			'[data-basalio-cst-source]:not([data-basalio-ready])'
		);
		forEach( sources, function ( link ) {
			link.setAttribute( 'data-basalio-ready', '' );
			link.addEventListener( 'click', function ( e ) {
				// Ignore modified / non-primary clicks (new tab, download, etc.):
				// those don't navigate this tab, so no focus handoff is wanted.
				if (
					e.defaultPrevented ||
					e.button !== 0 ||
					e.metaKey || e.ctrlKey || e.shiftKey || e.altKey
				) {
					return;
				}
				try {
					sessionStorage.setItem( FLAG, '1' );
				} catch ( err ) {}
			} );
		} );

		// --- Destination side: only if we arrived from a Basalio source. ---
		var pending = null;
		try {
			pending = sessionStorage.getItem( FLAG );
		} catch ( err ) {}
		if ( ! pending ) {
			return;
		}
		try {
			sessionStorage.removeItem( FLAG );
		} catch ( err ) {}

		var focused = false;
		var hero = document.querySelector( '[data-basalio-cst-hero]' );
		var focusTarget = hero ? ( hero.getAttribute( 'data-basalio-cst-focus-target' ) || 'next panel' ) : 'next panel';
		var announceChange = hero ? ( hero.getAttribute( 'data-basalio-cst-announce' ) || 'polite' ) : 'polite';

		var announced = false;
		var announce = function () {
			if ( announced || 'polite' !== announceChange ) {
				return;
			}
			announced = true;
			var status = document.createElement( 'div' );
			status.setAttribute( 'role', 'status' );
			status.setAttribute( 'aria-live', 'polite' );
			status.className = 'basalio-screen-reader-text';
			status.textContent = i18n ? i18n.__( 'Case study loaded', 'basalio' ) : 'Case study loaded';
			document.body.appendChild( status );
			window.setTimeout( function () {
				if ( status.parentNode ) {
					status.parentNode.removeChild( status );
				}
			}, 1000 );
		};

		var moveFocus = function () {
			if ( focused || 'none' === focusTarget ) {
				announce();
				return;
			}
			focused = true;

			var target = null;
			if ( 'top' === focusTarget && hero ) {
				target = hero;
			} else {
				target = document.querySelector( 'h1' );
			}
			if ( ! target ) {
				announce();
				return;
			}
			if ( ! target.hasAttribute( 'tabindex' ) ) {
				target.setAttribute( 'tabindex', '-1' );
			}
			target.classList.add( 'basalio-cst-focus-target' );
			target.focus( { preventScroll: true } );
			announce();
		};

		var prefersReduced = window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		var supportsCrossDocVT = ( 'onpagereveal' in window );

		if ( supportsCrossDocVT && ! prefersReduced ) {
			// Wait for the cross-document transition to settle, then focus.
			var fallbackTimer = window.setTimeout( moveFocus, 400 );
			window.addEventListener( 'pagereveal', function ( e ) {
				// The transition handler is now responsible; cancel the fallback.
				window.clearTimeout( fallbackTimer );
				if ( e && e.viewTransition && e.viewTransition.finished ) {
					e.viewTransition.finished.then( moveFocus, moveFocus );
				} else {
					moveFocus();
				}
			} );
		} else {
			// No view transition (unsupported, or reduced motion → plain nav):
			// focus once the document is ready.
			if ( document.readyState === 'loading' ) {
				document.addEventListener( 'DOMContentLoaded', moveFocus );
			} else {
				moveFocus();
			}
		}
	} );

	/* ---------------------------------------------------------------------
	 * Module 3, Magnetic Button (v2.0)
	 *
	 * Pointer-only decorative pull toward the cursor. The button is a real,
	 * keyboard-operable link regardless; this behavior only enhances fine
	 * pointers with hover, and bails entirely on touch or under reduced motion.
	 * It sets --basalio-mag-x / -y; the CSS translate does the rest.
	 * ------------------------------------------------------------------- */
	Basalio.register( 'magnetic-button', function ( root ) {
		// Off by default on touch / coarse pointers.
		var fine = window.matchMedia &&
			window.matchMedia( '(hover: hover) and (pointer: fine)' );
		if ( ! fine || ! fine.matches ) {
			return;
		}

		var reduce = window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' );

		var buttons = root.querySelectorAll(
			'[data-basalio-magnetic]:not([data-basalio-ready])'
		);

		forEach( buttons, function ( btn ) {
			btn.setAttribute( 'data-basalio-ready', '' );
			var strength = parseFloat( btn.getAttribute( 'data-basalio-magnetic' ) ) || 18;

			var reset = function () {
				btn.style.setProperty( '--basalio-mag-x', '0px' );
				btn.style.setProperty( '--basalio-mag-y', '0px' );
			};

			btn.addEventListener( 'pointermove', function ( e ) {
				// Respect reduced-motion even if the preference changes mid-session.
				if ( reduce && reduce.matches ) {
					reset();
					return;
				}
				var r = btn.getBoundingClientRect();
				if ( ! r.width || ! r.height ) {
					return;
				}
				var x = ( ( e.clientX - ( r.left + r.width / 2 ) ) / ( r.width / 2 ) ) * strength;
				var y = ( ( e.clientY - ( r.top + r.height / 2 ) ) / ( r.height / 2 ) ) * strength;
				btn.style.setProperty( '--basalio-mag-x', x.toFixed( 1 ) + 'px' );
				btn.style.setProperty( '--basalio-mag-y', y.toFixed( 1 ) + 'px' );
			} );

			// Spring back whenever the pointer leaves or focus moves away.
			btn.addEventListener( 'pointerleave', reset );
			btn.addEventListener( 'blur', reset );
		} );
	} );

	/* ---------------------------------------------------------------------
	 * Module 4, Filterable Grid (v2.0)
	 *
	 * Progressive enhancement: server renders every item visible plus real
	 * <button> filters. This wires the buttons to show/hide items (via the
	 * `hidden` attribute, instant, and it drops hidden items from the tab
	 * order + a11y tree) and updates a role="status" region so screen readers
	 * hear the new count. With JS off, all items simply stay visible.
	 * ------------------------------------------------------------------- */
	Basalio.register( 'filterable-grid', function ( root ) {
		var grids = root.querySelectorAll(
			'[data-basalio-filterable-grid]:not([data-basalio-ready])'
		);

		forEach( grids, function ( grid ) {
			grid.setAttribute( 'data-basalio-ready', '' );

			var filters = grid.querySelectorAll( '.basalio-filterable-grid__filter' );
			var items   = grid.querySelectorAll( '.basalio-grid-item' );
			var status  = grid.querySelector( '.basalio-filterable-grid__status' );
			var total   = items.length;
			var resultCount = grid.getAttribute( 'data-basalio-result-count' ) || 'polite';

			if ( status ) {
				if ( 'polite' === resultCount ) {
					status.setAttribute( 'aria-live', 'polite' );
				} else {
					status.removeAttribute( 'aria-live' );
				}
			}

			if ( ! filters.length || ! total ) {
				return;
			}

			// Roving tabindex: exactly one filter is in the tab order at a time.
			var setRoving = function ( activeBtn ) {
				forEach( filters, function ( f ) {
					f.setAttribute( 'tabindex', f === activeBtn ? '0' : '-1' );
				} );
			};

			var apply = function ( slug, activeBtn ) {
				var shown = 0;
				forEach( items, function ( item ) {
					var match = ( '*' === slug ) ||
						( item.getAttribute( 'data-basalio-item-category' ) === slug );
					item.hidden = ! match;
					if ( match ) {
						shown++;
					}
				} );

				forEach( filters, function ( f ) {
					var active = ( f === activeBtn );
					f.classList.toggle( 'is-active', active );
					f.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
				} );
				setRoving( activeBtn );

				if ( status ) {
					// Server-rendered initial text is translated via _n() in PHP. This
					// must be too, or the count silently reverts to English the first
					// time a visitor touches a filter.
					var tmpl = i18n
						? i18n._n( 'Showing %1$s of %2$s project.', 'Showing %1$s of %2$s projects.', shown, 'basalio' )
						: ( 1 === shown ? 'Showing %1$s of %2$s project.' : 'Showing %1$s of %2$s projects.' );
					status.textContent = tmpl
						.replace( '%1$s', shown )
						.replace( '%2$s', total );
				}
			};

			forEach( filters, function ( f ) {
				f.addEventListener( 'click', function () {
					apply( f.getAttribute( 'data-basalio-filter' ), f );
				} );
			} );

			// Arrow-key navigation across the filter toolbar (WAI-ARIA toolbar
			// pattern): arrows move focus, Enter/Space (native button) filters.
			var initialActive = null;
			forEach( filters, function ( f ) {
				if ( f.classList.contains( 'is-active' ) ) {
					initialActive = f;
				}
			} );
			setRoving( initialActive || filters[ 0 ] );

			var focusAt = function ( idx ) {
				var n = filters.length;
				idx = ( idx + n ) % n;
				setRoving( filters[ idx ] );
				filters[ idx ].focus();
			};

			forEach( filters, function ( f ) {
				f.addEventListener( 'keydown', function ( e ) {
					var idx = Array.prototype.indexOf.call( filters, f );
					switch ( e.key ) {
						case 'ArrowRight':
						case 'ArrowDown':
							e.preventDefault();
							focusAt( idx + 1 );
							break;
						case 'ArrowLeft':
						case 'ArrowUp':
							e.preventDefault();
							focusAt( idx - 1 );
							break;
						case 'Home':
							e.preventDefault();
							focusAt( 0 );
							break;
						case 'End':
							e.preventDefault();
							focusAt( filters.length - 1 );
							break;
					}
				} );
			} );
		} );
	} );

	/* ---------------------------------------------------------------------
	 * Module 5, Before / After (v2.0)
	 *
	 * Drives a real ARIA slider (role="slider"): pointer drag + full keyboard
	 * (arrows / Home / End / PageUp / PageDown). Position is a single % custom
	 * property. With JS off, both images render split at the start position.
	 * ------------------------------------------------------------------- */
	Basalio.register( 'before-after', function ( root ) {
		var comps = root.querySelectorAll(
			'[data-basalio-before-after]:not([data-basalio-ready])'
		);

		forEach( comps, function ( comp ) {
			var frame  = comp.querySelector( '.basalio-before-after__frame' );
			var handle = comp.querySelector( '.basalio-before-after__handle' );
			if ( ! frame || ! handle ) {
				return;
			}
			comp.setAttribute( 'data-basalio-ready', '' );

			var step = parseFloat( handle.getAttribute( 'data-basalio-step' ) ) || 2;

			var beforeLabel = handle.getAttribute( 'data-basalio-before-label' ) || '';
			var afterLabel  = handle.getAttribute( 'data-basalio-after-label' ) || '';

			var setPos = function ( p ) {
				p = Math.max( 0, Math.min( 100, p ) );
				var rounded = Math.round( p );
				comp.style.setProperty( '--basalio-ba-pos', p + '%' );
				handle.setAttribute( 'aria-valuenow', rounded );
				var text = rounded + '%';
				if ( beforeLabel || afterLabel ) {
					text = rounded + '%: ' + ( i18n ? i18n.__( 'Before:', 'basalio' ) : 'Before:' ) + ' ' + beforeLabel + ', ' + ( i18n ? i18n.__( 'After:', 'basalio' ) : 'After:' ) + ' ' + afterLabel;
				}
				handle.setAttribute( 'aria-valuetext', text );
			};

			var posFromClientX = function ( clientX ) {
				var r = frame.getBoundingClientRect();
				if ( ! r.width ) {
					return 50;
				}
				return ( ( clientX - r.left ) / r.width ) * 100;
			};

			// --- Pointer drag (mouse / touch / pen) ---
			var dragging = false;

			handle.addEventListener( 'pointerdown', function ( e ) {
				dragging = true;
				if ( handle.setPointerCapture ) {
					try { handle.setPointerCapture( e.pointerId ); } catch ( err ) {}
				}
				// preventDefault() below stops text selection during the drag, but it
				// also suppresses the browser's default focus, which would leave the
				// slider unreachable by keyboard after a click/tap. Focus it ourselves
				// so the arrow-key contract works once the user has grabbed the handle.
				e.preventDefault();
				handle.focus();
			} );
			handle.addEventListener( 'pointermove', function ( e ) {
				if ( dragging ) {
					setPos( posFromClientX( e.clientX ) );
				}
			} );
			var endDrag = function ( e ) {
				dragging = false;
				if ( handle.releasePointerCapture && e.pointerId != null ) {
					try { handle.releasePointerCapture( e.pointerId ); } catch ( err ) {}
				}
			};
			handle.addEventListener( 'pointerup', endDrag );
			handle.addEventListener( 'pointercancel', endDrag );

			// Press anywhere else on the frame to jump the divider there.
			frame.addEventListener( 'pointerdown', function ( e ) {
				if ( e.target === handle || handle.contains( e.target ) ) {
					return;
				}
				e.preventDefault();
				setPos( posFromClientX( e.clientX ) );
				handle.focus();
			} );

			// --- Keyboard (the ARIA slider contract) ---
			handle.addEventListener( 'keydown', function ( e ) {
				var now = parseFloat( handle.getAttribute( 'aria-valuenow' ) );
				if ( isNaN( now ) ) {
					now = 50;
				}
				var handled = true;
				switch ( e.key ) {
					case 'ArrowLeft':
					case 'ArrowDown':
						setPos( now - step );
						break;
					case 'ArrowRight':
					case 'ArrowUp':
						setPos( now + step );
						break;
					case 'PageDown':
						setPos( now - 10 );
						break;
					case 'PageUp':
						setPos( now + 10 );
						break;
					case 'Home':
						setPos( 0 );
						break;
					case 'End':
						setPos( 100 );
						break;
					default:
						handled = false;
				}
				if ( handled ) {
					e.preventDefault();
				}
			} );
		} );
	} );

	/* ---------------------------------------------------------------------
	 * Module 6 (v2.0), Custom Cursor
	 *
	 * A decorative accent that trails the pointer inside an area. Fine pointers
	 * with hover only, never on touch, never under reduced motion. The native
	 * cursor is untouched and the accent is pointer-events:none, so it never
	 * blocks a click.
	 * ------------------------------------------------------------------- */
	Basalio.register( 'custom-cursor', function ( root ) {
		var fine = window.matchMedia &&
			window.matchMedia( '(hover: hover) and (pointer: fine)' );
		if ( ! fine || ! fine.matches ) {
			return;
		}

		var reduce = window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' );

		var areas = root.querySelectorAll(
			'[data-basalio-custom-cursor]:not([data-basalio-ready])'
		);
		forEach( areas, function ( area ) {
			area.setAttribute( 'data-basalio-ready', '' );
			area.addEventListener( 'pointermove', function ( e ) {
				// Stop trailing if reduced motion is enabled mid-session.
				if ( reduce && reduce.matches ) {
					area.classList.remove( 'is-cursor-active' );
					return;
				}
				var r = area.getBoundingClientRect();
				area.style.setProperty( '--basalio-cursor-x', ( e.clientX - r.left ) + 'px' );
				area.style.setProperty( '--basalio-cursor-y', ( e.clientY - r.top ) + 'px' );
				area.classList.add( 'is-cursor-active' );
			} );
			area.addEventListener( 'pointerleave', function () {
				area.classList.remove( 'is-cursor-active' );
			} );
		} );
	} );

	/* ---------------------------------------------------------------------
	 * Module 7 (v2.0), Scroll Sequence
	 *
	 * Maps scroll progress through a tall wrapper to the active frame. The
	 * Previous and Next buttons step frames manually, so the sequence is usable
	 * without scrolling (WCAG 2.5.1). Under reduced motion the scroll driver is
	 * skipped and only the buttons drive it.
	 * ------------------------------------------------------------------- */
	Basalio.register( 'scroll-sequence', function ( root ) {
		var seqs = root.querySelectorAll(
			'[data-basalio-scroll-sequence]:not([data-basalio-ready])'
		);
		var reduce = window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' );

		forEach( seqs, function ( seq ) {
			seq.setAttribute( 'data-basalio-ready', '' );
			var frames = seq.querySelectorAll( '.basalio-scroll-sequence__frame' );
			var status = seq.querySelector( '.basalio-scroll-sequence__status' );
			var announcer = seq.querySelector( '[data-basalio-seq-announcer]' );
			var prev = seq.querySelector( '[data-basalio-seq-prev]' );
			var next = seq.querySelector( '[data-basalio-seq-next]' );
			var total = frames.length;
			if ( ! total ) {
				return;
			}
			var current = 0;

			// The visible counter updates on every frame change, including the
			// dozens produced by scrolling. The live region is written to ONLY when
			// `announce` is true, i.e. from the Previous/Next buttons. Announcing on
			// scroll would interrupt a screen reader continuously (WCAG 4.1.3
			// status messages are for meaningful change, not every scroll tick).
			var format = function ( i ) {
				var fmt = ( status && status.getAttribute( 'data-basalio-seq-format' ) ) || 'Frame {current} of {total}';
				return fmt
					.replace( '{current}', i + 1 )
					.replace( '{total}', total );
			};

			var show = function ( i, announce ) {
				i = Math.max( 0, Math.min( total - 1, i ) );
				if ( i === current ) {
					return;
				}
				frames[ current ].classList.remove( 'is-active' );
				frames[ i ].classList.add( 'is-active' );
				current = i;
				var text = format( i );
				if ( status ) {
					status.textContent = text;
				}
				if ( announce && announcer ) {
					announcer.textContent = text;
				}
			};

			// Scroll the page so the scroll driver lands on the requested frame.
			// This keeps scroll position and active frame in sync; without it the
			// next scroll tick would immediately reset the frame back to the one
			// matching the unchanged scroll position.
			var scrollToFrame = function ( i ) {
				i = Math.max( 0, Math.min( total - 1, i ) );
				if ( i === current ) {
					return;
				}
				var seqRect = seq.getBoundingClientRect();
				var scrollable = seq.offsetHeight - window.innerHeight;
				if ( scrollable <= 0 ) {
					return;
				}
				var targetTop = window.scrollY + seqRect.top + ( i / ( total - 1 ) ) * scrollable;
				window.scrollTo( {
					top: targetTop,
					behavior: ( reduce && reduce.matches ) ? 'auto' : 'smooth'
				} );
			};

			if ( prev ) {
				prev.addEventListener( 'click', function () {
					var target = current - 1;
					scrollToFrame( target );
					show( target, true );
				} );
			}
			if ( next ) {
				next.addEventListener( 'click', function () {
					var target = current + 1;
					scrollToFrame( target );
					show( target, true );
				} );
			}

			// Scroll-driven scrubbing: enabled/disabled dynamically so a user who
			// turns reduced motion on or off mid-session gets the right driver.
			var ticking = false;
			var onScroll = function () {
				if ( ticking ) {
					return;
				}
				ticking = true;
				window.requestAnimationFrame( function () {
					ticking = false;
					var r = seq.getBoundingClientRect();
					var scrollable = seq.offsetHeight - window.innerHeight;
					if ( scrollable <= 0 ) {
						return;
					}
					var progress = Math.max( 0, Math.min( 1, -r.top / scrollable ) );
					// No announce flag: scroll updates the visible counter only.
					show( Math.round( progress * ( total - 1 ) ) );
				} );
			};

			var scrollListenerActive = false;
			var updateScrollDriver = function () {
				if ( reduce && reduce.matches ) {
					if ( scrollListenerActive ) {
						window.removeEventListener( 'scroll', onScroll, { passive: true } );
						scrollListenerActive = false;
					}
				} else {
					if ( ! scrollListenerActive ) {
						window.addEventListener( 'scroll', onScroll, { passive: true } );
						scrollListenerActive = true;
						onScroll();
					}
				}
			};
			if ( reduce ) {
				reduce.addEventListener( 'change', updateScrollDriver );
			}
			updateScrollDriver();
		} );
	} );

	/* ---------------------------------------------------------------------
	 * Shared, Lightbox
	 *
	 * Any element carrying data-basalio-lightbox (its value is the full-size
	 * image URL) opens that image in a native modal <dialog>. One dialog is
	 * built lazily and reused by every image block that turns on "expand" mode.
	 * Using <dialog> gives the browser-native focus trap, Escape-to-close, and
	 * inert background, so no custom Tab interception is required.
	 * ------------------------------------------------------------------- */
	Basalio.register( 'lightbox', function ( root ) {
		var triggers = root.querySelectorAll(
			'[data-basalio-lightbox]:not([data-basalio-lb-ready])'
		);
		if ( ! triggers.length ) {
			return;
		}

		var overlay = document.getElementById( 'basalio-lightbox' );
		var imgEl, captionEl, closeBtn, lastFocused;

		var close = function () {
			if ( ! overlay || ! overlay.open ) {
				return;
			}
			overlay.classList.remove( 'is-open' );
			var prefersReduced = window.matchMedia &&
				window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
			window.setTimeout( function () {
				if ( overlay && overlay.open ) {
					overlay.close();
				}
			}, prefersReduced ? 0 : 220 );
		};

		var build = function () {
			overlay = document.createElement( 'dialog' );
			overlay.id = 'basalio-lightbox';
			overlay.className = 'basalio-lightbox';
			overlay.setAttribute( 'aria-label', i18n ? i18n.__( 'Image viewer', 'basalio' ) : 'Image viewer' );
			overlay.innerHTML =
				'<figure class="basalio-lightbox__figure">' +
					'<img class="basalio-lightbox__img" alt="" />' +
					'<figcaption class="basalio-lightbox__caption"></figcaption>' +
				'</figure>' +
				'<button type="button" class="basalio-lightbox__close" aria-label="' +
					( i18n ? i18n.__( 'Close image viewer', 'basalio' ) : 'Close image viewer' ) +
				'">&#215;</button>';
			document.body.appendChild( overlay );
			imgEl = overlay.querySelector( '.basalio-lightbox__img' );
			captionEl = overlay.querySelector( '.basalio-lightbox__caption' );
			closeBtn = overlay.querySelector( '.basalio-lightbox__close' );

			closeBtn.addEventListener( 'click', close );
			// Clicks on the dialog itself (the ::backdrop area) close the lightbox;
			// clicks on the figure or close button bubble from inside and are ignored.
			overlay.addEventListener( 'click', function ( e ) {
				if ( e.target === overlay ) {
					close();
				}
			} );
			overlay.addEventListener( 'close', function () {
				document.documentElement.style.overflow = '';
				if ( lastFocused && lastFocused.focus ) {
					lastFocused.focus();
				}
			} );
		};

		var open = function ( src, alt ) {
			if ( ! overlay ) {
				build();
			}
			lastFocused = document.activeElement;
			imgEl.setAttribute( 'src', src );
			imgEl.setAttribute( 'alt', alt || '' );
			captionEl.textContent = alt || '';
			captionEl.hidden = ! alt;
			document.documentElement.style.overflow = 'hidden';
			overlay.showModal();
			window.requestAnimationFrame( function () {
				overlay.classList.add( 'is-open' );
			} );
			closeBtn.focus();
		};

		forEach( triggers, function ( t ) {
			t.setAttribute( 'data-basalio-lb-ready', '' );
			t.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				open(
					t.getAttribute( 'data-basalio-lightbox' ),
					t.getAttribute( 'data-basalio-lightbox-alt' )
				);
			} );
		} );
	} );

	/* ---------------------------------------------------------------------
	 * Future modules register below. Do not edit the behaviors above.
	 * e.g. Basalio.register( 'text-reveal', function ( root ) { ... } );
	 * ------------------------------------------------------------------- */

	/* -------------------------------------------------------------------
	 * Text Reveal scroll trigger
	 *
	 * CSS handles the actual motion; JS only decides WHEN the motion starts.
	 * Under reduced motion, or when IntersectionObserver is unavailable, the
	 * block is made visible immediately so content is never trapped invisible.
	 * ------------------------------------------------------------------- */
	Basalio.register( 'motion-reveal', function ( root ) {
		var items = root.querySelectorAll(
			'[data-basalio-motion-reveal]:not([data-basalio-ready])'
		);

		if ( ! items.length ) {
			return;
		}

		var reduce = window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' );

		var makeVisible = function ( el ) {
			el.classList.add( 'is-visible' );
			el.setAttribute( 'data-basalio-ready', '' );
		};

		forEach( items, function ( item ) {
			if ( reduce && reduce.matches ) {
				makeVisible( item );
				return;
			}

			if ( ! ( 'IntersectionObserver' in window ) ) {
				makeVisible( item );
				return;
			}

			var threshold = 0.5;
			if ( window.getComputedStyle ) {
				var raw = window.getComputedStyle( item )
					.getPropertyValue( '--basalio-motion-scroll-threshold' );
				if ( raw ) {
					var parsed = parseFloat( raw );
					if ( ! isNaN( parsed ) ) {
						threshold = parsed;
					}
				}
			}
			threshold = Math.max( 0, Math.min( 1, threshold ) );

			var observer = new IntersectionObserver(
				function ( entries ) {
					forEach( entries, function ( entry ) {
						if ( entry.isIntersecting ) {
							makeVisible( entry.target );
							observer.unobserve( entry.target );
						}
					} );
				},
				{ threshold: threshold }
			);

			observer.observe( item );
		} );
	} );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			Basalio.boot();
		} );
	} else {
		Basalio.boot();
	}
}() );
