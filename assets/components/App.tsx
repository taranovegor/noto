import React, { useState, useEffect } from 'react';
import { ProjectsList } from './ProjectsList';
import { TasksList } from './TasksList';
import { TaskPage } from './TaskPage';
import { NotesList } from './NotesList';
import { NotePage } from './NotePage';
import '../styles/app.css';

type View = 'projects' | 'tasks' | 'task' | 'notes' | 'note';

function parseRoute(): { view: View; entityId?: string } {
  const hash = window.location.hash.slice(1);

  if (hash.startsWith('task/')) {
    const taskId = hash.slice(5);
    return { view: 'task', entityId: taskId };
  }

  if (hash.startsWith('note/')) {
    const noteId = hash.slice(5);
    return { view: 'note', entityId: noteId };
  }

  if (hash === 'task') {
    return { view: 'task' };
  }

  if (hash === 'note') {
    return { view: 'note' };
  }

  if (hash === 'projects') {
    return { view: 'projects' };
  }

  if (hash === 'notes') {
    return { view: 'notes' };
  }

  return { view: 'tasks' };
}

function setRoute(view: View, entityId?: string) {
  if (view === 'task') {
    if (entityId) {
      window.location.hash = `task/${entityId}`;
    } else {
      window.location.hash = 'task';
    }
  } else if (view === 'note') {
    if (entityId) {
      window.location.hash = `note/${entityId}`;
    } else {
      window.location.hash = 'note';
    }
  } else if (view === 'projects') {
    window.location.hash = 'projects';
  } else if (view === 'notes') {
    window.location.hash = 'notes';
  } else {
    window.location.hash = '';
  }
}

export function App() {
  const { view: initialView, entityId: initialEntityId } = parseRoute();
  const [currentView, setCurrentView] = useState<View>(initialView);
  const [selectedEntityId, setSelectedEntityId] = useState<string | undefined>(initialEntityId);

  useEffect(() => {
    const handleHashChange = () => {
      const { view, entityId } = parseRoute();
      setCurrentView(view);
      setSelectedEntityId(entityId);
    };

    window.addEventListener('hashchange', handleHashChange);
    return () => window.removeEventListener('hashchange', handleHashChange);
  }, []);

  const goToBoard = () => {
    setRoute('tasks');
    setSelectedEntityId(undefined);
    setCurrentView('tasks');
  };

  const navigateToTask = (taskId: string) => {
    setRoute('task', taskId);
    setSelectedEntityId(taskId);
    setCurrentView('task');
  };

  const navigateToNewTask = () => {
    setRoute('task');
    setSelectedEntityId(undefined);
    setCurrentView('task');
  };

  const navigateToNote = (noteId: string) => {
    setRoute('note', noteId);
    setSelectedEntityId(noteId);
    setCurrentView('note');
  };

  const navigateToNewNote = () => {
    setRoute('note');
    setSelectedEntityId(undefined);
    setCurrentView('note');
  };

  const navigateToProjects = () => {
    setRoute('projects');
    setCurrentView('projects');
  };

  const navigateToTasks = () => {
    setRoute('tasks');
    setCurrentView('tasks');
  };

  const navigateToNotes = () => {
    setRoute('notes');
    setCurrentView('notes');
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
            className={`sidebar-nav-item ${currentView === 'notes' ? 'active' : ''}`}
            onClick={navigateToNotes}
          >
            Notes
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
            {currentView === 'notes' && (
              <NotesList
                onNoteClick={navigateToNote}
                onNewNote={navigateToNewNote}
              />
            )}
            {currentView === 'projects' && <ProjectsList />}
            {currentView === 'task' && (
              <TaskPage
                taskId={selectedEntityId}
                onBack={goToBoard}
                onCreated={navigateToTask}
              />
            )}
            {currentView === 'note' && (
              <NotePage
                noteId={selectedEntityId}
                onBack={navigateToNotes}
                onCreated={navigateToNote}
              />
            )}
          </div>
        </main>
      </div>
    </div>
  );
}
