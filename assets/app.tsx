import '@vitejs/plugin-react/preamble';
import React, { Suspense } from 'react';
import { createRoot } from 'react-dom/client';
import { createBrowserRouter, Navigate, RouterProvider } from 'react-router-dom';
import { Provider } from 'react-redux';
import { App } from './layout/App';
import { RouteErrorBoundary } from './shared/components/RouteErrorBoundary';
import { TasksListShell } from './features/tasks/components/TasksListShell';
import { NotesListShell } from './features/notes/components/NotesListShell';
import { TaskPageSkeleton } from './features/tasks/components/TaskPageSkeleton';
import { NotePageSkeleton } from './features/notes/components/NotePageSkeleton';
import { store } from './shared/store';

const TaskPage = React.lazy(() =>
  import('./features/tasks/components/TaskPage').then((m) => ({ default: m.TaskPage })),
);
const NotePage = React.lazy(() =>
  import('./features/notes/components/NotePage').then((m) => ({ default: m.NotePage })),
);

const router = createBrowserRouter([
  {
    element: <App />,
    errorElement: <RouteErrorBoundary />,
    children: [
      { index: true, element: <Navigate to="/tasks" replace /> },
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
