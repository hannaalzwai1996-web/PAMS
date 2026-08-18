import type { ReactNode } from 'react';

export function Table({ children }: { children: ReactNode }) {
  return (
    <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">{children}</table>
    </div>
  );
}

export function TableHead({ children }: { children: ReactNode }) {
  return <thead className="bg-gray-50 dark:bg-gray-800">{children}</thead>;
}

export function TableBody({ children }: { children: ReactNode }) {
  return (
    <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">{children}</tbody>
  );
}

export function TableRow({ children }: { children: ReactNode }) {
  return <tr className="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/60">{children}</tr>;
}

export function TableHeaderCell({ children }: { children: ReactNode }) {
  return (
    <th className="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
      {children}
    </th>
  );
}

export function TableCell({ children }: { children: ReactNode }) {
  return <td className="px-4 py-3 align-middle text-sm text-gray-700 dark:text-gray-200">{children}</td>;
}
