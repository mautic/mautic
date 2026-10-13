import * as React from 'react';
import { createRoot } from 'react-dom/client';
import { flushSync } from 'react-dom';
import { Checkbox } from './src/components/animate-ui/components/radix/checkbox';

const root = createRoot(document.querySelector('#reference')!);
let options = (window as any).referenceOptions ?? {};
(window as any).referenceUpdate = (next: Record<string, unknown>) => {
  options = { ...options, ...next };
  flushSync(() => root.render(<Checkbox aria-label="Accept" {...options} />));
};
(window as any).referenceUpdate({});
