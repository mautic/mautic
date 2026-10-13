# Original React animation reference

`src/` contains byte-for-byte snapshots of the Acervo Checkbox component, primitive and their local helpers. `sources.json` records their original relative paths and SHA-256 fingerprints. These files are test fixtures, not production dependencies.

The committed CSS is the original comparison stylesheet compiled with Tailwind CSS 4.1.17 and the source theme tokens. It includes the utility rules needed by the source component. Both scenes receive its reset and theme so browser defaults cannot bias the comparison; only the native scene receives the CoreBundle component CSS.

`checkbox-motion.spec.mjs` builds the actual React reference with esbuild and renders the actual Twig and PHP-minified CSS/JavaScript separately. It compares intermediate scale, normalized SVG stroke length, opacity, geometry, colors and screenshots. The matrix includes every source size and variant, both themes and both system motion preferences. It covers checking, unchecking, indeterminate replacement, gesture priority, Enter, blur, touch filtering, secondary buttons and interrupted animations.

The two pages share one BrowserContext clock. Advancing either page advances both, so the clock must be advanced once per sampled frame. Playwright's clock does not control the browser compositor's Web Animations timeline. The test pauses browser animations at creation, preserves Motion's requested start time and advances currentTime explicitly. Keyframes, durations, easing and application code are unchanged. CSS transitions use the interaction time as their origin. Awaiting animation.ready prevents pending compositor pauses from racing screenshots. Assertions require visible intermediate opacity and stroke values so a frozen or prematurely completed animation cannot masquerade as a passing comparison.

Numeric trajectory assertions use four decimal places and SVG bounds use the same numeric tolerance and computed colors must match exactly. Screenshot comparisons use Pixelmatch threshold 0.05, the same perceptual tolerance as the earlier static comparisons. Small browser rasterization differences can remain below this threshold; a passing test is not a claim of byte-identical PNGs or equivalence for arbitrary host overrides or custom Motion props.

Run `npm ci`, `npm run build` and `npm test` from the parent components directory, through DDEV locally. React and Radix are devDependencies used only by the reference. The native bundle's esbuild dependency graph is checked separately to reject React runtime imports.
