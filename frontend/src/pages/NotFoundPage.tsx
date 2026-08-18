import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { InboxIcon } from '@/components/icons';

export function NotFoundPage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-surface px-4 text-center dark:bg-surface-dark">
      <span className="flex h-14 w-14 items-center justify-center rounded-md bg-brand-50 text-brand-400 dark:bg-gray-800 dark:text-gray-500">
        <InboxIcon className="h-7 w-7" />
      </span>
      <div>
        <h1 className="text-2xl font-semibold text-brand-950 dark:text-white">Page not found</h1>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
          The page you're looking for doesn't exist or may have moved.
        </p>
      </div>
      <Link to="/">
        <Button>Back to dashboard</Button>
      </Link>
    </div>
  );
}
