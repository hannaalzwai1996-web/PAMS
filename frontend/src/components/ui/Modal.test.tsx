import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Modal } from './Modal';

describe('Modal', () => {
  it('renders nothing when closed', () => {
    render(
      <Modal isOpen={false} onClose={vi.fn()} title="Create user">
        <p>Form contents</p>
      </Modal>,
    );

    expect(screen.queryByText('Create user')).not.toBeInTheDocument();
    expect(screen.queryByText('Form contents')).not.toBeInTheDocument();
  });

  it('renders the title and children when open', () => {
    render(
      <Modal isOpen onClose={vi.fn()} title="Create user">
        <p>Form contents</p>
      </Modal>,
    );

    expect(screen.getByText('Create user')).toBeInTheDocument();
    expect(screen.getByText('Form contents')).toBeInTheDocument();
  });

  it('calls onClose when the Escape key is pressed', async () => {
    const onClose = vi.fn();
    const user = userEvent.setup();
    render(
      <Modal isOpen onClose={onClose} title="Create user">
        <p>Form contents</p>
      </Modal>,
    );

    await user.keyboard('{Escape}');

    expect(onClose).toHaveBeenCalledOnce();
  });

  it('calls onClose when the backdrop is clicked', async () => {
    const onClose = vi.fn();
    const user = userEvent.setup();
    render(
      <Modal isOpen onClose={onClose} title="Create user">
        <p>Form contents</p>
      </Modal>,
    );

    await user.click(screen.getByLabelText('Close'));

    expect(onClose).toHaveBeenCalledOnce();
  });
});
