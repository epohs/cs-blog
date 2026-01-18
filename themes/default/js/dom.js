/**
 * Minimal dependency-free DOM helpers.
 *
 * Design goals:
 * - Thin wrappers over native APIs
 * - Scoped selectors by default
 * - No globals
 * - Opt-in convenience, not abstraction
 *
 * This module intentionally avoids chaining, mutation magic,
 * and state management.
 *
 * If you find your theme outgrowing this file, rather than adding
 * to it, consider https://umbrellajs.com
 */



/**
 * Select the first matching element.
 *
 * @param {string} s - CSS selector
 * @param {ParentNode} el - Root element (defaults to document)
 * @returns {Element|null}
 */
export const $ = (s, el = document) => el.querySelector(s);








/**
 * Select all matching elements.
 *
 * @param {string} s - CSS selector
 * @param {ParentNode} el - Root element (defaults to document)
 * @returns {NodeList}
 */
export const $$ = (s, el = document) => el.querySelectorAll(s);








/**
 * Add an event listener.
 *
 * Supports:
 * - Direct binding
 * - Event delegation via selector
 *
 * @param {Element|Document|Window} el
 * @param {string} evt
 * @param {string|Function} selector
 * @param {Function} [fn]
 * @param {Object} [options]
 */
export const on = (el, evt, selector, fn, options) => {

  if (!el) return;


  if (typeof selector === 'function') {

    fn = selector;

    el.addEventListener(evt, fn, options);

    return;

  }


  el.addEventListener(evt, e => {

    const target = e.target.closest(selector);


    if (target && el.contains(target)) {

      fn.call(target, e);

    }

  }, options);

};








/**
 * Remove an event listener.
 *
 * @param {Element|Document|Window} el
 * @param {string} evt
 * @param {Function} fn
 */
export const off = (el, evt, fn) => el.removeEventListener(evt, fn);








/**
 * Add an event listener that fires once.
 *
 * @param {Element|Document|Window} el
 * @param {string} evt
 * @param {Function} fn
 */
export const once = (el, evt, fn) => el.addEventListener(evt, fn, { once: true });








/**
 * Get or set attributes.
 *
 * - attr(el, name) → get
 * - attr(el, name, value) → set
 * - attr(el, object) → set multiple
 *
 * @param {Element} el
 * @param {string|Object} name
 * @param {string} [value]
 * @returns {string|undefined}
 */
export const attr = (el, name, value) => {

  if (!el) return;


  if (typeof name === 'string' && value === undefined) {

    return el.getAttribute(name);

  }


  if (typeof name === 'object') {

    for (const key in name) {

      el.setAttribute(key, name[key]);

    }

    return;

  }


  el.setAttribute(name, value);

};








/**
 * Get or set data-* attributes.
 *
 * - data(el, key) → get
 * - data(el, key, value) → set
 * - data(el, object) → set multiple
 *
 * Values are treated as strings and are not coerced.
 *
 * @param {Element} el
 * @param {string|Object} key
 * @param {string} [value]
 * @returns {string|undefined}
 */
export const data = (el, key, value) => {

  if (!el) return;


  if (typeof key === 'string' && value === undefined) {

    return el.dataset[key];

  }


  if (typeof key === 'object') {

    for (const k in key) {

      el.dataset[k] = key[k];

    }

    return;

  }

  
  el.dataset[key] = value;

};








/**
 * Get or set inline styles.
 *
 * - css(el, prop) → get computed style
 * - css(el, prop, value) → set
 * - css(el, object) → set multiple
 *
 * @param {Element} el
 * @param {string|Object} prop
 * @param {string} [value]
 * @returns {string|undefined}
 */
export const css = (el, prop, value) => {

  if (!el) return;


  if (typeof prop === 'string' && value === undefined) {

    return getComputedStyle(el).getPropertyValue(prop);

  }


  if (typeof prop === 'object') {

    for (const key in prop) {

      el.style[key] = prop[key];

    }

    return;

  }


  el.style[prop] = value;

};








/**
 * Toggle a class name.
 *
 * @param {Element} el
 * @param {string} className
 * @param {boolean} [force]
 * @returns {boolean}
 */
export const toggle = (el, className, force) => {

  if (!el) return false;

  return el.classList.toggle(className, force);

};
