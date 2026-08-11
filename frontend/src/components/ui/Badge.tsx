import type { ReactNode } from 'react';
import { cn } from '@/utils/cn';

type Color = 'gray' | 'green' | 'yellow' | 'red' | 'blue' | 'indigo';

const COLOR_CLASSES: Record<Color, string> = {
  gray: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  green: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
  yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
  red: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
  blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
  indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
};

export function Badge({ color = 'gray', children }: { color?: Color; children: ReactNode }) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
        COLOR_CLASSES[color],
      )}
    >
      {children}
    </span>
  );
}
