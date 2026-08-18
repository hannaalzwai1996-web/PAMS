import { useEffect, type ReactNode } from 'react';
import { XIcon } from '@/components/icons';

interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
}

/**
 * A minimal, dependency-free modal (no Headless UI/Radix) — closes on
 * Escape, backdrop click, or the close button, which covers this app's
 * needs (confirmation dialogs, small forms) without pulling in a
 * component library the ADR never called for.
 *
 * The close button is an absolute-positioned *sibling* of the <h2>, not a
 * wrapper around it — tests locate the dialog via
 * `getByRole('heading', ...).closest('div')`, which only works if that
 * heading's nearest ancestor <div> is still the one panel div containing
 * the form body, not a narrower header row.
 */
export function Modal({ isOpen, onClose, title, children }: ModalProps) {
  useEffect(() => {
    if (!isOpen) return;

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') onClose();
    }

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <button
        type="button"
        aria-label="Close"
        className="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
        onClick={onClose}
      />
      <div className="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl ring-1 ring-black/5 dark:bg-gray-900 dark:ring-white/10">
        <button
          type="button"
          aria-label="Close dialog"
          onClick={onClose}
          className="absolute right-4 top-4 rounded-md p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
        >
          <XIcon className="h-5 w-5" />
        </button>
        <h2 className="pr-8 text-base font-semibold text-gray-900 dark:text-white">{title}</h2>
        <div className="mt-4">{children}</div>
      </div>
    </div>
  );
}
