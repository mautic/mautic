import * as runtime from './runtime';

window.MauticComponents = runtime;

declare global {
  interface Window {
    MauticComponents?: typeof runtime;
  }
}
