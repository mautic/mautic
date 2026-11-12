(() => {
    if (window.MauticUI && window.MauticUI.HoldToConfirmButton) return;

    const spring = (time, stiffness, damping) => {
        const decay = damping / 2;
        const frequency = Math.sqrt(stiffness - decay * decay);
        return 1 - Math.exp(-decay * time) * (Math.cos(frequency * time) + decay / frequency * Math.sin(frequency * time));
    };

    const animate = (target, destination, options = {}) => {
        const duration = options.type === 'spring' ? 600 : (options.duration === undefined ? 0.3 : options.duration) * 1000;
        const delay = (options.delay || 0) * 1000;
        const easing = options.ease === 'linear' ? 'linear' : 'cubic-bezier(.22, 1, .36, 1)';
        if (typeof target === 'number') {
            let frame;
            const start = performance.now();
            const tick = (now) => {
                const elapsed = Math.max(0, now - start - delay);
                const fraction = duration ? Math.min(1, elapsed / duration) : 1;
                const amount = options.type === 'spring' ? spring(elapsed / 1000, options.stiffness, options.damping) : options.ease === 'linear' ? fraction : 1 - Math.pow(1 - fraction, 3);
                options.onUpdate(fraction === 1 ? destination : target + (destination - target) * amount);
                if (fraction < 1) frame = requestAnimationFrame(tick);
                else if (options.onComplete) options.onComplete();
            };
            frame = requestAnimationFrame(tick);
            return {stop: () => cancelAnimationFrame(frame)};
        }
        const computed = getComputedStyle(target);
        const matrix = new DOMMatrixReadOnly(computed.transform === 'none' ? undefined : computed.transform);
        const from = {};
        const to = {};
        Object.entries(destination).forEach(([key, value]) => {
            from[key] = Array.isArray(value) ? value[0] : key === 'opacity' ? Number(computed.opacity) : key === 'scale' ? matrix.a : matrix.f;
            to[key] = Array.isArray(value) ? value[value.length - 1] : value;
        });
        const styles = (fraction) => {
            const values = {};
            Object.keys(to).forEach((key) => values[key] = from[key] + (to[key] - from[key]) * fraction);
            const result = {};
            if ('opacity' in values) result.opacity = values.opacity;
            if ('scale' in values) result.transform = `scale(${values.scale})`;
            if ('y' in values) result.transform = `translateY(${values.y}px)`;
            return result;
        };
        const frames = options.type === 'spring' ? Array.from({length: 61}, (_, index) => styles(index === 60 ? 1 : spring(index / 100, options.stiffness, options.damping))) : [styles(0), styles(1)];
        const animation = target.animate(frames, {duration, delay, fill: 'both', easing: options.type === 'spring' ? 'linear' : easing});
        animation.onfinish = () => {
            Object.assign(target.style, styles(1));
            animation.cancel();
            if (options.onComplete) options.onComplete();
        };
        return {stop: () => {
            const current = getComputedStyle(target);
            if ('opacity' in to) target.style.opacity = current.opacity;
            if ('scale' in to || 'y' in to) {
                target.style.transform = current.transform;
            }
            animation.cancel();
        }};
    };

    const {beamCSS, beamThemes} = window.MauticUI.HoldBeam;
    const reducedMotion = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    function emit(element, name, detail) {
        element.dispatchEvent(new CustomEvent(name, { bubbles: true, detail }));
    }
    function register(name, selector, create) {
        const controllers = new Map();
        const mount = (root = document) => {
            const candidates = Array.from(root.querySelectorAll(selector));
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
        window.MauticUI = window.MauticUI || {};
        window.MauticUI[name] = { get: (element) => controllers.get(element), mount };
        if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", start, { once: true });
        else start();
    }
    let beamCounter = 0;
    const snappy = { type: "spring", stiffness: 500, damping: 30 };
    const easeOut = [0.22, 1, 0.36, 1];
    class HoldToConfirmButton {
        constructor(element) {
            this.element = element;
            this.events = new AbortController();
            this.sources = new Set();
            this.animations = new Map();
            this.colorScheme = window.matchMedia("(prefers-color-scheme: dark)");
            this.beamId = `mautic-hold-${++beamCounter}`;
            this.progress = 0;
            this.confirmed = false;
            this.button = element.querySelector("button");
            this.ring = element.querySelector(".hold-to-confirm__ring");
            this.progressCircle = element.querySelector(".hold-to-confirm__progress");
            this.icon = element.querySelector(".hold-to-confirm__icon");
            this.defaultIcon = this.icon.cloneNode(true);
            this.check = element.querySelector(".hold-to-confirm__check");
            this.idleLabel = element.querySelector("[data-idle-label]");
            this.confirmedLabel = element.querySelector("[data-confirmed-label]");
            this.status = element.querySelector('[role="status"]');
            this.duration = Number.isFinite(Number(element.dataset.duration)) ? Math.max(0, Number(element.dataset.duration)) : 1200;
            this.resetDelay = Number.isFinite(Number(element.dataset.resetDelay)) ? Math.max(0, Number(element.dataset.resetDelay)) : 1500;
            const options = { signal: this.events.signal };
            this.button.addEventListener("pointerdown", (event) => {
                try {
                    this.button.setPointerCapture(event.pointerId);
                } catch (error) {
                    if (!(error instanceof DOMException)) throw error;
                }
                if (event.button === 0 && event.isPrimary) this.start("pointer");
            }, options);
            for (const name of ["pointerup", "pointerleave", "pointercancel", "lostpointercapture"]) {
                this.button.addEventListener(name, () => this.cancel("pointer"), options);
            }
            this.button.addEventListener("keydown", (event) => {
                if (event.key !== " " && event.key !== "Enter") return;
                event.preventDefault();
                if (!event.repeat) this.start("keyboard");
            }, options);
            this.button.addEventListener("keyup", (event) => {
                if (event.key !== " " && event.key !== "Enter") return;
                event.preventDefault();
                this.cancel("keyboard");
            }, options);
            this.button.addEventListener("blur", () => this.cancel(), options);
            this.button.addEventListener("contextmenu", (event) => event.preventDefault(), options);
            window.addEventListener("blur", () => this.cancel(), options);
            document.addEventListener("visibilitychange", () => {
                if (document.hidden) this.cancel();
            }, options);
            this.colorScheme.addEventListener("change", () => this.renderBeam(), options);
            element.addEventListener("animationend", (event) => {
                if (event.target !== element || !event.animationName.includes("fade-out")) return;
                element.removeAttribute("data-fading");
                element.removeAttribute("data-active");
            }, options);
            this.renderBeam();
        }
        update(options) {
            if (options.className !== void 0) this.button.className = `btn btn-tertiary hold-to-confirm__button ${options.className}`;
            if (options.beamClassName !== void 0) this.element.className = `hold-to-confirm ${options.beamClassName}`;
            if ("icon" in options) {
                this.icon.replaceChildren(...options.icon ? [options.icon] : Array.from(this.defaultIcon.childNodes).map((node) => node.cloneNode(true)));
            }
            if (options.size !== void 0) {
                this.element.dataset.size = options.size;
                const dimensions = { sm: { icon: 12, ring: 20, stroke: 2 }, md: { icon: 14, ring: 24, stroke: 2 }, lg: { icon: 17, ring: 30, stroke: 2.5 } }[options.size];
                const symbol = this.element.querySelector(".hold-to-confirm__symbol");
                symbol.style.width = `${dimensions.ring}px`;
                symbol.style.height = `${dimensions.ring}px`;
                this.ring.setAttribute("viewBox", `0 0 ${dimensions.ring} ${dimensions.ring}`);
                this.ring.setAttribute("width", String(dimensions.ring));
                this.ring.setAttribute("height", String(dimensions.ring));
                for (const circle of this.ring.querySelectorAll("circle")) {
                    circle.setAttribute("cx", String(dimensions.ring / 2));
                    circle.setAttribute("cy", String(dimensions.ring / 2));
                    circle.setAttribute("r", String((dimensions.ring - dimensions.stroke) / 2));
                    circle.setAttribute("stroke-width", String(dimensions.stroke));
                }
                for (const svg of this.element.querySelectorAll("[data-default-icon], .hold-to-confirm__check svg")) {
                    svg.setAttribute("width", String(dimensions.icon));
                    svg.setAttribute("height", String(dimensions.icon));
                }
            }
            if (options.duration !== void 0 && Number.isFinite(options.duration)) {
                this.cancel();
                this.duration = Math.max(0, options.duration);
            }
            if (options.resetDelay !== void 0 && Number.isFinite(options.resetDelay)) this.resetDelay = Math.max(0, options.resetDelay);
            if ("onConfirm" in options) this.onConfirm = options.onConfirm;
            if (options.disabled !== void 0) {
                this.button.disabled = options.disabled;
                if (options.disabled) this.cancel();
            }
            if (options.label !== void 0) this.idleLabel.textContent = options.label;
            if (options.confirmedLabel !== void 0) this.confirmedLabel.textContent = options.confirmedLabel;
            for (const key of ["variant", "theme", "colorVariant", "strength", "active"]) {
                if (options[key] !== void 0) this.element.dataset[key === "active" ? "beamEnabled" : key] = key === "variant" && options[key] === "destructive" ? "danger" : String(options[key]);
            }
            this.button.setAttribute("aria-label", this.element.dataset.instructions.replace("%label%", this.idleLabel.textContent || "").replace("%seconds%", (this.duration / 1e3).toFixed(1).replace(/\.0$/, "")));
            this.button.classList.toggle('btn-danger', this.element.dataset.variant === 'danger');
            this.renderBeam();
        }
        motion(element, target, options = snappy) {
            if (this.animations.get(element)) this.animations.get(element).stop();
            this.animations.set(element, animate(element, target, reducedMotion() ? { duration: 0 } : options));
        }
        setProgress(value) {
            this.progress = value;
            this.progressCircle.style.strokeDashoffset = String(1 - value);
            this.ring.style.opacity = String(Math.max(0, Math.min(1, value / 0.04)));
        }
        start(source) {
            if (!this.element.isConnected || this.button.disabled || this.confirmed) return;
            const alreadyHolding = this.sources.size > 0;
            this.sources.add(source);
            if (alreadyHolding) return;
            this.element.dataset.state = "holding";
            this.motion(this.button, { scale: reducedMotion() ? 1 : 0.97 }, { duration: 0.2, ease: easeOut });
            if (this.fill) this.fill.stop();
            this.fill = animate(this.progress, 1, {
                duration: Math.max(0, this.duration * (1 - this.progress) / 1e3),
                ease: "linear",
                onUpdate: (value) => this.setProgress(value),
                onComplete: () => this.complete()
            });
        }
        cancel(source) {
            if (source) this.sources.delete(source);
            else this.sources.clear();
            if (this.sources.size > 0) return;
            this.motion(this.button, { scale: 1 });
            if (this.confirmed) return;
            this.element.dataset.state = "idle";
            if (this.fill) this.fill.stop();
            this.fill = animate(this.progress, 0, {
                ...reducedMotion() ? { duration: 0.15, ease: "linear" } : snappy,
                onUpdate: (value) => this.setProgress(value)
            });
        }
        swapLabel(confirmed) {
            const outgoing = confirmed ? this.idleLabel : this.confirmedLabel;
            const incoming = confirmed ? this.confirmedLabel : this.idleLabel;
            const ghost = outgoing.cloneNode(true);
            ghost.style.position = "absolute";
            ghost.setAttribute("aria-hidden", "true");
            outgoing.parentElement.append(ghost);
            outgoing.hidden = true;
            incoming.hidden = false;
            const transition = { type: "spring", stiffness: 400, damping: 30 };
            this.animations.set(ghost, animate(ghost, { y: reducedMotion() ? 0 : -6, opacity: 0 }, {
                ...reducedMotion() ? { duration: 0 } : transition,
                onComplete: () => { this.animations.delete(ghost); ghost.remove(); }
            }));
            this.motion(incoming, { y: reducedMotion() ? 0 : [6, 0], opacity: [0, 1] }, { ...transition, delay: confirmed ? 0.17 : 0 });
        }
        complete() {
            if (this.confirmed || !this.element.isConnected || this.button.disabled || document.hidden || this.sources.size === 0) return;
            this.confirmed = true;
            this.sources.clear();
            this.setProgress(1);
            this.element.dataset.state = "confirmed";
            this.motion(this.button, { scale: 1 });
            this.motion(this.icon, { scale: 0, opacity: 0 });
            this.motion(this.check, { scale: 1, opacity: 1 }, { type: "spring", stiffness: 500, damping: 22, delay: 0.05 });
            this.swapLabel(true);
            this.status.textContent = this.confirmedLabel.textContent;
            this.button.setAttribute("aria-label", this.confirmedLabel.textContent || "");
            if (this.onConfirm) this.onConfirm();
            emit(this.element, "mautic:hold-to-confirm:confirm", {});
            if (this.resetDelay > 0) this.resetTimer = setTimeout(() => this.reset(), this.resetDelay);
        }
        reset() {
            const wasConfirmed = this.confirmed;
            this.confirmed = false;
            this.sources.clear();
            clearTimeout(this.resetTimer);
            this.element.dataset.state = "idle";
            this.motion(this.button, { scale: 1 });
            this.motion(this.icon, { scale: 1, opacity: 1 });
            this.motion(this.check, { scale: 0, opacity: 0 });
            if (wasConfirmed) this.swapLabel(false);
            this.status.textContent = "";
            this.update({});
            if (this.fill) this.fill.stop();
            this.fill = animate(this.progress, 0, { duration: reducedMotion() ? 0.1 : 0.3, ease: "easeOut", onUpdate: (value) => this.setProgress(value) });
        }
        renderBeam() {
            if (this.element.dataset.variant !== "beam") {
                if (this.beamStyle) this.beamStyle.remove();
                this.beamStyle = void 0;
                this.element.removeAttribute("data-beam");
                if (this.element.querySelector("[data-beam-bloom]")) this.element.querySelector("[data-beam-bloom]").remove();
                if (this.intersection) this.intersection.disconnect();
                this.intersection = void 0;
                return;
            }
            const colorVariant = ["colorful", "mono", "ocean", "sunset"].includes(this.element.dataset.colorVariant || "") ? this.element.dataset.colorVariant : "sunset";
            const theme = ['light', 'dark'].includes(this.element.dataset.theme) ? this.element.dataset.theme : this.colorScheme.matches ? 'dark' : 'light';
            if (!this.beamStyle) this.beamStyle = document.createElement("style");
            this.beamStyle.dataset.beamStyle = this.beamId;
            this.beamStyle.textContent = beamCSS({
                ...beamThemes.md[theme],
                id: this.beamId,
                borderRadius: parseFloat(getComputedStyle(this.button).borderTopLeftRadius),
                borderWidth: 1,
                duration: 1.96,
                colorVariant,
                staticColors: colorVariant === "mono",
                brightness: 1.3,
                hueRange: 30,
                theme
            });
            if (!this.beamStyle.isConnected) document.head.append(this.beamStyle);
            this.element.dataset.beam = this.beamId;
            this.element.style.setProperty("--beam-strength", String(Math.max(0, Math.min(1, Number(this.element.dataset.strength)))));
            if (!this.element.querySelector("[data-beam-bloom]")) {
                const bloom = document.createElement("div");
                bloom.setAttribute("data-beam-bloom", "");
                this.element.append(bloom);
            }
            const active = this.element.dataset.beamEnabled !== "false" && !this.button.disabled;
            if (active) {
                this.element.setAttribute("data-active", "");
                this.element.removeAttribute("data-fading");
            } else if (this.element.hasAttribute("data-active")) {
                this.element.setAttribute("data-fading", "");
            } else this.element.removeAttribute("data-active");
            if (!this.intersection) {
                this.intersection = new IntersectionObserver((entries) => {
                    for (const entry of entries) this.element.toggleAttribute("data-paused", !entry.isIntersecting);
                }, { rootMargin: "256px" });
                this.intersection.observe(this.element);
            }
        }
        destroy() {
            this.events.abort();
            if (this.fill) this.fill.stop();
            clearTimeout(this.resetTimer);
            for (const animation of this.animations.values()) animation.stop();
            if (this.beamStyle) this.beamStyle.remove();
            if (this.intersection) this.intersection.disconnect();
        }
    }
    register("HoldToConfirmButton", '[data-component="hold-to-confirm"]', (element) => new HoldToConfirmButton(element));
})();
