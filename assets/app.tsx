import '@vitejs/plugin-react/preamble';
import React, { Suspense, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { createBrowserRouter, Navigate, RouterProvider } from 'react-router-dom';
import { Provider } from 'react-redux';
import { useAppDispatch } from './shared/store/hooks';
import {
  setUser,
  setIsInitialized,
  setIsLoading,
  setError,
  setCentrifugoConfig,
} from './shared/store/authSlice';
import { useAuth } from './features/auth/hooks/useAuth';
import { App } from './layout/App';
import { ProtectedRoute } from './shared/components/ProtectedRoute';
import { RouteErrorBoundary } from './shared/components/RouteErrorBoundary';
import { CentrifugoProvider } from './shared/websocket';
import { TasksListShell } from './features/tasks/components/TasksListShell';
import { MemosListShell } from './features/memos/components/MemosListShell';
import { NotebooksListShell } from './features/notebooks/components/NotebooksListShell';
import { StashesListShell } from './features/stashes/components/StashesListShell';
import { SettingsPage } from './features/settings/components/SettingsPage';
import { TaskPageSkeleton } from './features/tasks/components/TaskPageSkeleton';
import { MemoPageSkeleton } from './features/memos/components/MemoPageSkeleton';
import { NotebookPageSkeleton } from './features/notebooks/components/NotebookPageSkeleton';
import { NotePageSkeleton } from './features/notebooks/components/NotePageSkeleton';
import { ExtractNotePageSkeleton } from './features/notebooks/components/ExtractNotePageSkeleton';
import { TASKS_ROUTE } from './features/auth';
import { authApi } from './features/auth/store/api';
import { store } from './shared/store';

const TaskPage = React.lazy(() =>
  import('./features/tasks/components/TaskPage').then((m) => ({ default: m.TaskPage })),
);
const MemoPage = React.lazy(() =>
  import('./features/memos/components/MemoPage').then((m) => ({ default: m.MemoPage })),
);
const NotebookPage = React.lazy(() =>
  import('./features/notebooks/components/NotebookPage').then((m) => ({ default: m.NotebookPage })),
);
const NotebookEditPage = React.lazy(() =>
  import('./features/notebooks/components/NotebookEditPage').then((m) => ({
    default: m.NotebookEditPage,
  })),
);
const NotePage = React.lazy(() =>
  import('./features/notebooks/components/NotePage').then((m) => ({ default: m.NotePage })),
);
const ExtractNotePage = React.lazy(() =>
  import('./features/notebooks/components/ExtractNotePage').then((m) => ({
    default: m.ExtractNotePage,
  })),
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
      dispatch(setIsLoading(true));

      try {
        const user = await dispatch(authApi.endpoints.getCurrentUser.initiate()).unwrap();
        dispatch(setUser(user));

        try {
          const centrifugo = await dispatch(
            authApi.endpoints.getCentrifugoConnection.initiate(),
          ).unwrap();
          dispatch(setCentrifugoConfig(centrifugo));
        } catch (error) {
          console.error('Failed to fetch Centrifugo connection:', error);
        }
      } catch (error) {
        console.error('Failed to fetch user:', error);
        dispatch(setError('Failed to fetch user'));
      } finally {
        dispatch(setIsLoading(false));
        dispatch(setIsInitialized(true));
      }
    };

    if (!isInitialized) {
      void initAuth();
    }
  }, [dispatch, isInitialized]);

  return null;
}

const router = createBrowserRouter([
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
        path: 'memos',
        element: <MemosListShell />,
      },
      {
        path: 'memos/:id',
        element: (
          <Suspense fallback={<MemoPageSkeleton />}>
            <MemoPage />
          </Suspense>
        ),
      },
      {
        path: 'notebooks',
        element: <NotebooksListShell />,
      },
      {
        path: 'notebooks/new',
        element: (
          <Suspense fallback={<NotebookPageSkeleton />}>
            <NotebookEditPage />
          </Suspense>
        ),
      },
      {
        path: 'notebooks/:id',
        element: (
          <Suspense fallback={<NotebookPageSkeleton />}>
            <NotebookPage />
          </Suspense>
        ),
      },
      {
        path: 'notebooks/:id/edit',
        element: (
          <Suspense fallback={<NotebookPageSkeleton />}>
            <NotebookEditPage />
          </Suspense>
        ),
      },
      {
        path: 'notebooks/:notebookId/extract',
        element: (
          <Suspense fallback={<ExtractNotePageSkeleton />}>
            <ExtractNotePage />
          </Suspense>
        ),
      },
      {
        path: 'notebooks/:notebookId/notes/:id',
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
    <RouterProvider router={router} />
  </Provider>,
);
