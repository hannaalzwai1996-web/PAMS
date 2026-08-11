import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Badge } from './Badge';

describe('Badge', () => {
  it('renders its children', () => {
    render(<Badge color="green">Active</Badge>);

    expect(screen.getByText('Active')).toBeInTheDocument();
  });

  it('applies a different class per color', () => {
    const { rerender } = render(<Badge color="green">Active</Badge>);
    const greenClasses = screen.getByText('Active').className;

    rerender(<Badge color="red">Inactive</Badge>);
    const redClasses = screen.getByText('Inactive').className;

    expect(greenClasses).not.toBe(redClasses);
  });
});
