import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { cn } from '@/utils/cn';

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost' | 'ghost-danger';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: Variant;
  isLoading?: boolean;
  children: ReactNode;
}

const VARIANT_CLASSES: Record<Variant, string> = {
  primary: 'bg-indigo-600 text-white hover:bg-indigo-500 focus-visible:outline-indigo-600',
  secondary:
    'bg-white text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-700',
  danger: 'bg-red-600 text-white hover:bg-red-500 focus-visible:outline-red-600',
  ghost: 'bg-transparent text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800',
  'ghost-danger': 'bg-transparent text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20',
};

export function Button({ variant = 'primary', isLoading, disabled, className, children, ...props }: ButtonProps) {
  return (
    <button
      className={cn(
        'inline-flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-semibold shadow-sm transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
        VARIANT_CLASSES[variant],
        className,
      )}
      disabled={disabled || isLoading}
      {...props}
    >
      {isLoading && (
        <span
          className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
          aria-hidden="true"
        />
      )}
      {children}
    </button>
  );
}
