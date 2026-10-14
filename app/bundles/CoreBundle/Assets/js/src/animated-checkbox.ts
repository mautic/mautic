import { animate, hover, press, visualElementStore } from 'motion';

type Checked = boolean | 'indeterminate';
type Options = { checked?: Checked; disabled?: boolean; onCheckedChange?: (checked: Checked) => void };
type JQueryLike = {
  find(selector: string): JQueryLike;
  addBack(selector: string): JQueryLike;
  each(callback: (index: number, element: HTMLElement) => void): JQueryLike;
  on(event: string, handler: (event: unknown, container: unknown) => void): JQueryLike;
};

declare const mQuery: (target: unknown) => JQueryLike;
declare const Mautic: { animatedCheckbox?: unknown };

const SELECTOR = '[data-component="animated-checkbox"]';
const SPRING = { type: 'spring' as const, stiffness: 550, damping: 30, restSpeed: 10, reduceMotion: false };
const controllers = new WeakMap<HTMLElement, AnimatedCheckbox>();

function springScale(element: HTMLElement, scale: number) {
  return animate(element, { scale }, SPRING);
}

function drawStroke(shape: SVGElement, on: boolean, fresh: boolean, delay = 0) {
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

class AnimatedCheckbox {
  private readonly button: HTMLButtonElement;
  private readonly input: HTMLInputElement;
  private readonly indicator: SVGElement;
  private readonly gestures: Array<() => void> = [];
  private readonly events = new AbortController();
  private checked: Checked;
  private readonly defaultChecked: Checked;
  private controlled: boolean;
  private onCheckedChange?: Options['onCheckedChange'];
  private animation?: ReturnType<typeof springScale>;
  private stroke?: ReturnType<typeof drawStroke>;
  private lastDrawn?: Checked;
  private hovered = false;
  private pressing = false;

  constructor(private readonly element: HTMLElement) {
    this.button = element.querySelector('button')!;
    this.input = element.querySelector('input')!;
    this.indicator = element.querySelector('[data-slot="checkbox-indicator"]')!;
    this.checked = element.dataset.state === 'indeterminate' ? 'indeterminate' : element.dataset.state === 'checked';
    this.defaultChecked = this.checked;
    this.controlled = element.dataset.controlled === 'true';
    const options = { signal: this.events.signal };
    this.button.addEventListener('click', () => {
      if (this.button.disabled) return;
      const next = this.checked === 'indeterminate' ? true : !this.checked;
      if (!this.controlled) {
        this.checked = next;
        this.render(true);
      }
      this.onCheckedChange?.(next);
      element.dispatchEvent(new CustomEvent('mautic:animated-checkbox:change', { bubbles: true, detail: { checked: next } }));
    }, options);
    this.button.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') event.preventDefault();
    }, options);
    this.gestures.push(hover(this.button, () => {
      this.hovered = true;
      this.scale();
      return () => {
        this.hovered = false;
        this.scale();
      };
    }));
    this.gestures.push(press(this.button, () => {
      if (this.button.disabled) return;
      this.pressing = true;
      this.scale();
      return () => {
        if (this.button.disabled) return;
        this.pressing = false;
        this.scale();
      };
    }));
    this.input.addEventListener('invalid', () => this.button.focus(), options);
    this.input.form?.addEventListener('reset', () => {
      this.checked = this.defaultChecked;
      this.render();
    }, options);
    this.render();
  }

  update(options: Options): void {
    if ('checked' in options) {
      this.controlled = options.checked !== undefined;
      if (options.checked !== undefined) this.checked = options.checked;
    }
    if (options.disabled !== undefined) {
      this.button.disabled = options.disabled;
      this.input.disabled = options.disabled;
    }
    if ('onCheckedChange' in options) this.onCheckedChange = options.onCheckedChange;
    this.render();
  }

  private render(notify = false): void {
    this.element.dataset.state = this.checked === 'indeterminate' ? 'indeterminate' : this.checked ? 'checked' : 'unchecked';
    this.button.setAttribute('aria-checked', this.checked === 'indeterminate' ? 'mixed' : String(this.checked));
    this.input.checked = this.checked === true;
    this.input.indeterminate = this.checked === 'indeterminate';
    this.draw();
    if (notify) {
      this.input.dispatchEvent(new Event('input', { bubbles: true }));
      this.input.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  private draw(): void {
    if (this.lastDrawn === this.checked) return;
    const mixed = this.checked === 'indeterminate';
    const fresh = this.lastDrawn === undefined || mixed !== (this.lastDrawn === 'indeterminate');
    let shape = this.indicator.querySelector<SVGElement>('path, line')!;
    if (mixed !== (shape.tagName.toLowerCase() === 'line')) {
      this.stroke?.stop();
      shape = document.createElementNS('http://www.w3.org/2000/svg', mixed ? 'line' : 'path');
      const attributes: Record<string, string> = mixed ? { x1: '5', y1: '12', x2: '19', y2: '12' } : { d: 'M4.5 12.75l6 6 9-13.5', 'stroke-linejoin': 'round' };
      for (const [name, value] of Object.entries(attributes)) shape.setAttribute(name, value);
      shape.setAttribute('stroke-linecap', 'round');
      shape.setAttribute('pathLength', '1');
      shape.setAttribute('stroke-dasharray', '0 1');
      shape.setAttribute('stroke-dashoffset', '0');
      shape.setAttribute('opacity', '0');
      this.indicator.replaceChildren(shape);
    }
    this.stroke = drawStroke(shape, Boolean(this.checked), fresh, this.checked === true ? 0.2 : 0);
    this.lastDrawn = this.checked;
  }

  private scale(): void {
    this.animation = springScale(this.button, this.pressing ? 0.95 : this.hovered ? 1.05 : 1);
  }

  destroy(): void {
    this.events.abort();
    for (const dispose of this.gestures) dispose();
    this.animation?.stop();
    this.stroke?.stop();
  }
}

function mount(container: unknown): void {
  mQuery(container).find(SELECTOR).addBack(SELECTOR).each((_, element) => {
    if (!controllers.has(element)) controllers.set(element, new AnimatedCheckbox(element));
  });
}

Mautic.animatedCheckbox = {
  get: (element: HTMLElement) => controllers.get(element)
};

mQuery(document).on('mautic:onPageLoad:before', (_event, container) => mount(container));
