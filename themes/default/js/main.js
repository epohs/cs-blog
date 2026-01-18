/**
 * main.js
 *
 * Example usage of dom.js helpers.
 *
 * This file is intended as a starting point and reference
 * when adding JavaScript-driven behavior to an application.
 *
 * usage: <script type="module" src="./main.js"></script>
 */



import {
  $,
  $$,
  on,
  off,
  once,
  attr,
  data,
  css,
  toggle
} from './dom.js';








/**
 * Basic element selection.
 */

const app = $('#app');

const buttons = $$('.btn');








/**
 * Scoped selection.
 */

const card = $('.card');

const cardTitle = $('.title', card);








/**
 * Direct event binding.
 */

on($('#save'), 'click', e => {

  console.log('save clicked');

});








/**
 * Event delegation.
 */

on(document, 'click', '.btn', e => {

  console.log('button clicked:', this);

});








/**
 * One-time event handling.
 */

once($('#intro'), 'animationend', e => {

  console.log('intro animation finished');

});








/**
 * Removing an event listener.
 */

const onResize = e => {

  console.log('window resized');

};

on(window, 'resize', onResize);

off(window, 'resize', onResize);








/**
 * Attribute access.
 */

attr(app, 'role', 'application');

const role = attr(app, 'role');








/**
 * Batch attribute assignment.
 */

attr(app, {
  'aria-live': 'polite',
  'data-ready': 'true'
});








/**
 * data-* access.
 */

const userId = data(app, 'userId');

data(app, 'state', 'active');








/**
 * Batch data-* assignment.
 */

data(app, {
  view: 'dashboard',
  loaded: 'true'
});








/**
 * Inline style manipulation.
 */

css(app, 'display', 'block');

const display = css(app, 'display');








/**
 * Batch style assignment.
 */

css(app, {
  opacity: '1',
  pointerEvents: 'auto'
});








/**
 * Class toggling.
 */

toggle(app, 'is-active');

toggle(app, 'is-hidden', false);








/**
 * Combining helpers in a UI interaction.
 */

on($('.toggle-panel'), 'click', e => {

  toggle(app, 'is-open');

  data(app, 'open', String(
    data(app, 'open') !== 'true'
  ));

});
