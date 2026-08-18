import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { ChevronLeftIcon } from '@/components/icons';

interface PageHeaderProps {
  title: string;
  description?: string;
  /** Renders a small "← label" link above the title (e.g. back to a parent program). */
  backTo?: { to: string; label: string };
  actions?: ReactNode;
}

/**
 * The one place every page's title row is decided — before this, each
 * page hand-rolled its own `<h1>` + flex row, drifting slightly in
 * spacing/type-scale from page to page.
 */
export function PageHeader({ title, description, backTo, actions }: PageHeaderProps) {
  return (
    <div className="border-b border-gray-200 pb-5 dark:border-gray-800">
      {backTo && (
        <Link
          to={backTo.to}
          className="mb-2 inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-brand-700 dark:text-gray-400 dark:hover:text-brand-300"
        >
          <ChevronLeftIcon className="h-4 w-4" />
          {backTo.label}
        </Link>
      )}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-xl font-semibold tracking-tight text-brand-950 dark:text-white sm:text-2xl">{title}</h1>
          {description && <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{description}</p>}
        </div>
        {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
      </div>
    </div>
  );
}
