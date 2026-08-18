import type { TextareaHTMLAttributes } from 'react';
import { cn } from '@/utils/cn';

interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  hasError?: boolean;
}

/** Was duplicated inline (identical className) in both ProgramObjectiveFormModal and LearningOutcomeFormModal. */
export function Textarea({ hasError, className, ...props }: TextareaProps) {
  return (
    <textarea
      className={cn(
        'block w-full rounded-md border-0 px-3 py-1.5 text-sm text-gray-900 ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-brand-700 dark:bg-gray-800 dark:text-white',
        hasError ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-300 dark:ring-gray-600',
        className,
      )}
      {...props}
    />
  );
}
