import { Link } from 'react-router-dom';

export function NotFoundPage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-2 text-center">
      <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Page not found</h1>
      <Link to="/" className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
        Back to dashboard
      </Link>
    </div>
  );
}
