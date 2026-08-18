import type { SelectHTMLAttributes } from 'react';
import { cn } from '@/utils/cn';

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  hasError?: boolean;
}

export function Select({ hasError, className, children, ...props }: SelectProps) {
  return (
    <select
      className={cn(
        'block w-full rounded-md border-0 px-3 py-1.5 text-sm text-gray-900 ring-1 ring-inset focus:ring-2 focus:ring-inset focus:ring-brand-700 dark:bg-gray-800 dark:text-white',
        hasError ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-300 dark:ring-gray-600',
        className,
      )}
      {...props}
    >
      {children}
    </select>
  );
}
