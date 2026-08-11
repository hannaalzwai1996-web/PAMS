import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { FormField } from './FormField';
import { Input } from './Input';

describe('FormField', () => {
  it('associates the label with the field via htmlFor', () => {
    render(
      <FormField label="Email" htmlFor="email">
        <Input id="email" />
      </FormField>,
    );

    expect(screen.getByLabelText('Email')).toBeInTheDocument();
  });

  it('shows the error message when one is present', () => {
    render(
      <FormField label="Email" htmlFor="email" error="Email is required." hint="We'll never share this.">
        <Input id="email" />
      </FormField>,
    );

    expect(screen.getByText('Email is required.')).toBeInTheDocument();
    expect(screen.queryByText("We'll never share this.")).not.toBeInTheDocument();
  });

  it('falls back to the hint when there is no error', () => {
    render(
      <FormField label="Email" htmlFor="email" hint="We'll never share this.">
        <Input id="email" />
      </FormField>,
    );

    expect(screen.getByText("We'll never share this.")).toBeInTheDocument();
  });
});
