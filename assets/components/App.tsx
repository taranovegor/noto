import React, { useState, useEffect } from 'react';
import { ProjectsList } from './ProjectsList';
import { TasksList } from './TasksList';
import { TaskPage } from './TaskPage';
import '../styles/app.css';

type View = 'projects' | 'tasks' | 'task';

function parseRoute(): { view: View; taskId?: string } {
  const hash = window.location.hash.slice(1);

  if (hash.startsWith('task/')) {
    const taskId = hash.slice(5);
    return { view: 'task', taskId };
  }

  if (hash === 'projects') {
    return { view: 'projects' };
  }

  return { view: 'tasks' };
}

function setRoute(view: View, taskId?: string) {
  if (view === 'task' && taskId) {
    window.location.hash = `task/${taskId}`;
  } else if (view === 'projects') {
    window.location.hash = 'projects';
  } else {
    window.location.hash = '';
  }
}

export function App() {
  const { view: initialView, taskId: initialTaskId } = parseRoute();
  const [currentView, setCurrentView] = useState<View>(initialView);
  const [selectedTaskId, setSelectedTaskId] = useState<string | undefined>(initialTaskId);

  useEffect(() => {
    const handleHashChange = () => {
      const { view, taskId } = parseRoute();
      setCurrentView(view);
      setSelectedTaskId(taskId);
    };

    window.addEventListener('hashchange', handleHashChange);
    return () => window.removeEventListener('hashchange', handleHashChange);
  }, []);

  const goToBoard = () => {
    setRoute('tasks');
    setSelectedTaskId(undefined);
    setCurrentView('tasks');
  };

  const navigateToTask = (taskId: string) => {
    setRoute('task', taskId);
    setSelectedTaskId(taskId);
    setCurrentView('task');
  };

  const navigateToNewTask = () => {
    setRoute('task');
    setSelectedTaskId(undefined);
    setCurrentView('task');
  };

  const navigateToProjects = () => {
    setRoute('projects');
    setCurrentView('projects');
  };

  const navigateToTasks = () => {
    setRoute('tasks');
    setCurrentView('tasks');
  };

  return (
    <div style={{ display: 'flex', minHeight: '100vh', backgroundColor: 'var(--color-bg)' }}>
      {/* Sidebar */}
      <aside className="sidebar">
        <div className="sidebar-header">
          <button onClick={goToBoard} className="sidebar-title">
            noto
          </button>
        </div>
        <nav className="sidebar-nav">
          <button
            className={`sidebar-nav-item ${currentView === 'tasks' ? 'active' : ''}`}
            onClick={navigateToTasks}
          >
            Tasks
          </button>
          <button
            className={`sidebar-nav-item ${currentView === 'projects' ? 'active' : ''}`}
            onClick={navigateToProjects}
          >
            Projects
          </button>
        </nav>
      </aside>

      {/* Main content */}
      <div style={{ flex: 1, display: 'flex', flexDirection: 'column' }}>
        <main className="main-content">
          <div className="container">
            {currentView === 'tasks' && (
              <TasksList
                onTaskClick={navigateToTask}
                onNewTask={navigateToNewTask}
              />
            )}
            {currentView === 'projects' && <ProjectsList />}
            {currentView === 'task' && (
              <TaskPage
                taskId={selectedTaskId}
                onBack={goToBoard}
                onCreated={navigateToTask}
              />
            )}
          </div>
        </main>
      </div>
    </div>
  );
}
