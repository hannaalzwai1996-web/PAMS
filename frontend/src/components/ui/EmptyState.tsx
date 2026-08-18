import type { ReactNode } from 'react';

interface EmptyStateProps {
  icon: ReactNode;
  title: string;
  description?: string;
  action?: ReactNode;
}

/** The one shared "nothing here yet" panel — every table/grid used a bare gray paragraph before this. */
export function EmptyState({ icon, title, description, action }: EmptyStateProps) {
  return (
    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 px-6 py-14 text-center dark:border-gray-700">
      <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
        {icon}
      </div>
      <h3 className="mt-4 text-sm font-semibold text-gray-900 dark:text-white">{title}</h3>
      {description && <p className="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{description}</p>}
      {action && <div className="mt-5">{action}</div>}
    </div>
  );
}
