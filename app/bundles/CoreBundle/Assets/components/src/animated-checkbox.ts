import { animate } from 'motion';
import { emit, register } from './lifecycle';

type Checked = boolean | 'indeterminate';
type Options = { checked?: Checked; disabled?: boolean; onCheckedChange?: (checked: Checked) => void };

class AnimatedCheckbox {
  private readonly button: HTMLButtonElement;
  private readonly input: HTMLInputElement;
  private readonly events = new AbortController();
  private checked: Checked;
  private readonly defaultChecked: Checked;
  private controlled: boolean;
  private onCheckedChange?: Options['onCheckedChange'];
  private animation?: ReturnType<typeof animate>;

  constructor(private readonly element: HTMLElement) {
    this.button = element.querySelector('button')!;
    this.input = element.querySelector('input')!;
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
      emit(element, 'mautic:animated-checkbox:change', { checked: next });
    }, options);
    this.button.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') event.preventDefault();
    }, options);
    this.button.addEventListener('pointerenter', () => this.scale(1.05), options);
    this.button.addEventListener('pointerleave', () => this.scale(1), options);
    this.button.addEventListener('pointerdown', () => this.scale(.95), options);
    this.button.addEventListener('pointerup', () => this.scale(1.05), options);
    this.button.addEventListener('pointercancel', () => this.scale(1), options);
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
    if (notify) {
      this.input.dispatchEvent(new Event('input', { bubbles: true }));
      this.input.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  private scale(value: number): void {
    if (this.button.disabled) return;
    this.animation?.stop();
    this.animation = animate(this.button, { scale: value }, { type: 'spring', stiffness: 100, damping: 10 });
  }

  destroy(): void {
    this.events.abort();
    this.animation?.stop();
  }
}

register('AnimatedCheckbox', '[data-component="animated-checkbox"]', (element) => new AnimatedCheckbox(element));
