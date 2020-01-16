<template>
	<li id="wp-admin-bar-mio-omnisearch" :class="isActive ? 'mio-omnisearch hover' : 'mio-omnisearch'">
		<div class="mio-omnisearch__icon ab-item" @click="toggleActive">
			<span class="ab-icon"></span>
		</div>

		<div class="mio-omnisearch__sub" v-if="isActive" v-click-outside="closeSearch">
			<div class="mio-omnisearch__inputs">
				<form method="post">
					<div class="input-text-wrap">
						<input
							type="search"
							name="search_term"
							:placeholder="ui.search"
							v-model="searchTerm"
							@keyup.prevent="search"
							@keydown="onKeyDown"
							@keypress.enter.prevent="editSelectedResult"
							ref="search"
							autocomplete="off"
						/>
					</div>
					<div class="input-text-wrap">
						<select name="search_type" v-model="searchType" @change.prevent="search">
							<option v-for="type in postTypes" :key="type.name" :value="type.name">{{ type.label }}</option>
						</select>
					</div>
				</form>
			</div>

			<div class="mio-omnisearch__results" v-if="searchResults">
				<div class="mio-omnisearch__results__heading">
					{{ ui.searchResults }}
					<span v-if="searchResults.length">{{ ui.openInfo }}</span>
				</div>
				<ul class="mio-omnisearch__results__list" v-if="searchResults.length">
					<li
						v-for="(post, index) in searchResults"
						:key="post.id"
						:id="'post-' + post.id"
						:class="['post', resultSelected === index ? '-is-selected' : '']"
						@click="editResult(post.link_edit, $event)"
						@mouseenter="selectResult(index)"
						@mouseleave="selectResult(null)"
					>
						<div class="post__image">
							<img class="post__image__img" :src="post.image" alt v-if="post.image" />
							<div class="post__image__img" v-else></div>
						</div>
						<div class="post__content">
							<a :href="post.link_edit" class="post__title">
								{{ post.title }}
								{{ post.status !== "" ? " &mdash; " + post.status : "" }}
							</a>
							<br />
							<a :href="post.link_edit">{{ ui.edit }}</a> |
							<a
								:href="post.link_view"
								target="_blank"
								@click.stop="viewResult(post.link_view, $event)"
							>{{ ui.view }}</a>
						</div>
						<div class="post__type">{{ post.type }}</div>
					</li>
				</ul>
				<ul class="mio-omnisearch__results__list" v-else>
					<li class="post">{{ ui.nothingFound }}</li>
				</ul>
			</div>
		</div>
	</li>
</template>

<script>
import _debounce from 'lodash.debounce';

export default {
	name: 'mioAdminOmniSearchApp',
	data() {
		return {
			isActive: false,
			searchTerm: '',
			searchType: 'any', // the filter
			searchResults: null,
			resultSelected: null, // post.id
			postTypes: mio_omnisearch.types,
			ui: mio_omnisearch.ui,
		};
	},
	watch: {
		searchResults: function(data) {
			this.searchResults = data;
			// select first result
			this.resultSelected = this.searchResults != null && this.searchResults.length !== 0 ? 0 : null;
		},
	},
	methods: {
		openSearch: function(e) {
			this.isActive = true;
			this.$nextTick(function() {
				this.$refs.search.focus();
			});
		},

		closeSearch: function(e) {
			this.isActive = false;
		},

		toggleActive: function(e) {
			if (!this.isActive) {
				this.openSearch();
			} else {
				this.closeSearch();
			}
		},

		onKeyDown: function(e) {
			if (e.keyCode === 27) {
				// esc
				if ('' === e.target.value) {
					this.closeSearch(e);
				}
				return;
			}
		},

		search: function(e) {
			if (e.keyCode === 13) {
				// enter
				return;
			} else if (e.keyCode === 38) {
				// up
				this.selectPrev(e);
				return;
			} else if (e.keyCode === 40) {
				// down
				this.selectNext(e);
				return;
			}

			this.searchDebounced(e, this);
		},

		searchDebounced: _debounce((e, vm) => {
			if ('' === vm.searchTerm) {
				vm.searchResults = null;
				return;
			}
			var url = '/wp-json/mio/omnisearch?search=' + vm.searchTerm + '&type=' + vm.searchType;
			fetch(url)
				.then(response => response.json())
				.then(data => {
					if (data.code) {
						vm.searchResults = [];
					} else {
						vm.searchResults = data;
					}
				});
		}, 300),

		// opens page
		viewResult: function(link, e) {
			window.open(link, '_blank');
		},

		// opens edit page
		editResult: function(link, e) {
			window.open(link, '_self');
		},

		// opens edit page based on selection
		editSelectedResult: function(e) {
			if (this.searchResults == null || this.searchResults.length === 0) {
				return;
			}

			let link_edit = this.searchResults[this.resultSelected].link_edit;
			window.open(link_edit, '_self');
		},

		// selects a result
		selectResult: function(index) {
			this.resultSelected = index;
			return;
		},

		selectNext: function(e) {
			e.target.selectionStart = e.target.value.length;
			if (this.resultSelected < this.searchResults.length - 1) {
				this.resultSelected++;
			}
		},

		selectPrev: function(e) {
			e.target.selectionStart = e.target.value.length;
			if (this.resultSelected >= 1) {
				this.resultSelected--;
			}
		},
	},
};
</script>

<style lang="scss">
#mio-admin-search .inside {
	margin-bottom: 0 !important;
	padding-bottom: 0 !important;
}
</style>

<style lang="scss" scoped>
$color: #eee;
$color-hover: #00b9eb;

$color-light: #f1f1f1;
$bg-color: #32373c;
$bg-color-dark: #23282d;
$bg-color-light: #42484e;

#wp-admin-bar-mio-omnisearch .mio-omnisearch__sub,
#wp-admin-bar-mio-omnisearch .mio-omnisearch__sub * {
	line-height: 1.5;
	box-sizing: border-box;
}

#wp-admin-bar-mio-omnisearch {
	.ab-icon:before {
		content: '\f179';
		top: 2px;
	}

	.ab-icon {
		margin-right: 0;
	}

	.mio-omnisearch {
		&__icon {
			margin: 0;
			padding: 0 1.5em;
			height: 32px;
			display: block;
			cursor: pointer;
		}

		&__search {
			margin: 0 0.5em;
		}

		&__sub {
			margin: 0;
			padding: 0;
			box-shadow: 0 3px 5px rgba(0, 0, 0, 0.2);
			background: #32373c;
			display: block;
			position: absolute;
			float: none;
			right: 0;
			left: auto;
			width: 400px;
		}

		&__inputs {
			padding: 12px;
		}

		&__results__heading {
			padding: 8px 12px;
			background: $bg-color-dark;
			display: flex;
			justify-content: space-between;
			text-transform: uppercase;
			font-size: 11px;
			span {
				font-size: inherit;
				text-transform: none;
			}
		}

		&__results__list {
			max-height: 400px;
			display: block;
			overflow: auto;
		}
	}
}

#wp-admin-bar-mio-omnisearch {
	.post + .post {
		border-top: 1px solid $bg-color-dark;
	}

	input,
	select {
		padding: 4px 8px;
		border-radius: 0;
		border: none;
		width: 100%;
		background: $bg-color-light;
		color: $color;
		max-width: 100%;
	}

	input {
		// caret-color: transparent;

		&:active,
		&:focus {
			outline: 1px solid $color-hover !important;
		}
	}

	select {
		margin-top: 8px;
	}

	.post {
		display: flex;
		width: 100%;
		padding: 12px;

		&__image {
			display: flex;
			flex: 0 0 auto;
			align-items: center;
		}

		&__image__img {
			width: 32px;
			height: 32px;
			display: block;
			// background: $bg-color-light;
			border: 1px solid $bg-color-dark;
			padding: 0;
			margin: 0 8px 0 0;
		}

		&__title {
			font-weight: 600;
		}

		&__content {
			flex: 1 1 auto;
			color: rgba($color, 0.4);

			a {
				display: inline;
				padding: 0;
				margin: 0;
				line-height: 1;
				color: $color;
				&:hover {
					color: $color-hover;
				}
			}
		}

		&__type {
			text-transform: uppercase;
			font-size: 0.8em;
			margin-left: 1em;
			text-align: right;
		}

		&.-is-selected {
			background: $bg-color-light;
			cursor: pointer;
		}

		&.-is-selected a.post__title {
			color: $color-hover;
		}
	}
}
</style>
