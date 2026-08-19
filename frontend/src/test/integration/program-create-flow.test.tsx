import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { renderWithProviders } from '@/test/renderWithProviders';
import { DashboardPage } from '@/pages/DashboardPage';
import { authService } from '@/features/auth/authService';
import { programsService } from '@/features/programs/programsService';
import { departmentsService } from '@/features/departments/departmentsService';
import { ApiError } from '@/utils/apiError';
import type { User } from '@/types/user';
import type { Program } from '@/types/program';
import type { Department } from '@/types/department';

vi.mock('@/features/auth/authService', () => ({
  authService: { login: vi.fn(), logout: vi.fn(), me: vi.fn() },
}));

vi.mock('@/features/programs/programsService', () => ({
  programsService: {
    list: vi.fn(),
    show: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
    assignCoordinator: vi.fn(),
    unassignCoordinator: vi.fn(),
  },
}));

vi.mock('@/features/departments/departmentsService', () => ({
  departmentsService: { list: vi.fn() },
}));

const mockedAuthService = vi.mocked(authService);
const mockedProgramsService = vi.mocked(programsService);
const mockedDepartmentsService = vi.mocked(departmentsService);

const admin: User = {
  id: 'admin-1',
  name: 'Grace Hopper',
  email: 'admin@pams.test',
  roles: ['admin'],
  direct_permissions: [],
  effective_permissions: ['users.manage'],
  is_active: true,
};

const department: Department = { id: 1, code: 'ENG', name: 'Engineering' };

function makeProgram(overrides: Partial<Program> = {}): Program {
  return {
    id: 'prog-1',
    code: 'BSCS-2026',
    name: 'BSc Computer Science',
    level: 'bachelor',
    description: '',
    status: 'draft',
    duration_years: 4,
    department: { id: 1, code: 'ENG', name: 'Engineering' },
    objectives_count: 0,
    learning_outcomes_count: 0,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  };
}

/**
 * Exercises DashboardPage's full composition — useAuth (admin-only
 * "Create program" action/policy hint), usePrograms, ProgramsGrid, and
 * ProgramFormModal — together, with only the service modules mocked, same
 * approach as the Users create-flow integration test.
 */
describe('program create flow', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockedAuthService.me.mockResolvedValue(admin);
    mockedDepartmentsService.list.mockResolvedValue([department]);
  });

  it('creates a program through the form and the new program appears on the dashboard', async () => {
    mockedProgramsService.list.mockResolvedValueOnce([]).mockResolvedValueOnce([makeProgram()]);
    mockedProgramsService.create.mockResolvedValue(makeProgram());

    const user = userEvent.setup();
    renderWithProviders(<DashboardPage />, { route: '/' });

    await waitFor(() => expect(mockedProgramsService.list).toHaveBeenCalledTimes(1));

    await user.click(await screen.findByRole('button', { name: 'Create program' }));

    const dialog = screen.getByRole('heading', { name: 'Create program' }).closest('div')!;
    await waitFor(() => expect(within(dialog).getByRole('option', { name: 'Engineering' })).toBeInTheDocument());

    await user.selectOptions(within(dialog).getByLabelText('Department'), '1');
    await user.type(within(dialog).getByLabelText('Program Code'), 'BSCS-2026');
    await user.type(within(dialog).getByLabelText('Program Name'), 'BSc Computer Science');
    await user.click(within(dialog).getByRole('button', { name: 'Create program' }));

    await waitFor(() => {
      expect(mockedProgramsService.create.mock.calls[0]?.[0]).toEqual({
        department_id: 1,
        code: 'BSCS-2026',
        name: 'BSc Computer Science',
        level: 'bachelor',
        description: '',
        duration_years: 4,
      });
    });

    // Creating invalidates the programs query, which refetches — the new program should appear without a manual reload.
    expect(await screen.findByText('BSc Computer Science')).toBeInTheDocument();
  });

  it('shows a field-level error when the backend rejects the submission', async () => {
    mockedProgramsService.list.mockResolvedValue([]);
    mockedProgramsService.create.mockRejectedValue(
      new ApiError('The given data was invalid.', 422, { code: ['Program code has already been taken.'] }),
    );

    const user = userEvent.setup();
    renderWithProviders(<DashboardPage />, { route: '/' });

    await waitFor(() => expect(mockedProgramsService.list).toHaveBeenCalledTimes(1));
    await user.click(await screen.findByRole('button', { name: 'Create program' }));

    const dialog = screen.getByRole('heading', { name: 'Create program' }).closest('div')!;
    await waitFor(() => expect(within(dialog).getByRole('option', { name: 'Engineering' })).toBeInTheDocument());

    await user.selectOptions(within(dialog).getByLabelText('Department'), '1');
    await user.type(within(dialog).getByLabelText('Program Code'), 'DUPLICATE');
    await user.type(within(dialog).getByLabelText('Program Name'), 'Some Program');
    await user.click(within(dialog).getByRole('button', { name: 'Create program' }));

    expect(await screen.findByText('Program code has already been taken.')).toBeInTheDocument();
  });

  it('does not show the "Create program" action to a non-admin', async () => {
    mockedAuthService.me.mockResolvedValue({
      ...admin,
      id: 'coordinator-1',
      roles: ['program_coordinator'],
    });
    mockedProgramsService.list.mockResolvedValue([]);

    renderWithProviders(<DashboardPage />, { route: '/' });

    // Wait for something that only renders once auth has actually
    // resolved (not just the programs list, which fires independently of
    // auth state) before asserting the admin-only action is absent.
    await screen.findByText('Welcome, Grace Hopper');
    await waitFor(() => expect(mockedProgramsService.list).toHaveBeenCalledTimes(1));

    expect(screen.queryByRole('button', { name: 'Create program' })).not.toBeInTheDocument();
  });
});
