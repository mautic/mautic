import { animate } from 'motion';
import { emit, reducedMotion, register } from './lifecycle';
import { beamCSS, beamThemes } from './vendor/beam.mjs';

type Source = 'pointer' | 'keyboard';
type Animation = { stop(): void };
type Options = {
  size?: 'sm' | 'md' | 'lg';
  className?: string;
  beamClassName?: string;
  beamSize?: 'md';
  icon?: Node | null;
  duration?: number;
  resetDelay?: number;
  disabled?: boolean;
  label?: string;
  confirmedLabel?: string;
  onConfirm?: () => void;
  variant?: 'destructive' | 'neutral' | 'beam';
  theme?: 'auto' | 'light' | 'dark';
  colorVariant?: 'colorful' | 'mono' | 'ocean' | 'sunset';
  strength?: number;
  active?: boolean;
};

let beamCounter = 0;
const snappy = { type: 'spring', stiffness: 500, damping: 30 } as const;
const easeOut = [.22, 1, .36, 1] as [number, number, number, number];

class HoldToConfirmButton {
  private readonly button: HTMLButtonElement;
  private readonly ring: SVGElement;
  private readonly progressCircle: SVGElement;
  private readonly icon: HTMLElement;
  private readonly check: HTMLElement;
  private readonly idleLabel: HTMLElement;
  private readonly confirmedLabel: HTMLElement;
  private readonly status: HTMLElement;
  private readonly events = new AbortController();
  private readonly sources = new Set<Source>();
  private readonly animations = new Map<Element, Animation>();
  private readonly defaultIcon: Node;
  private readonly colorScheme = window.matchMedia('(prefers-color-scheme: dark)');
  private readonly beamId = `mautic-hold-${++beamCounter}`;
  private beamStyle?: HTMLStyleElement;
  private intersection?: IntersectionObserver;
  private progress = 0;
  private fill?: Animation;
  private resetTimer?: ReturnType<typeof setTimeout>;
  private confirmed = false;
  private duration: number;
  private resetDelay: number;
  private onConfirm?: () => void;

  constructor(private readonly element: HTMLElement) {
    this.button = element.querySelector('button')!;
    this.ring = element.querySelector('.acervo-hold__ring')!;
    this.progressCircle = element.querySelector('.acervo-hold__progress')!;
    this.icon = element.querySelector('.acervo-hold__icon')!;
    this.defaultIcon = this.icon.cloneNode(true);
    this.check = element.querySelector('.acervo-hold__check')!;
    this.idleLabel = element.querySelector('[data-idle-label]')!;
    this.confirmedLabel = element.querySelector('[data-confirmed-label]')!;
    this.status = element.querySelector('[role="status"]')!;
    this.duration = Number(element.dataset.duration);
    this.resetDelay = Number(element.dataset.resetDelay);
    const options = { signal: this.events.signal };
    this.button.addEventListener('pointerdown', (event) => {
      try {
        this.button.setPointerCapture(event.pointerId);
      } catch (error) {
        if (!(error instanceof DOMException)) throw error;
      }
      this.start('pointer');
    }, options);
    for (const name of ['pointerup', 'pointerleave', 'pointercancel']) {
      this.button.addEventListener(name, () => this.cancel('pointer'), options);
    }
    this.button.addEventListener('keydown', (event) => {
      if (event.key !== ' ' && event.key !== 'Enter') return;
      event.preventDefault();
      if (!event.repeat) this.start('keyboard');
    }, options);
    this.button.addEventListener('keyup', (event) => {
      if (event.key !== ' ' && event.key !== 'Enter') return;
      event.preventDefault();
      this.cancel('keyboard');
    }, options);
    this.button.addEventListener('blur', () => this.cancel(), options);
    this.button.addEventListener('contextmenu', (event) => event.preventDefault(), options);
    this.colorScheme.addEventListener('change', () => this.renderBeam(), options);
    element.addEventListener('animationend', (event) => {
      if (event.target !== element || !event.animationName.includes('fade-out')) return;
      element.removeAttribute('data-fading');
      element.removeAttribute('data-active');
    }, options);
    this.renderBeam();
  }

  update(options: Options): void {
    if (options.className !== undefined) this.button.className = `acervo-hold ${options.className}`;
    if (options.beamClassName !== undefined) this.element.className = `acervo-hold-wrapper ${options.beamClassName}`;
    if ('icon' in options) {
      this.icon.replaceChildren(...(options.icon ? [options.icon] : Array.from(this.defaultIcon.childNodes).map((node) => node.cloneNode(true))));
    }
    if (options.size !== undefined) {
      this.element.dataset.size = options.size;
      const dimensions = { sm: { icon: 12, ring: 20, stroke: 2 }, md: { icon: 14, ring: 24, stroke: 2 }, lg: { icon: 17, ring: 30, stroke: 2.5 } }[options.size];
      const symbol = this.element.querySelector<HTMLElement>('.acervo-hold__symbol')!;
      symbol.style.width = `${dimensions.ring}px`;
      symbol.style.height = `${dimensions.ring}px`;
      this.ring.setAttribute('viewBox', `0 0 ${dimensions.ring} ${dimensions.ring}`);
      this.ring.setAttribute('width', String(dimensions.ring));
      this.ring.setAttribute('height', String(dimensions.ring));
      for (const circle of this.ring.querySelectorAll('circle')) {
        circle.setAttribute('cx', String(dimensions.ring / 2));
        circle.setAttribute('cy', String(dimensions.ring / 2));
        circle.setAttribute('r', String((dimensions.ring - dimensions.stroke) / 2));
        circle.setAttribute('stroke-width', String(dimensions.stroke));
      }
      for (const svg of this.element.querySelectorAll('[data-default-icon], .acervo-hold__check svg')) {
        svg.setAttribute('width', String(dimensions.icon));
        svg.setAttribute('height', String(dimensions.icon));
      }
    }
    if (options.duration !== undefined) this.duration = options.duration;
    if (options.resetDelay !== undefined) this.resetDelay = options.resetDelay;
    if ('onConfirm' in options) this.onConfirm = options.onConfirm;
    if (options.disabled !== undefined) {
      this.button.disabled = options.disabled;
      if (options.disabled) this.cancel();
    }
    if (options.label !== undefined) this.idleLabel.textContent = options.label;
    if (options.confirmedLabel !== undefined) this.confirmedLabel.textContent = options.confirmedLabel;
    for (const key of ['variant', 'theme', 'colorVariant', 'strength', 'active'] as const) {
      if (options[key] !== undefined) this.element.dataset[key === 'active' ? 'beamEnabled' : key] = String(options[key]);
    }
    this.button.setAttribute('aria-label', this.element.dataset.instructions!.replace('%label%', this.idleLabel.textContent ?? '').replace('%seconds%', (this.duration / 1000).toFixed(1).replace(/\.0$/, '')));
    this.renderBeam();
  }

  private motion(element: HTMLElement, target: Record<string, number | number[]>, options: Parameters<typeof animate>[2] = snappy): void {
    this.animations.get(element)?.stop();
    this.animations.set(element, animate(element, target, reducedMotion() ? { duration: 0 } : options));
  }

  private setProgress(value: number): void {
    this.progress = value;
    this.progressCircle.style.strokeDashoffset = String(1 - value);
    this.ring.style.opacity = String(Math.max(0, Math.min(1, value / .04)));
  }

  private start(source: Source): void {
    if (this.button.disabled || this.confirmed) return;
    const alreadyHolding = this.sources.size > 0;
    this.sources.add(source);
    if (alreadyHolding) return;
    this.element.dataset.state = 'holding';
    this.motion(this.button, { scale: reducedMotion() ? 1 : .97 }, { duration: .2, ease: easeOut });
    this.fill?.stop();
    this.fill = animate(this.progress, 1, {
      duration: Math.max(0, this.duration * (1 - this.progress) / 1000),
      ease: 'linear',
      onUpdate: (value) => this.setProgress(value),
      onComplete: () => this.complete()
    });
  }

  private cancel(source?: Source): void {
    if (source) this.sources.delete(source);
    else this.sources.clear();
    if (this.sources.size > 0) return;
    this.motion(this.button, { scale: 1 });
    if (this.confirmed) return;
    this.element.dataset.state = 'idle';
    this.fill?.stop();
    this.fill = animate(this.progress, 0, {
      ...(reducedMotion() ? { duration: .15, ease: 'linear' as const } : snappy),
      onUpdate: (value) => this.setProgress(value)
    });
  }

  private swapLabel(confirmed: boolean): void {
    const outgoing = confirmed ? this.idleLabel : this.confirmedLabel;
    const incoming = confirmed ? this.confirmedLabel : this.idleLabel;
    const ghost = outgoing.cloneNode(true) as HTMLElement;
    ghost.style.position = 'absolute';
    ghost.setAttribute('aria-hidden', 'true');
    outgoing.parentElement!.append(ghost);
    outgoing.hidden = true;
    incoming.hidden = false;
    const transition = { type: 'spring', stiffness: 400, damping: 30 } as const;
    animate(ghost, { y: reducedMotion() ? 0 : -6, opacity: 0 }, {
      ...(reducedMotion() ? { duration: 0 } : transition),
      onComplete: () => ghost.remove()
    });
    this.motion(incoming, { y: reducedMotion() ? 0 : [6, 0], opacity: [0, 1] }, { ...transition, delay: confirmed ? .17 : 0 });
  }

  private complete(): void {
    if (this.confirmed || this.button.disabled || this.sources.size === 0) return;
    this.confirmed = true;
    this.sources.clear();
    this.setProgress(1);
    this.element.dataset.state = 'confirmed';
    this.motion(this.button, { scale: 1 });
    this.motion(this.icon, { scale: 0, opacity: 0 });
    this.motion(this.check, { scale: 1, opacity: 1 }, { type: 'spring', stiffness: 500, damping: 22, delay: .05 });
    this.swapLabel(true);
    this.status.textContent = this.confirmedLabel.textContent;
    this.onConfirm?.();
    emit(this.element, 'mautic:hold-to-confirm:confirm', {});
    if (this.resetDelay > 0) this.resetTimer = setTimeout(() => this.reset(), this.resetDelay);
  }

  reset(): void {
    const wasConfirmed = this.confirmed;
    this.confirmed = false;
    this.sources.clear();
    clearTimeout(this.resetTimer);
    this.element.dataset.state = 'idle';
    this.motion(this.button, { scale: 1 });
    this.motion(this.icon, { scale: 1, opacity: 1 });
    this.motion(this.check, { scale: 0, opacity: 0 });
    if (wasConfirmed) this.swapLabel(false);
    this.status.textContent = '';
    this.fill?.stop();
    this.fill = animate(this.progress, 0, { duration: reducedMotion() ? .1 : .3, ease: 'easeOut', onUpdate: (value) => this.setProgress(value) });
  }

  private renderBeam(): void {
    if (this.element.dataset.variant !== 'beam') {
      this.beamStyle?.remove();
      this.beamStyle = undefined;
      this.element.removeAttribute('data-beam');
      this.element.querySelector('[data-beam-bloom]')?.remove();
      this.intersection?.disconnect();
      this.intersection = undefined;
      return;
    }
    const colorVariant = this.element.dataset.colorVariant as NonNullable<Options['colorVariant']>;
    const theme = this.element.dataset.theme === 'auto' ? (this.colorScheme.matches ? 'dark' : 'light') : this.element.dataset.theme as 'dark' | 'light';
    this.beamStyle ??= document.createElement('style');
    this.beamStyle.dataset.beamStyle = this.beamId;
    this.beamStyle.textContent = beamCSS({
      ...beamThemes.md[theme], id: this.beamId, borderRadius: parseFloat(getComputedStyle(this.button).borderTopLeftRadius),
      borderWidth: 1, duration: 1.96, colorVariant, staticColors: colorVariant === 'mono', brightness: 1.3, hueRange: 30, theme
    });
    if (!this.beamStyle.isConnected) document.head.append(this.beamStyle);
    this.element.dataset.beam = this.beamId;
    this.element.style.setProperty('--beam-strength', String(Math.max(0, Math.min(1, Number(this.element.dataset.strength)))));
    if (!this.element.querySelector('[data-beam-bloom]')) {
      const bloom = document.createElement('div');
      bloom.setAttribute('data-beam-bloom', '');
      this.element.append(bloom);
    }
    const active = this.element.dataset.beamEnabled !== 'false' && !this.button.disabled;
    if (active) {
      this.element.setAttribute('data-active', '');
      this.element.removeAttribute('data-fading');
    } else if (this.element.hasAttribute('data-active')) {
      this.element.setAttribute('data-fading', '');
    } else this.element.removeAttribute('data-active');
    if (!this.intersection) {
      this.intersection = new IntersectionObserver((entries) => {
        for (const entry of entries) this.element.toggleAttribute('data-paused', !entry.isIntersecting);
      }, { rootMargin: '256px' });
      this.intersection.observe(this.element);
    }
  }

  destroy(): void {
    this.events.abort();
    this.fill?.stop();
    clearTimeout(this.resetTimer);
    for (const animation of this.animations.values()) animation.stop();
    this.beamStyle?.remove();
    this.intersection?.disconnect();
  }
}

register('HoldToConfirmButton', '[data-component="hold-to-confirm"]', (element) => new HoldToConfirmButton(element));
