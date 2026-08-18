import type { ReactNode } from 'react';
import { cn } from '@/utils/cn';

type Color = 'gray' | 'green' | 'yellow' | 'red' | 'blue' | 'brand';

/** Muted, document-tag tones rather than saturated "status pill" colors — kept distinguishable, not decorative. */
const COLOR_CLASSES: Record<Color, string> = {
  gray: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  green: 'bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800',
  yellow: 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800',
  red: 'bg-red-50 text-red-800 ring-1 ring-inset ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800',
  blue: 'bg-brand-50 text-brand-800 ring-1 ring-inset ring-brand-200 dark:bg-brand-900/40 dark:text-brand-200 dark:ring-brand-800',
  brand: 'bg-brand-800 text-white dark:bg-brand-700',
};

export function Badge({ color = 'gray', children }: { color?: Color; children: ReactNode }) {
  return (
    <span className={cn('inline-flex items-center rounded px-2 py-0.5 text-xs font-medium', COLOR_CLASSES[color])}>
      {children}
    </span>
  );
}
