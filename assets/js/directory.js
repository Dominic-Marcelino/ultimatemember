if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.frontend ) !== 'object' ) {
	UM.frontend = {};
}

UM.frontend.directories = {
	list: [],
	getHash: function( $directory ) {
		return $directory.data( 'hash' );
	},
	filtersHash: function( str ) {
		let hash = 0;
		for (let i = 0; i < str.length; i++) {
			const char = str.charCodeAt(i);
			hash = ((hash << 5) - hash) + char;
			hash |= 0; // Convert to 32-bit integer
		}
		return hash;
	},
	filters: {
		checkEmpty: function ( $filtersForm ) {
			let $clearFilters = $filtersForm.find('.um-clear-filters-a');
			let $filters = $filtersForm.find('.um-search-filter-field');

			let emptyFilters = 0;
			$filters.each( function () {
				let $filterWrapper = jQuery(this).parents( '.um-search-filter' );

				let filterType = $filterWrapper.data( 'filter-type' );

				if ( 'text' === filterType ) {
					let filterValue = jQuery(this).val();
					if ( '' === filterValue ) {
						emptyFilters++;
					}
				} else if ( 'select' === filterType ) {
					let filterValue = jQuery(this).val();
					if ( 0 === filterValue.length ) {
						emptyFilters++;
					}
				} else if ( 'slider' === filterType ) {
					// Get from "from" and "to" fields.
					let $rangeContainer = jQuery(this).parents( '.um-range-container' );
					const fromSlider = $rangeContainer.find( '.um-from-slider' )[0];
					const toSlider = $rangeContainer.find( '.um-to-slider' )[0];

					// Empty when selected full range.
					if ( parseInt( fromSlider.value, 10) === parseInt( fromSlider.min, 10 ) &&
						parseInt( toSlider.value, 10) === parseInt( toSlider.max, 10 ) ) {
						emptyFilters++;
					}
				} else if ( 'datepicker' === filterType ) {
					let $rangeContainer = jQuery(this).parents( '.um-date-range-row' );
					const fromDate = $rangeContainer.find( '[data-range="from"]' );
					const toDate = $rangeContainer.find( '[data-range="to"]' );

					if ( '' === fromDate.val() && '' === toDate.val() ) {
						emptyFilters++;
					}
				} else if ( 'timepicker' === filterType ) {
					let $rangeContainer = jQuery(this).parents( '.um-time-range-row' );
					const fromTime = $rangeContainer.find( '[data-range="from"]' );
					const toTime = $rangeContainer.find( '[data-range="to"]' );

					if ( '' === fromTime.val() && '' === toTime.val() ) {
						emptyFilters++;
					}
				}
			});

			// Show clear filters button in case where are not empty filters.
			if ( $filters.length === emptyFilters ) {
				$clearFilters.addClass( 'um-hidden' ).prop( 'disabled', true );
			} else {
				$clearFilters.removeClass( 'um-hidden' ).prop( 'disabled', false );
			}

			return $filters.length === emptyFilters;
		}
	},
};

UM.frontend.directory = function( hash ) {
	this.hash = hash;
	this.wrapper = jQuery('.um-directory[data-hash="' + hash + '"]');
	this.defaultOrder = this.wrapper.data('default-order');
};

UM.frontend.directory.prototype = {
	hash: '',
	wrapper: null,
	busy: false,
	lastResponse: null,
	page: 1,
	totalPages: 0,
	search: '',
	order: '',
	defaultOrder: '',
	filters: {},
	getDataFromURL: function( dataKey ) {
		let hash = this.hash;
		let data = {};

		let urlData = UM.frontend.url.parseData();
		jQuery.each( urlData, function( key ) {
			if ( key.indexOf( '_' + hash ) !== -1 && urlData[ key ] !== '' ) {
				data[ key.replace( '_' + hash, '' ) ] = urlData[ key ];
			}
		});

		if ( ! dataKey ) {
			return data;
		}

		if ( typeof data[ dataKey ] !== 'undefined' ) {
			try {
				data[ dataKey ] = decodeURIComponent( data[ dataKey ] );
			} catch(e) { // catches a malformed URI
				console.error(e);
			}
		}

		return data[ dataKey ];
	},
	setDataToURL: function( key, value ) {
		let hash = this.hash;
		let data = this.getDataFromURL();

		let newQuery = {};

		if ( Array.isArray( value ) ) {
			jQuery.each( value, function( i ) {
				value[ i ] = encodeURIComponent( value[ i ] );
			});
			value = value.join( '||' );
		} else if ( ! Number.isFinite( value ) ) {
			value = value.split( '||' );
			jQuery.each( value, function( i ) {
				value[ i ] = encodeURIComponent( value[ i ] );
			});
			value = value.join( '||' );
		}

		if ( value !== '' ) {
			newQuery[ key + '_' + hash ] = value;
		}
		jQuery.each( data, function( dataKey ) {
			if ( key === dataKey ) {
				if ( value !== '' ) {
					newQuery[ dataKey + '_' + hash ] = value;
				}
			} else {
				newQuery[ dataKey + '_' + hash ] = data[ dataKey ];
			}
		});

		// added data of other directories to the url
		jQuery.each( UM.frontend.directories.list, function( dirHash ) {
			if ( dirHash !== hash ) {
				let directoryObj = UM.frontend.directories.list[ dirHash ];
				let directoryData = directoryObj.getDataFromURL();

				jQuery.each( directoryData, function( data_key ) {
					newQuery[ data_key + '_' + dirHash ] = directoryData[ data_key ];
				});
			}
		});

		let queryStrings = [];
		jQuery.each( newQuery, function( data_key ) {
			queryStrings.push( data_key + '=' + newQuery[ data_key ] );
		});

		queryStrings = wp.hooks.applyFilters( 'um_member_directory_url_attrs', queryStrings );

		let queryString = '?' + queryStrings.join( '&' );
		if ( queryString === '?' ) {
			queryString = '';
		}

		window.history.pushState("string", "UM Member Directory", window.location.origin + window.location.pathname + queryString );
	},
	setLastResponse: function ( data ) {
		this.lastResponse = data;
	},
	setTotalPages: function ( totalPages ) {
		this.totalPages = totalPages;
	},
	getLastResponse: function () {
		return this.lastResponse;
	},
	isBusy: function () {
		return this.busy;
	},
	getHash: function () {
		return this.hash;
	},
	getWrapper: function () {
		return this.wrapper;
	},
	preloaderShow: function () {
		this.busy = true;
		this.wrapper.addClass('um-processing');
	},
	preloaderHide: function () {
		this.busy = false;
		this.wrapper.removeClass('um-processing');
	},
	getPage: function () {
		return this.page;
	},
	setPage: function ( page, args ) {
		let ignoreURL = false;
		if ( 'undefined' !== typeof( args ) && args.hasOwnProperty('ignoreURL') && false !== args.ignoreURL ) {
			ignoreURL = true;
		}

		let updateUI = false;
		if ( 'undefined' !== typeof( args ) && args.hasOwnProperty('updateUI') && false !== args.updateUI ) {
			updateUI = true;
		}

		if ( updateUI ) {
			this.wrapper.find( '.um-members-pagination-box' ).addClass( 'um-display-none' );
		}

		page = parseInt( page );
		page = ! page ? 1 : page;

		let totalPages = parseInt( this.wrapper.data('total_pages') );
		if ( totalPages ) {
			totalPages = ! totalPages ? 1 : totalPages;
			// Page can be in the range between 1 and `totalPages`;
			page = totalPages >= page ? page : totalPages;
		}

		this.page = page;

		if ( ! ignoreURL ) {
			if ( 1 === page ) {
				this.setDataToURL( 'page', '' );
			} else {
				this.setDataToURL( 'page', page );
			}
		}
	},
	getOrder: function () {
		return this.order;
	},
	setOrder: function ( order, args ) {
		let ignoreURL = false;
		if ( 'undefined' !== typeof( args ) && args.hasOwnProperty('ignoreURL') && false !== args.ignoreURL ) {
			ignoreURL = true;
		}

		let updateUI = false;
		if ( 'undefined' !== typeof( args ) && args.hasOwnProperty('updateUI') && false !== args.updateUI ) {
			updateUI = true;
		}

		if ( updateUI ) {
			// Sorting from history
			if ( this.wrapper.find( '.um-member-directory-sorting' ).length ) {
				let sort_dropdown = this.wrapper.find( '.um-new-dropdown[data-element=".um-member-directory-sorting-a"]' );
				sort_dropdown.find('a').data('selected', 0).prop('data-selected', 0).attr('data-selected', 0);
				sort_dropdown.find('a[data-value="' + order + '"]').data('selected', 1).prop('data-selected', 1).attr('data-selected', 1);
				this.wrapper.find('.um-member-directory-sorting-a').find('> a').html( sort_dropdown.find('a[data-value="' + order + '"]').html() );
			}
		}

		this.order = order;

		if ( ! ignoreURL ) {
			if ( this.defaultOrder === order ) {
				this.setDataToURL( 'sort', '' );
			} else {
				this.setDataToURL( 'sort', order );
			}
		}
	},
	getSearch: function () {
		return this.search;
	},
	setSearch: function ( search, args ) {
		let ignoreURL = false;
		if ( 'undefined' !== typeof( args ) && args.hasOwnProperty('ignoreURL') && false !== args.ignoreURL ) {
			ignoreURL = true;
		}

		let updateUI = false;
		if ( 'undefined' !== typeof( args ) && args.hasOwnProperty('updateUI') && false !== args.updateUI ) {
			updateUI = true;
		}

		this.search = search;

		if ( updateUI ) {
			let searchVal = this.getDataFromURL( 'search' );
			if ( 'undefined' === typeof searchVal ) {
				searchVal = '';
			}
			this.wrapper.find('.um-search-line').val( searchVal );
		}

		if ( ! ignoreURL ) {
			if ( '' === search ) {
				this.setDataToURL( 'search', '' );
			} else {
				this.setDataToURL( 'search', search );
			}
		}
	},
	hasSearched: function() {
		return '' !== this.getSearch() || 0 !== Object.keys( this.getFilters() ).length;
	},
	getFilters: function() {
		return this.filters;
	},
	setFilters: function ( ignoreURL = false ) {
		let instance = this;
		let directory = instance.wrapper;
		let $filtersForm = directory.find('.um-filters-form');
		let $filters = $filtersForm.find('.um-search-filter-field');

		let filters = {};
		$filters.each( function () {
			let $filterWrapper = jQuery(this).parents( '.um-search-filter' );
			let filterType = $filterWrapper.data( 'filter-type' );
			let filterName = $filterWrapper.data( 'filter-name' );

			if ( 'text' === filterType ) {
				let filterValue = UM.common.form.sanitizeValue( jQuery(this).val() );
				if ( '' !== filterValue ) {
					filters[ filterName ] = filterValue;
				}
				if ( ! ignoreURL ) {
					instance.setDataToURL( 'filter_' + filterName, filterValue );
				}
			} else if ( 'select' === filterType ) {
				let filterValueRaw = jQuery(this).val();
				let filterValue = [];
				for ( let i = 0; i < filterValueRaw.length; i++ ) {
					filterValue[i] = UM.common.form.sanitizeValue( filterValueRaw[i] );
				}

				if ( 0 !== filterValue.length ) {
					filters[ filterName ] = filterValue;
				}

				filterValue = filterValue.length ? filterValue.join( '||' ) : '';
				if ( ! ignoreURL ) {
					instance.setDataToURL( 'filter_' + filterName, filterValue );
				}
			} else if ( 'slider' === filterType ) {
				// Get from "from" and "to" fields.
				let $rangeContainer = jQuery(this).parents( '.um-range-container' );
				const fromSlider = $rangeContainer.find( '.um-from-slider' )[0];
				const toSlider = $rangeContainer.find( '.um-to-slider' )[0];

				let fromVal =  parseInt( fromSlider.value, 10);
				let fromValURL;
				if ( parseInt( fromSlider.value, 10) === parseInt( fromSlider.min, 10 ) ) {
					fromValURL = '';
				} else {
					fromValURL = parseInt( fromSlider.value, 10);
				}
				let toVal = parseInt( toSlider.value, 10);
				let toValURL;
				if ( parseInt( toSlider.value, 10) === parseInt( toSlider.max, 10 ) ) {
					toValURL = '';
				} else {
					toValURL = parseInt( toSlider.value, 10);
				}

				if ( '' !== fromValURL || '' !== toValURL ) {
					filters[ filterName ] = [ fromVal, toVal ];
				}

				if ( ! ignoreURL ) {
					instance.setDataToURL( 'filter_' + filterName + '_from', fromValURL );
					instance.setDataToURL( 'filter_' + filterName + '_to', toValURL );
				}
			} else if ( 'datepicker' === filterType ) {
				let $rangeContainer = jQuery(this).parents( '.um-date-range-row' );
				const fromDate = $rangeContainer.find( '[data-range="from"]' );
				const toDate = $rangeContainer.find( '[data-range="to"]' );

				let fromVal =  UM.common.form.sanitizeValue( fromDate.val() );
				let toVal =  UM.common.form.sanitizeValue( toDate.val() );

				if ( '' !== fromVal || '' !== toVal ) {
					filters[ filterName ] = [ fromVal, toVal ];
				}

				if ( ! ignoreURL ) {
					instance.setDataToURL( 'filter_' + filterName + '_from', fromVal );
					instance.setDataToURL( 'filter_' + filterName + '_to', toVal );
				}
			} else if ( 'timepicker' === filterType ) {
				let $rangeContainer = jQuery(this).parents( '.um-time-range-row' );
				const fromTime = $rangeContainer.find( '[data-range="from"]' );
				const toTime = $rangeContainer.find( '[data-range="to"]' );

				let fromVal =  UM.common.form.sanitizeValue( fromTime.val() );
				let toVal =  UM.common.form.sanitizeValue( toTime.val() );

				if ( '' !== fromVal || '' !== toVal ) {
					filters[ filterName ] = [ fromVal, toVal ];
				}

				if ( ! ignoreURL ) {
					instance.setDataToURL( 'filter_' + filterName + '_from', fromVal );
					instance.setDataToURL( 'filter_' + filterName + '_to', toVal );
				}
			}
		});
		this.filters = filters;

		// Disable button if all filters are empty
		if ( UM.frontend.directories.filters.checkEmpty( $filtersForm ) ) {
			$filtersForm.find('.um-apply-filters').prop( 'disabled', true );
		}
	},
	resetFilters: function () {
		this.filters = {};

		let instance = this;
		let directory = instance.wrapper;
		let $filtersForm = directory.find('.um-filters-form');
		let $filters = $filtersForm.find('.um-search-filter-field');

		$filters.each( function () {
			let $filterWrapper = jQuery(this).parents( '.um-search-filter' );
			let filterType = $filterWrapper.data( 'filter-type' );
			let filterName = $filterWrapper.data( 'filter-name' );

			if ( 'text' === filterType ) {
				instance.setDataToURL( 'filter_' + filterName, '' );
			} else if ( 'select' === filterType ) {
				instance.setDataToURL( 'filter_' + filterName, '' );
			} else if ( 'slider' === filterType || 'datepicker' === filterType || 'timepicker' === filterType ) {
				instance.setDataToURL( 'filter_' + filterName + '_from', '' );
				instance.setDataToURL( 'filter_' + filterName + '_to', '' );
			}
		});
	},
	request: function ( args ) {
		let paginationAction = false;
		if ( 'undefined' !== typeof( args ) && args.hasOwnProperty('pagination') && false !== args.pagination ) {
			paginationAction = true;
		}

		// On pagination, we use skeleton load so don't show the loader.
		if ( ! paginationAction ) {
			this.preloaderShow();
		}

		this.wrapper.find('.um-member-directory-must-search').addClass( 'um-display-none' );
		this.wrapper.find('.um-member-directory-empty-search-result' ).addClass( 'um-display-none' );
		this.wrapper.find('.um-member-directory-empty-no-search-result' ).addClass( 'um-display-none' );
		this.wrapper.find( '.um-member-directory-sorting .um-dropdown-wrapper .um-button' ).prop( 'disabled', true );
		this.wrapper.find( '.um-member-view-switcher' ).addClass( 'um-disabled' );

		// Hide wrapper and pagination on every action otherwise pagination
		if ( ! paginationAction ) {
			this.wrapper.find( '.um-members-wrapper' ).addClass( 'um-display-none' );
			this.wrapper.find( '.um-members-pagination-box' ).addClass( 'um-display-none' );
		} else {
			this.wrapper.find( '.um-members-wrapper .um-member' ).addClass( 'um-skeleton-mode' );
		}

		/**
		 * Operates with the next data:
		 *
		 * 1) Page - getting from directory data 'page'
		 * 2) Sort - getting from 'um-member-directory-sorting-options' field value
		 * 3) Search - getting from 'um-search-line' field value
		 * 4) Filters - getting from URL data by 'um_get_data_for_directory' function
		 *
		 */

		let allow = wp.hooks.applyFilters( 'um_member_directory_get_members_allow', true, this );
		if ( ! allow ) {
			setTimeout( this.request, 600, args );
			return;
		}

		var local_date = new Date();
		var gmt_hours = -local_date.getTimezoneOffset() / 60;

		var request = {
			directory_id:   this.getHash(),
			page:           this.getPage(),
			search:         this.getSearch(),
			sorting:        this.getOrder(),
			gmt_offset:     gmt_hours,
			post_refferer:  this.wrapper.data('base-post'),
			nonce:          this.wrapper.data('nonce')
		};

		console.log( this.getFilters() );

		let filters = this.getFilters();
		for ( const key in filters ) {
			request[ key ] = filters[ key ];
		}

		// if ( this.wrapper.find('.um-search-filter').length ) {
		// 	this.wrapper.find('.um-search-filter').each( function() {
		// 		var filter = jQuery(this);
		//
		// 		if ( filter.find( '.um-slider' ).length ) {
		// 			var filter_name = filter.find( '.um-slider' ).data('field_name');
		//
		// 			var value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
		// 			var value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
		// 			if ( typeof value_from != 'undefined' || typeof value_to != 'undefined' ) {
		// 				request[ filter_name ] = [ value_from, value_to ];
		// 			}
		// 		} else if ( filter.find( '.um-datepicker-filter' ).length ) {
		// 			var filter_name = filter.find( '.um-datepicker-filter' ).data('filter_name');
		// 			var value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
		// 			var value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
		// 			if (  typeof value_from != 'undefined' || typeof value_to != 'undefined') {
		// 				request[ filter_name ] = [ value_from, value_to ];
		// 			}
		// 		} else if ( filter.find( '.um-timepicker-filter' ).length ) {
		// 			var filter_name = filter.find( '.um-timepicker-filter' ).data('filter_name');
		// 			var value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
		// 			var value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
		//
		// 			if ( typeof value_from != 'undefined' ) {
		// 				var value_from = value_from.split(':');
		// 				var hours = value_from[0]*1;
		// 				if ( hours < 10 ) {
		// 					hours = '0' + hours;
		// 				}
		//
		// 				var minutes = value_from[1]*1;
		// 				if ( minutes < 10 ) {
		// 					minutes = '0' + minutes;
		// 				}
		//
		// 				value_from = hours + ':' + minutes + ':00';
		// 			}
		// 			if ( typeof value_to != 'undefined' ) {
		// 				var val_to = value_to.split(':');
		// 				var minutes = val_to[1]*1;
		//
		// 				var hours = val_to[0]*1;
		// 				if ( hours < 10 ) {
		// 					hours = '0' + hours;
		// 				}
		//
		// 				if ( minutes < 10 ) {
		// 					minutes = '0' + minutes;
		// 				}
		//
		// 				value_to = hours + ':' + minutes + ':59';
		// 			}
		//
		// 			if ( typeof value_from != 'undefined' || typeof value_to != 'undefined' ) {
		// 				request[ filter_name ] = [ value_from, value_to ];
		// 			}
		// 		} else if (  filter.find( 'select' ).length ) {
		// 			var filter_name = filter.find('select').attr('name');
		// 			var value = um_get_data_for_directory( directory, 'filter_' + filter_name );
		// 			if ( typeof value != 'undefined' ) {
		// 				value = UM.common.form.unsanitizeValue( value );
		// 				request[ filter_name ] = value.split( '||' );
		// 			}
		// 		} else if ( filter.hasClass( 'um-text-filter-type' ) && filter.find('input[type="text"]').length ) {
		// 			var filter_name = filter.find('input[type="text"]').attr('name');
		// 			var value = um_get_data_for_directory( directory, 'filter_' + filter_name );
		// 			if ( typeof value != 'undefined' ) {
		// 				value = UM.common.form.unsanitizeValue( value );
		// 				request[ filter_name ] = value;
		// 			}
		// 		} else {
		// 			request = wp.hooks.applyFilters( 'um_member_directory_custom_filter_handler', request, filter, directory );
		// 		}
		// 	});
		// }

		request = wp.hooks.applyFilters( 'um_member_directory_filter_request', request );

		let instance = this;

		wp.ajax.send( 'um_get_members', {
			data:  request,
			success: function( answer ) {
				// Set last data hard for using on layouts reloading.
				instance.setLastResponse( answer );
				instance.setTotalPages( answer.total_pages );

				um_build_template( instance.wrapper, answer );

				instance.wrapper.find('.um-members-pagination-box').html( answer.pagination );
				instance.wrapper.data( 'total_pages', answer.total_pages );

				if ( answer.total_pages ) {
					instance.wrapper.find( '.um-member-directory-sorting .um-dropdown-wrapper .um-button' ).prop( 'disabled', false );
					instance.wrapper.find( '.um-member-view-switcher' ).removeClass( 'um-disabled' );

					instance.wrapper.find( '.um-member-directory-header' ).removeClass( 'um-display-none' );
					instance.wrapper.find( '.um-members-wrapper' ).removeClass( 'um-display-none' );
					instance.wrapper.find( '.um-members-pagination-box' ).removeClass( 'um-display-none' );
					instance.wrapper.find('.um-member-directory-header-row-grid').removeClass( 'um-display-none' );
				} else {
					if ( instance.hasSearched() ) {
						instance.wrapper.find( '.um-member-directory-empty-search-result' ).removeClass( 'um-display-none' );
						instance.wrapper.find( '.um-member-directory-header' ).removeClass( 'um-display-none' );
						instance.wrapper.find( '.um-member-directory-header-row-grid' ).addClass( 'um-display-none' );
					} else {
						instance.wrapper.find( '.um-member-directory-empty-no-search-result' ).removeClass( 'um-display-none' );
					}
				}

				wp.hooks.doAction( 'um_member_directory_loaded', instance.wrapper, answer );

				um_init_new_dropdown();

				UM.frontend.slider.init();

				instance.preloaderHide();
			},
			error: function( data ) {
				console.log( data );

				if ( instance.hasSearched() ) {
					instance.wrapper.find( '.um-member-directory-empty-search-result' ).removeClass( 'um-display-none' );
				} else {
					instance.wrapper.find( '.um-member-directory-empty-no-search-result' ).removeClass( 'um-display-none' );
				}

				instance.preloaderHide();
			}
		});
	}
}

function um_time_convert( time, range ) {
	var hours = Math.floor( time / 60 );
	var minutes = time % 60;

	if ( minutes >= 60 ) {
		minutes = 0;
		hours = hours + 1;

		if ( hours >= 24 ) {
			hours = 0;
		}
	}

	if ( minutes < 10 ) {
		minutes = '0' + minutes;
	}

	return hours + ":" + minutes;
}

function um_build_template( directory, data ) {
	let layout = directory.data('view_type');
	let hash = UM.frontend.directories.getHash( directory );
	if ( jQuery('.um-' + hash ).length ) {
		directory.find('.um-members-wrapper').html('').prepend(data[ 'content_' + layout ]);

		if ( '' !== data.counter ) {
			directory.find('.um-members-counter').text( data.counter ).show();
		} else {
			directory.find('.um-members-counter').text( data.counter ).hide();
		}

		directory.addClass('um-loaded');

		jQuery(document).trigger('um_build_template', [directory, data]);
		jQuery(window).trigger('resize');

		wp.hooks.doAction( 'um_member_directory_build_template', directory );

		UM.common.tipsy.init();
	}
}

jQuery(document.body).ready( function() {

	/**
	 * Change View Type Handlers
	 */

	jQuery( document.body ).on( 'click', '.um-directory .um-member-view-switcher:not(.um-disabled) .um-button-in-group:not(.current)', function() {
		let $this = jQuery(this);
		let directory = $this.parents('.um-directory');
		let hash         = UM.frontend.directories.getHash( directory );
		let directoryObj = UM.frontend.directories.list[ hash ];

		if ( directoryObj.isBusy() ) {
			return;
		}

		let data = directoryObj.getLastResponse();
		if ( data !== null ) {

			let prevType = $this.parents('.um-member-view-switcher').find('.um-button-in-group.current').data('type');
			directory.find('.um-members-wrapper.um-members-' + prevType).removeClass('um-members-' + prevType);

			$this.parents('.um-member-view-switcher').find('.um-button-in-group').removeClass('current');
			$this.addClass('current');

			let layout = $this.data('type');
			let defaultView = $this.data('default');

			directory.find('.um-members-wrapper').addClass('um-members-' + layout);

			if ( defaultView ) {
				um_set_url_from_data( directory, 'view_type', '' );
			} else {
				um_set_url_from_data( directory, 'view_type', layout );
			}
			directory.data( 'view_type', layout );

			um_build_template( directory, data );

			um_init_new_dropdown();
		}
	});

	/**
	 * END: Change View Type Handlers
	 */


	/**
	 * General Search
	 */

	function um_run_search( directory ) {
		let hash = UM.frontend.directories.getHash( directory );
		let directoryObj = UM.frontend.directories.list[ hash ];
		if ( directoryObj.isBusy() ) {
			return;
		}

		let preSearch = directoryObj.getDataFromURL( 'search' );
		let search = UM.common.form.sanitizeValue( directory.find('.um-search-line').val() );
		if ( search === preSearch || ( search === '' && typeof preSearch == 'undefined' ) ) {
			return;
		}

		directoryObj.setSearch( search );
		directoryObj.setPage( 1 );

		let ignoreMustSearch = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', false, directory );
		if ( false === ignoreMustSearch ) {
			let mustSearch = parseInt( directory.data('must-search') );
			if ( mustSearch === 1 ) {
				if ( '' === directoryObj.getSearch() && 0 === Object.keys( directoryObj.getFilters() ).length ) {
					directory.find('.um-member-directory-empty-search-result' ).addClass( 'um-display-none' );
					directory.find('.um-member-directory-empty-no-search-result' ).addClass( 'um-display-none' );
					directory.find( '.um-member-directory-sorting .um-dropdown-wrapper .um-button' ).prop( 'disabled', true );
					directory.find( '.um-member-view-switcher' ).addClass( 'um-disabled' );

					directory.find( '.um-member-directory-header-row-grid' ).addClass( 'um-display-none' );
					directory.find( '.um-members-wrapper' ).addClass( 'um-display-none' );
					directory.find( '.um-members-pagination-box' ).addClass( 'um-display-none' );

					directory.find('.um-member-directory-must-search').removeClass( 'um-display-none' );
					return;
				}
			}
		}

		directoryObj.request();
	}

	// Searching
	jQuery( document.body ).on( 'click', '.um-directory .um-do-search', function() {
		let directory = jQuery(this).parents('.um-directory');
		um_run_search( directory );
	});


	// Make search on Enter click
	jQuery( document.body ).on( 'keypress', '.um-directory .um-search-line', function(e) {
		if ( e.which === 13 ) {
			let directory = jQuery(this).parents('.um-directory');
			um_run_search( directory );
		}
	});

	/**
	 * END: General Search
	 */

	/**
	 * Sorting
	 */
	jQuery( document.body ).on( 'click', '.um-members-sorting', function(e) {
		e.preventDefault();

		if ( jQuery( this ).data('selected') === 1 ) {
			return;
		}

		let hash = jQuery(this).data('directory-hash');
		let directoryObj = UM.frontend.directories.list[ hash ];
		if ( directoryObj.isBusy() ) {
			return;
		}

		let directory = directoryObj.getWrapper();
		let sortingLabel = jQuery( this ).text();
		let order = jQuery(this).data('value');

		directory.find('.um-dropdown[data-element=".um-members-sorting-toggle"]').find('a').data('selected', 0).prop('data-selected', 0).attr('data-selected', 0);
		directory.find('.um-dropdown[data-element=".um-members-sorting-toggle"] a[data-value="' + order + '"]').data('selected', 1).prop('data-selected', 1).attr('data-selected', 1);
		directory.find('.um-members-sorting-toggle .um-button-content').text( sortingLabel );

		directoryObj.setOrder( order );
		directoryObj.request();
	});

	/**
	 * END: Sorting
	 */

	/**
	 * Pagination
	 */

	function um_member_directory_handle_pagination_nav( directory, page, totalPages ) {
		let $prevButton = directory.find('.um-pagination-item[data-page="prev"]');
		let $nextButton = directory.find('.um-pagination-item[data-page="next"]');

		if ( 1 === page ) {
			$prevButton.addClass('disabled');
			$nextButton.removeClass('disabled');
		} else if ( page === totalPages ) {
			$nextButton.addClass('disabled');
			$prevButton.removeClass('disabled');
		} else {
			$prevButton.removeClass('disabled');
			$nextButton.removeClass('disabled');
		}
	}

	jQuery( document.body ).on( 'click', '.um-directory .um-pagination-item:not(.current)', function() {
		if ( jQuery(this).hasClass('disabled') ) {
			return;
		}

		let directory = jQuery(this).parents('.um-directory');
		let hash = UM.frontend.directories.getHash( directory );
		let directoryObj = UM.frontend.directories.list[ hash ];
		if ( directoryObj.isBusy() ) {
			return;
		}

		let totalPages = parseInt( directory.data( 'total_pages' ) );
		let currentPage = directoryObj.getPage();
		let dataPage = jQuery(this).data('page');

		let page;
		if ( 'prev' === dataPage ) {
			page = currentPage - 1;
		} else if ( 'next' === dataPage ) {
			page = currentPage + 1;
		} else {
			page = parseInt( dataPage );
		}

		um_member_directory_handle_pagination_nav( directory, page, totalPages );

		directory.find('.um-pagination-item').removeClass('current');
		directory.find('.um-pagination-item[data-page="' + page + '"]').addClass('current');

		directoryObj.setPage( page );
		directoryObj.request({pagination:true});
	});

	jQuery( document.body ).on( 'change', '.um-directory .um-pagination-current-page-input', function() {
		if ( jQuery(this).hasClass('disabled') ) {
			return;
		}

		let directory = jQuery(this).parents('.um-directory');
		let hash = UM.frontend.directories.getHash( directory );
		let directoryObj = UM.frontend.directories.list[ hash ];
		if ( directoryObj.isBusy() ) {
			return;
		}

		let currentPage = directoryObj.getPage();
		let page        = parseInt( jQuery(this).val() );
		let totalPages  = parseInt( directory.data( 'total_pages' ) );
		if ( currentPage === page ) {
			return;
		}

		if ( page < 1 ) {
			jQuery(this).val( 1 );
			page = 1;
		}

		if ( page > totalPages ) {
			jQuery(this).val( totalPages );
			page = totalPages;
		}

		um_member_directory_handle_pagination_nav( directory, page, totalPages );

		directoryObj.setPage( page );
		directoryObj.request({pagination:true});
	});

	jQuery( document.body ).on( 'keypress', '.um-directory .um-pagination-current-page-input', function(e) {
		if ( 13 === e.which ) {
			jQuery(this).trigger('change');
			return false;
		}
	});

	/**
	 * END: Pagination
	 */

	/**
	 * START: Filters
	 */

	jQuery( document.body ).on( 'change', '.um-search-filter-field', function() {
		let $filtersForm = jQuery(this).parents('.um-filters-form');
		let $applyFilters = $filtersForm.find('.um-apply-filters');

		UM.frontend.directories.filters.checkEmpty( $filtersForm );

		// Enable filters submission as soon as first filter is changed.
		$applyFilters.prop( 'disabled', false );
	});

	jQuery( document.body ).on( 'reset', '.um-directory .um-filters-form', function() {
		let directory = jQuery(this).parents('.um-directory');
		let hash = UM.frontend.directories.getHash( directory );
		let directoryObj = UM.frontend.directories.list[ hash ];
		if ( directoryObj.isBusy() ) {
			return;
		}

		let $filtersForm = jQuery(this);

		let $clearFilters = $filtersForm.find('.um-clear-filters-a');
		let $applyFilters = $filtersForm.find('.um-apply-filters');

		$clearFilters.addClass('um-hidden').prop('disabled', true);
		$applyFilters.prop('disabled', true);

		directoryObj.resetFilters();
		directoryObj.setPage(1);

		let ignoreMustSearch = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', false, directory );
		if ( false === ignoreMustSearch ) {
			let mustSearch = parseInt( directory.data('must-search') );
			if ( mustSearch === 1 ) {
				if ( '' === directoryObj.getSearch() && 0 === Object.keys( directoryObj.getFilters() ).length ) {
					directory.find('.um-member-directory-empty-search-result' ).addClass( 'um-display-none' );
					directory.find('.um-member-directory-empty-no-search-result' ).addClass( 'um-display-none' );
					directory.find( '.um-member-directory-sorting .um-dropdown-wrapper .um-button' ).prop( 'disabled', true );
					directory.find( '.um-member-view-switcher' ).addClass( 'um-disabled' );

					directory.find( '.um-member-directory-header-row-grid' ).addClass( 'um-display-none' );
					directory.find( '.um-members-wrapper' ).addClass( 'um-display-none' );
					directory.find( '.um-members-pagination-box' ).addClass( 'um-display-none' );

					directory.find('.um-member-directory-must-search').removeClass( 'um-display-none' );
					return;
				}
			}
		}

		directoryObj.request();
	});

	jQuery( document.body ).on( 'click', '.um-directory .um-apply-filters', function(e) {
		e.preventDefault();
		let directory = jQuery(this).parents('.um-directory');
		let hash = UM.frontend.directories.getHash( directory );
		let directoryObj = UM.frontend.directories.list[ hash ];
		if ( directoryObj.isBusy() ) {
			return;
		}

		directoryObj.setFilters();
		directoryObj.setPage(1);

		let ignoreMustSearch = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', false, directory );
		if ( false === ignoreMustSearch ) {
			let mustSearch = parseInt( directory.data('must-search') );
			if ( mustSearch === 1 ) {
				if ( '' === directoryObj.getSearch() && 0 === Object.keys( directoryObj.getFilters() ).length ) {
					directory.find('.um-member-directory-empty-search-result' ).addClass( 'um-display-none' );
					directory.find('.um-member-directory-empty-no-search-result' ).addClass( 'um-display-none' );
					directory.find( '.um-member-directory-sorting .um-dropdown-wrapper .um-button' ).prop( 'disabled', true );
					directory.find( '.um-member-view-switcher' ).addClass( 'um-disabled' );

					directory.find( '.um-member-directory-header-row-grid' ).addClass( 'um-display-none' );
					directory.find( '.um-members-wrapper' ).addClass( 'um-display-none' );
					directory.find( '.um-members-pagination-box' ).addClass( 'um-display-none' );

					directory.find('.um-member-directory-must-search').removeClass( 'um-display-none' );
					return;
				}
			}
		}

		directoryObj.request();
	});

	/**
	 * First Page Loading
	 */

	wp.hooks.doAction( 'um_member_directory_on_first_pages_loading' );

	// Init Directories
	jQuery( '.um-directory' ).each( function() {
		let directory = jQuery(this);
		let hash      = UM.frontend.directories.getHash( directory );
		let directoryObj = new UM.frontend.directory( hash );
		UM.frontend.directories.list[ hash ] = directoryObj;

		wp.hooks.doAction( 'um_member_directory_on_init', directory, hash );

		let page = directoryObj.getDataFromURL( 'page' );
		if ( 'undefined' !== typeof page ) {
			directoryObj.setPage(page, {ignoreURL:true});
		}

		let search = directoryObj.getDataFromURL( 'search' );
		if ( 'undefined' !== typeof search ) {
			directoryObj.setSearch(search, {ignoreURL:true});
		}

		let order = directoryObj.getDataFromURL( 'sort' );
		if ( 'undefined' !== typeof order ) {
			directoryObj.setOrder(order, true);
		}

		directoryObj.setFilters( true );

		let ignoreMustSearch = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', false, directory );
		if ( false === ignoreMustSearch ) {
			let mustSearch = parseInt( directory.data('must-search') );
			if ( mustSearch === 1 ) {
				if ( '' === directoryObj.getSearch() && 0 === Object.keys( directoryObj.getFilters() ).length ) {
					return;
				}
			}
		}

		let preventDefaultRequest = false;
		preventDefaultRequest = wp.hooks.applyFilters( 'um_member_directory_prevent_default_request', preventDefaultRequest, directoryObj );

		if ( ! preventDefaultRequest ) {
			directoryObj.request({defaultRequest:true});
		}
	});

	// History events when back/forward and change window.location.hash
	window.addEventListener( 'popstate', function(e) {
		jQuery( '.um-directory' ).each( function() {
			let directory = jQuery(this);
			let hash      = UM.frontend.directories.getHash( directory );
			let directoryObj = new UM.frontend.directory( hash );
			UM.frontend.directories.list[ hash ] = directoryObj;

			let page = directoryObj.getDataFromURL( 'page' );
			if ( 'undefined' !== typeof page ) {
				directoryObj.setPage(page, {ignoreURL:true,updateUI:true});
			} else {
				directoryObj.setPage('', {ignoreURL:true,updateUI:true});
			}

			let search = directoryObj.getDataFromURL( 'search' );
			if ( 'undefined' !== typeof search ) {
				directoryObj.setSearch(search, {ignoreURL:true,updateUI:true});
			} else {
				directoryObj.setSearch('', {ignoreURL:true,updateUI:true});
			}

			let order = directoryObj.getDataFromURL( 'sort' );
			if ( 'undefined' !== typeof order ) {
				directoryObj.setOrder(order, {ignoreURL:true,updateUI:true});
			} else {
				directoryObj.setOrder('', {ignoreURL:true,updateUI:true});
			}

			directoryObj.setFilters( true );

			// View type from history
			if ( directory.find( '.um-member-directory-view-type' ).length ) {
				var layout = um_get_data_for_directory( directory, 'view_type' );
				if ( typeof layout == 'undefined' ) {
					layout = directory.find( '.um-member-directory-view-type-a[data-default="1"]' ).data('type');
				}
				directory.data( 'view_type', layout );

				directory.find('.um-member-directory-view-type .um-member-directory-view-type-a').hide();
				directory.find('.um-member-directory-view-type .um-member-directory-view-type-a[data-type="' + layout + '"]').show();
			}

			let ignoreMustSearch = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', false, directory );
			if ( false === ignoreMustSearch ) {
				let mustSearch = parseInt( directory.data('must-search') );
				if ( mustSearch === 1 ) {
					if ( '' === directoryObj.getSearch() && 0 === Object.keys( directoryObj.getFilters() ).length ) {
						return;
					}
				}
			}

			let prevent_default = wp.hooks.applyFilters( 'um_member_directory_prevent_default_first_loading', false, directory, hash );
			if ( ! prevent_default ) {
				directoryObj.request();
			}
		});
	});
});

// Set toggle block based on parent.
wp.hooks.addFilter( 'um_toggle_block', 'um_member_directory', function( $toggleBlock, $toggleButton ) {
	if ( $toggleButton.hasClass('um-meta-toggle') ) {
		$toggleBlock = $toggleButton.parents('.um-member').find( $toggleButton.data('um-toggle') );
		let textAfter  = $toggleButton.data('toggle-text');
		let textBefore = $toggleButton.text();
		$toggleButton.data('toggle-text',textBefore).text(textAfter);
	} else if ( $toggleButton.hasClass('um-filters-toggle') ) {
		$toggleBlock = $toggleButton.parents('.um-directory').find( $toggleButton.data('um-toggle') );
	}

	return $toggleBlock;
});
