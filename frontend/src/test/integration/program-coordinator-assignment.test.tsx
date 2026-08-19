import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { renderWithProviders } from '@/test/renderWithProviders';
import { DashboardPage } from '@/pages/DashboardPage';
import { authService } from '@/features/auth/authService';
import { programsService } from '@/features/programs/programsService';
import { departmentsService } from '@/features/departments/departmentsService';
import { usersService } from '@/features/users/usersService';
import type { User } from '@/types/user';
import type { Program } from '@/types/program';
import type { PaginatedResponse } from '@/types/api';

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

vi.mock('@/features/users/usersService', () => ({
  usersService: {
    list: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
    assignRole: vi.fn(),
    syncPermissions: vi.fn(),
  },
}));

const mockedAuthService = vi.mocked(authService);
const mockedProgramsService = vi.mocked(programsService);
const mockedDepartmentsService = vi.mocked(departmentsService);
const mockedUsersService = vi.mocked(usersService);

const admin: User = {
  id: 'admin-1',
  name: 'Grace Hopper',
  email: 'admin@pams.test',
  roles: ['admin'],
  direct_permissions: [],
  effective_permissions: ['users.manage'],
  is_active: true,
};

const alreadyAssigned: User = {
  id: 'coord-1',
  name: 'Ada Lovelace',
  email: 'ada@pams.test',
  roles: ['program_coordinator'],
  direct_permissions: [],
  effective_permissions: [],
  is_active: true,
};

const notYetAssigned: User = {
  id: 'coord-2',
  name: 'Katherine Johnson',
  email: 'katherine@pams.test',
  roles: ['program_coordinator'],
  direct_permissions: [],
  effective_permissions: [],
  is_active: true,
};

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
    coordinators: [],
    objectives_count: 0,
    learning_outcomes_count: 0,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  };
}

function candidatesPage(users: User[]): PaginatedResponse<User> {
  return {
    data: users,
    meta: { current_page: 1, last_page: 1, per_page: 100, total: users.length },
    links: { first: null, last: null, prev: null, next: null },
  };
}

/**
 * Coordinator assignment lives inside the Program edit modal (P0.2) — this
 * exercises the full composition (DashboardPage -> ProgramFormModal ->
 * ProgramCoordinatorsPanel) with only the service modules mocked.
 */
describe('program coordinator assignment', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockedAuthService.me.mockResolvedValue(admin);
    mockedDepartmentsService.list.mockResolvedValue([{ id: 1, code: 'ENG', name: 'Engineering' }]);
  });

  it('shows the currently assigned coordinator and only offers program_coordinator-role users as candidates', async () => {
    const program = makeProgram({ coordinators: [{ id: alreadyAssigned.id, name: alreadyAssigned.name, email: alreadyAssigned.email }] });
    mockedProgramsService.list.mockResolvedValue([program]);
    mockedProgramsService.show.mockResolvedValue(program);
    mockedUsersService.list.mockResolvedValue(candidatesPage([alreadyAssigned, notYetAssigned]));

    const user = userEvent.setup();
    renderWithProviders(<DashboardPage />, { route: '/' });

    await waitFor(() => expect(mockedProgramsService.list).toHaveBeenCalledTimes(1));
    await user.click(await screen.findByRole('button', { name: 'Edit' }));

    const dialog = screen.getByRole('heading', { name: 'Edit program' }).closest('div')!;

    // Already-assigned coordinator is shown in the "current" list.
    expect(await within(dialog).findByText('Ada Lovelace')).toBeInTheDocument();

    // The candidate picker only lists program_coordinator users who are NOT already assigned.
    const picker = within(dialog).getByLabelText('Assign coordinator');
    expect(within(picker).getByText(/Katherine Johnson/)).toBeInTheDocument();
    expect(within(picker).queryByText(/Ada Lovelace/)).not.toBeInTheDocument();

    // The role filter was actually requested from the backend, not just assumed client-side.
    expect(mockedUsersService.list).toHaveBeenCalledWith(1, { role: 'program_coordinator', per_page: 100 });
  });

  it('assigns the selected candidate and refreshes the program', async () => {
    const program = makeProgram({ coordinators: [] });
    mockedProgramsService.list.mockResolvedValue([program]);
    mockedProgramsService.show.mockResolvedValue(program);
    mockedUsersService.list.mockResolvedValue(candidatesPage([notYetAssigned]));
    mockedProgramsService.assignCoordinator.mockResolvedValue({
      ...program,
      coordinators: [{ id: notYetAssigned.id, name: notYetAssigned.name, email: notYetAssigned.email }],
    });

    const user = userEvent.setup();
    renderWithProviders(<DashboardPage />, { route: '/' });

    await waitFor(() => expect(mockedProgramsService.list).toHaveBeenCalledTimes(1));
    await user.click(await screen.findByRole('button', { name: 'Edit' }));

    const dialog = screen.getByRole('heading', { name: 'Edit program' }).closest('div')!;
    const picker = await within(dialog).findByLabelText('Assign coordinator');

    await user.selectOptions(picker, notYetAssigned.id);
    await user.click(within(dialog).getByRole('button', { name: 'Assign' }));

    await waitFor(() => {
      expect(mockedProgramsService.assignCoordinator).toHaveBeenCalledWith('prog-1', notYetAssigned.id);
    });
  });

  it('shows an error when assignment is rejected (e.g. the selected user lacks the coordinator role)', async () => {
    const { ApiError } = await import('@/utils/apiError');
    const program = makeProgram({ coordinators: [] });
    mockedProgramsService.list.mockResolvedValue([program]);
    mockedProgramsService.show.mockResolvedValue(program);
    mockedUsersService.list.mockResolvedValue(candidatesPage([notYetAssigned]));
    mockedProgramsService.assignCoordinator.mockRejectedValue(
      new ApiError('Only users with the Program Coordinator role can be assigned to a program.', 422),
    );

    const user = userEvent.setup();
    renderWithProviders(<DashboardPage />, { route: '/' });

    await waitFor(() => expect(mockedProgramsService.list).toHaveBeenCalledTimes(1));
    await user.click(await screen.findByRole('button', { name: 'Edit' }));

    const dialog = screen.getByRole('heading', { name: 'Edit program' }).closest('div')!;
    const picker = await within(dialog).findByLabelText('Assign coordinator');

    await user.selectOptions(picker, notYetAssigned.id);
    await user.click(within(dialog).getByRole('button', { name: 'Assign' }));

    expect(await screen.findByText('Only users with the Program Coordinator role can be assigned to a program.')).toBeInTheDocument();
  });
});
