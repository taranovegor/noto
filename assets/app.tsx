import '@vitejs/plugin-react/preamble';
import React, { Suspense, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { createBrowserRouter, Navigate, RouterProvider } from 'react-router-dom';
import { Provider } from 'react-redux';
import { useAppDispatch } from './shared/store/hooks';
import { setUser, setIsInitialized, setTokens } from './shared/store/authSlice';
import { useAuth } from './features/auth/hooks/useAuth';
import { App } from './layout/App';
import { LoginPage } from './features/auth/components/LoginPage';
import { ProtectedRoute } from './shared/components/ProtectedRoute';
import { RouteErrorBoundary } from './shared/components/RouteErrorBoundary';
import { CentrifugoProvider } from './shared/websocket';
import { TasksListShell } from './features/tasks/components/TasksListShell';
import { NotesListShell } from './features/notes/components/NotesListShell';
import { StashesListShell } from './features/stashes/components/StashesListShell';
import { SettingsPage } from './features/settings/components/SettingsPage';
import { TaskPageSkeleton } from './features/tasks/components/TaskPageSkeleton';
import { NotePageSkeleton } from './features/notes/components/NotePageSkeleton';
import { LOGIN_ROUTE, TASKS_ROUTE } from './features/auth';
import { tokenStorage } from './shared/utils/tokenStorage';
import { authApi } from './features/auth/store/api';
import { store } from './shared/store';

const TaskPage = React.lazy(() =>
  import('./features/tasks/components/TaskPage').then((m) => ({ default: m.TaskPage })),
);
const NotePage = React.lazy(() =>
  import('./features/notes/components/NotePage').then((m) => ({ default: m.NotePage })),
);
const RefsPage = React.lazy(() =>
  import('./features/refs/RefsPage').then((m) => ({ default: m.RefsPage })),
);

// Auth initializer component - runs on app mount
function AuthInitializer() {
  const dispatch = useAppDispatch();
  const { isInitialized } = useAuth();

  useEffect(() => {
    const initAuth = async () => {
      const accessToken = tokenStorage.getAccessToken();
      const refreshToken = tokenStorage.getRefreshToken();
      const rememberMe = tokenStorage.getRememberMe();

      if (accessToken && refreshToken) {
        dispatch(setTokens({ accessToken, refreshToken, rememberMe }));

        try {
          const user = await dispatch(authApi.endpoints.getCurrentUser.initiate()).unwrap();
          dispatch(setUser(user));
        } catch (error) {
          console.error('Failed to fetch user:', error);
        }
      }

      dispatch(setIsInitialized(true));
    };

    if (!isInitialized) {
      void initAuth();
    }
  }, [dispatch, isInitialized]);

  return null;
}

const router = createBrowserRouter([
  {
    path: LOGIN_ROUTE,
    element: <LoginPage />,
  },
  {
    element: (
      <>
        <AuthInitializer />
        <ProtectedRoute>
          <CentrifugoProvider>
            <App />
          </CentrifugoProvider>
        </ProtectedRoute>
      </>
    ),
    errorElement: <RouteErrorBoundary />,
    children: [
      { index: true, element: <Navigate to={TASKS_ROUTE} replace /> },
      {
        path: 'tasks',
        element: <TasksListShell />,
      },
      {
        path: 'tasks/:id',
        element: (
          <Suspense fallback={<TaskPageSkeleton />}>
            <TaskPage />
          </Suspense>
        ),
      },
      {
        path: 'notes',
        element: <NotesListShell />,
      },
      {
        path: 'notes/:id',
        element: (
          <Suspense fallback={<NotePageSkeleton />}>
            <NotePage />
          </Suspense>
        ),
      },
      {
        path: 'refs/:id',
        element: (
          <Suspense fallback={null}>
            <RefsPage />
          </Suspense>
        ),
      },
      {
        path: 'stashes',
        element: <StashesListShell />,
      },
      {
        path: 'settings',
        element: <SettingsPage />,
      },
    ],
  },
]);

const container = document.getElementById('app')!;
// Reuse existing root on HMR to avoid React 18 double-createRoot warning
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const reactRoot = (container as any).__r ?? ((container as any).__r = createRoot(container));

reactRoot.render(
  <Provider store={store}>
    <RouterProvider router={router} future={{ v7_startTransition: true }} />
  </Provider>,
);
