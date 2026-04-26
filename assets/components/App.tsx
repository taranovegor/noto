import React, { useEffect } from 'react';
import { Routes, Route, Navigate, useNavigate, useLocation } from 'react-router-dom';
import { ProjectsList } from './ProjectsList';
import { TasksList } from './TasksList';
import { TaskPage } from './TaskPage';
import { NotesList } from './NotesList';
import { NotePage } from './NotePage';
import '../styles/app.css';
import { useAppDispatch } from '../store/hooks';
import { setActiveSearch as setTasksSearch } from '../store/slices/tasks';
import { setActiveSearch as setNotesSearch } from '../store/slices/notes';

export function App() {
  const navigate = useNavigate();
  const location = useLocation();
  const dispatch = useAppDispatch();
  const currentPath = location.pathname;

  useEffect(() => {
    const isTasksTab = currentPath.startsWith('/tasks');
    const isNotesTab = currentPath.startsWith('/notes');
    const isProjectsTab = currentPath.startsWith('/projects');

    if (!isTasksTab) dispatch(setTasksSearch(null));
    if (!isNotesTab) dispatch(setNotesSearch(null));
  }, [location.pathname, dispatch]);

  const isActive = (path: string) => currentPath === path;

  return (
    <div style={{ display: 'flex', minHeight: '100vh', backgroundColor: 'var(--color-bg)' }}>
      {/* Sidebar */}
      <aside className="sidebar">
        <div className="sidebar-header">
          <button onClick={() => navigate('/tasks')} className="sidebar-title">
            noto
          </button>
        </div>
        <nav className="sidebar-nav">
          <button
            className={`sidebar-nav-item ${isActive('/tasks') ? 'active' : ''}`}
            onClick={() => navigate('/tasks')}
          >
            Tasks
          </button>
          <button
            className={`sidebar-nav-item ${isActive('/notes') ? 'active' : ''}`}
            onClick={() => navigate('/notes')}
          >
            Notes
          </button>
          <button
            className={`sidebar-nav-item ${isActive('/projects') ? 'active' : ''}`}
            onClick={() => navigate('/projects')}
          >
            Projects
          </button>
        </nav>
      </aside>

      {/* Main content */}
      <div style={{ flex: 1, display: 'flex', flexDirection: 'column' }}>
        <main className="main-content">
          <div className="container">
            <Routes>
              <Route path="/tasks" element={<TasksList />} />
              <Route path="/tasks/:id" element={<TaskPage />} />
              <Route path="/notes" element={<NotesList />} />
              <Route path="/notes/:id" element={<NotePage />} />
              <Route path="/projects" element={<ProjectsList />} />
              <Route path="/" element={<Navigate to="/tasks" replace />} />
            </Routes>
          </div>
        </main>
      </div>
    </div>
  );
}
