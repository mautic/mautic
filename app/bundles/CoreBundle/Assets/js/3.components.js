(() => {
    if (window.MauticUI && window.MauticUI.HoldToConfirmButton) return;

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
    class HoldToConfirmButton {
        constructor(element) {
            this.element = element;
            this.events = new AbortController();
            this.sources = new Set();
            this.confirmed = false;
            this.button = element.querySelector("button");
            this.ring = element.querySelector(".hold-to-confirm__ring");
            this.progressCircle = element.querySelector(".hold-to-confirm__progress");
            this.icon = element.querySelector(".hold-to-confirm__icon");
            this.defaultIcon = this.icon.cloneNode(true);
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
        }
        update(options) {
            if (options.className !== void 0) this.button.className = `btn btn-tertiary btn-nospin hold-to-confirm__button ${options.className}`;
            if ("icon" in options) {
                this.icon.replaceChildren(...options.icon ? [options.icon] : Array.from(this.defaultIcon.childNodes).map((node) => node.cloneNode(true)));
            }
            if (options.size !== void 0) {
                this.element.dataset.size = options.size;
                this.button.setAttribute('size', options.size);
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
            if (options.variant !== void 0) this.element.dataset.variant = options.variant === "neutral" ? "neutral" : "danger";
            this.button.setAttribute("aria-label", this.element.dataset.instructions.replace("%label%", this.idleLabel.textContent || "").replace("%seconds%", (this.duration / 1e3).toFixed(1).replace(/\.0$/, "")));
            this.button.classList.toggle('btn-danger', this.element.dataset.variant === 'danger');
        }
        setProgress(value) {
            this.progressCircle.style.strokeDashoffset = String(1 - value);
            this.ring.style.opacity = String(Math.max(0, Math.min(1, value / 0.04)));
        }
        start(source) {
            if (!this.element.isConnected || this.button.disabled || this.confirmed) return;
            const alreadyHolding = this.sources.size > 0;
            this.sources.add(source);
            if (alreadyHolding) return;
            cancelAnimationFrame(this.frame);
            const progress = Math.max(0, Math.min(1, 1 - parseFloat(getComputedStyle(this.progressCircle).strokeDashoffset)));
            this.element.dataset.state = "holding";
            this.setProgress(progress);
            const start = performance.now();
            const duration = this.duration * (1 - progress);
            const tick = (now) => {
                const fraction = duration ? Math.max(0, Math.min(1, (now - start) / duration)) : 1;
                this.setProgress(progress + (1 - progress) * fraction);
                if (fraction < 1) this.frame = requestAnimationFrame(tick);
                else this.complete();
            };
            this.frame = requestAnimationFrame(tick);
        }
        cancel(source) {
            if (source) this.sources.delete(source);
            else this.sources.clear();
            if (this.sources.size > 0) return;
            if (this.confirmed) return;
            cancelAnimationFrame(this.frame);
            this.element.dataset.state = "idle";
            this.setProgress(0);
        }
        swapLabel(confirmed) {
            const outgoing = confirmed ? this.idleLabel : this.confirmedLabel;
            const incoming = confirmed ? this.confirmedLabel : this.idleLabel;
            this.element.querySelectorAll(".hold-to-confirm__label-out").forEach((ghost) => ghost.remove());
            if (!reducedMotion()) {
                outgoing.parentElement.style.setProperty("--hold-to-confirm-label-width", `${outgoing.offsetWidth}px`);
                const ghost = outgoing.cloneNode(true);
                ghost.classList.remove("hold-to-confirm__label-in");
                ghost.classList.add("hold-to-confirm__label-out");
                ghost.setAttribute("aria-hidden", "true");
                const removeGhost = (event) => {
                    if (event.target === ghost) ghost.remove();
                };
                for (const name of ["animationend", "animationcancel"]) {
                    ghost.addEventListener(name, removeGhost, { signal: this.events.signal });
                }
                outgoing.parentElement.append(ghost);
            }
            outgoing.hidden = true;
            incoming.hidden = false;
            incoming.classList.add("hold-to-confirm__label-in");
        }
        complete() {
            if (this.confirmed || !this.element.isConnected || this.button.disabled || document.hidden || this.sources.size === 0) return;
            this.confirmed = true;
            this.sources.clear();
            this.setProgress(1);
            this.element.dataset.state = "confirmed";
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
            if (wasConfirmed) this.swapLabel(false);
            this.status.textContent = "";
            this.update({});
            cancelAnimationFrame(this.frame);
            this.setProgress(0);
        }
        destroy() {
            this.events.abort();
            cancelAnimationFrame(this.frame);
            clearTimeout(this.resetTimer);
            this.element.querySelectorAll(".hold-to-confirm__label-out").forEach((ghost) => ghost.remove());
        }
    }
    register("HoldToConfirmButton", '[data-component="hold-to-confirm"]', (element) => new HoldToConfirmButton(element));
})();
