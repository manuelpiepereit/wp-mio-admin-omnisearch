import Vue from "vue";
import App from "./components/App.vue";

Vue.directive("click-outside", {
	bind: function (el, binding, vnode) {
		el.clickOutsideEvent = function (event) {
			let exludeItem = event.target.closest(".mio-omnisearch") === null;

			// here I check that click was outside the el and his childrens
			if (!(el == event.target || el.contains(event.target)) && exludeItem) {
				// and if it did, call method provided in attribute value
				vnode.context[binding.expression](event);
			}
		};
		document.body.addEventListener("click", el.clickOutsideEvent);
	},
	unbind: function (el) {
		document.body.removeEventListener("click", el.clickOutsideEvent);
	}
});


window.addEventListener('load', function () {
	new Vue({
		el: "#wp-admin-bar-mio-omnisearch-app",
		render: h => h(App)
	});
});
