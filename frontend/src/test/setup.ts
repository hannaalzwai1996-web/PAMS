import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach } from 'vitest';

// Unmounts every rendered tree between tests — without this, components
// (and their effects/timers) from one test can leak into the next.
afterEach(() => {
  cleanup();
});
