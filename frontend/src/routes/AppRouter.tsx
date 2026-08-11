import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { ProtectedRoute } from './ProtectedRoute';
import { RequireRole } from './RequireRole';
import { AppLayout } from '@/components/layout/AppLayout';
import { LoginPage } from '@/pages/LoginPage';
import { DashboardPage } from '@/pages/DashboardPage';
import { UsersPage } from '@/pages/UsersPage';
import { ProgramObjectivesPage } from '@/pages/ProgramObjectivesPage';
import { LearningOutcomesPage } from '@/pages/LearningOutcomesPage';
import { MatrixPage } from '@/pages/MatrixPage';
import { NotFoundPage } from '@/pages/NotFoundPage';

/**
 * Data-fetching stays entirely in React Query hooks inside page/feature
 * components (see ARCH-0003) — deliberately not using react-router's
 * loader/action data APIs, so there is exactly one pattern for "how does
 * data get onto the screen," not two competing ones.
 */
export function AppRouter() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<LoginPage />} />

        <Route element={<ProtectedRoute />}>
          <Route element={<AppLayout />}>
            <Route path="/" element={<DashboardPage />} />

            <Route path="/programs/:programId/objectives" element={<ProgramObjectivesPage />} />
            <Route path="/programs/:programId/learning-outcomes" element={<LearningOutcomesPage />} />
            <Route path="/programs/:programId/matrix" element={<MatrixPage />} />

            <Route element={<RequireRole roles={['admin']} />}>
              <Route path="/admin/users" element={<UsersPage />} />
            </Route>
          </Route>
        </Route>

        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </BrowserRouter>
  );
}
