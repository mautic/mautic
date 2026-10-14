import { animate, hover, press, visualElementStore } from 'motion';

export { animate, hover, press, visualElementStore };

type Disposable = { destroy(): void };
type Registry = { get(element: HTMLElement): unknown; mount(root?: ParentNode): void };

declare global {
  interface Window {
    MauticUI?: Record<string, Registry>;
  }
}

export function emit(element: HTMLElement, name: string, detail: unknown): void {
  element.dispatchEvent(new CustomEvent(name, { bubbles: true, detail }));
}

export function mountComponent<Controller extends Disposable>(name: string, selector: string, create: (element: HTMLElement) => Controller): void {
  const controllers = new Map<HTMLElement, Controller>();
  const mount = (root: ParentNode = document) => {
    const candidates = Array.from(root.querySelectorAll<HTMLElement>(selector));
    if (root instanceof HTMLElement && root.matches(selector)) candidates.unshift(root);
    for (const element of candidates) {
      if (!controllers.has(element)) controllers.set(element, create(element));
    }
  };
  const start = () => {
    mount();
    new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
          if (node instanceof HTMLElement) mount(node);
        }
      }
      for (const [element, controller] of controllers) {
        if (element.isConnected) continue;
        controller.destroy();
        controllers.delete(element);
      }
    }).observe(document.documentElement, { childList: true, subtree: true });
  };
  window.MauticUI ??= {};
  window.MauticUI[name] = { get: (element) => controllers.get(element), mount };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
  else start();
}

const SPRING = { type: 'spring' as const, stiffness: 550, damping: 30, restSpeed: 10, reduceMotion: false };

export function springScale(element: HTMLElement, scale: number) {
  return animate(element, { scale }, SPRING);
}

export function drawStroke(shape: SVGElement, on: boolean, fresh: boolean, delay = 0) {
  const target = on ? 1 : 0;
  const animation = animate(shape, {
    pathLength: fresh ? [0, target] : target,
    opacity: fresh ? [0, target] : target
  }, { duration: 0.2, delay, ease: 'easeOut' });
  if (fresh) {
    const visual = visualElementStore.get(shape);
    visual?.getValue('opacity')?.set(0);
    visual?.getValue('pathLength')?.set(0);
  }
  return animation;
}
