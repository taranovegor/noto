import React, { useState, useEffect } from 'react';
import { TaskResponseDto, ProjectResponseDto, TaskStatus } from '../types/api';
import { api } from '../api';
import { PRIORITY_OPTIONS } from '../constants';
import { formatDateShort } from '../utils/date';

interface TasksListProps {
  onTaskClick: (taskId: string) => void;
  onNewTask: () => void;
}

const COLUMNS: { status: TaskStatus; label: string }[] = [
  { status: 'backlog',     label: 'Backlog' },
  { status: 'in_progress', label: 'In Progress' },
  { status: 'done',        label: 'Done' },
];

const priorityColor = (priority: string) =>
  PRIORITY_OPTIONS.find((o) => o.value === priority) ?? { bg: '#f9f9f8', text: '#787774' };

export function TasksList({ onTaskClick, onNewTask }: TasksListProps) {
  const [tasks, setTasks] = useState<TaskResponseDto[]>([]);
  const [projects, setProjects] = useState<ProjectResponseDto[]>([]);
  const [selectedProjectId, setSelectedProjectId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchInput, setSearchInput] = useState('');
  const [activeSearch, setActiveSearch] = useState<string | null>(null);

  useEffect(() => {
    const loadTasks = async () => {
      try {
        const tasksData = activeSearch
          ? await api.tasks.search(activeSearch)
          : await api.tasks.list();
        const projectsData = await api.projects.list();
        setTasks(tasksData.data);
        setProjects(projectsData.data);
      } catch (err: unknown) {
        setError(err instanceof Error ? err.message : 'Failed to load data');
      } finally {
        setLoading(false);
      }
    };

    loadTasks();
  }, [activeSearch]);

  const handleProjectClick = (id: string) =>
    setSelectedProjectId((prev) => (prev === id ? null : id));

  const handleSearch = (query: string) => {
    if (query.trim()) {
      setActiveSearch(query);
    } else {
      setActiveSearch(null);
    }
  };

  const handleSearchKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      handleSearch((e.target as HTMLInputElement).value);
    }
  };

  const handleClearSearch = () => {
    setSearchInput('');
    setActiveSearch(null);
  };

  const visibleTasks = selectedProjectId
    ? tasks.filter((t) => t.projectId === selectedProjectId)
    : tasks;

  const tasksByStatus = (status: TaskStatus) =>
    visibleTasks.filter((t) => t.status === status);

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2 style={{ marginBottom: 0 }}>Tasks</h2>
        <button className="btn btn-primary" onClick={onNewTask}>New task</button>
      </div>

      <div style={{ display: 'flex', gap: '8px', marginBottom: '20px' }}>
        <input
          type="text"
          placeholder="Search tasks..."
          value={searchInput}
          onChange={(e) => setSearchInput(e.target.value)}
          onKeyDown={handleSearchKeyDown}
          style={{
            flex: 1,
            padding: '8px 12px',
            borderRadius: '4px',
            border: '1px solid var(--color-border)',
            backgroundColor: 'var(--color-bg)',
            color: 'var(--color-text)',
            fontSize: '0.9rem',
            fontFamily: 'inherit',
          }}
        />
        <button
          className="btn btn-primary"
          onClick={() => handleSearch(searchInput)}
          style={{ whiteSpace: 'nowrap' }}
        >
          Search
        </button>
        {activeSearch && (
          <button
            className="btn"
            onClick={handleClearSearch}
            style={{ whiteSpace: 'nowrap' }}
          >
            Clear
          </button>
        )}
      </div>

      {projects.length > 0 && !activeSearch && (
        <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', marginBottom: '32px' }}>
          {projects.map((project) => {
            const active = selectedProjectId === project.id;
            return (
              <button
                key={project.id}
                onClick={() => handleProjectClick(project.id)}
                className="project-badge"
                data-active={active}
                style={{
                  padding: '4px 12px',
                  borderRadius: '9999px',
                  fontSize: '0.8rem',
                  fontWeight: 500,
                  cursor: 'pointer',
                  border: '1px solid',
                  borderColor: active ? 'var(--color-text)' : 'var(--color-border)',
                  backgroundColor: active ? 'var(--color-text)' : 'var(--color-bg)',
                  color: active ? '#ffffff' : 'var(--color-text-secondary)',
                  transition: 'all 150ms ease-out',
                  fontFamily: 'var(--font-mono)',
                }}
              >
                {project.prefix}
                <span style={{ marginLeft: '6px', opacity: 0.7, fontFamily: 'var(--font-sans)', fontWeight: 400 }}>
                  {project.name}
                </span>
              </button>
            );
          })}
        </div>
      )}

      {error && (
        <div className="error-message">
          {error}
        </div>
      )}

      {loading ? (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '24px', alignItems: 'start' }}>
          {COLUMNS.map((col) => (
            <div key={col.status}>
              <div style={{
                marginBottom: '16px', paddingBottom: '8px',
              }}>
                <div className="skeleton skeleton-text" style={{ width: '80px', height: '0.9rem' }} />
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', maxHeight: 'calc(100vh - 320px)', overflowY: 'auto' }}>
                {Array.from({ length: 4 }).map((_, i) => (
                  <div key={i} className="skeleton-card">
                    <div className="skeleton skeleton-text" style={{ height: '1rem', marginBottom: '8px' }} />
                    <div className="skeleton skeleton-text tiny" />
                    <div className="skeleton skeleton-text tiny" style={{ marginBottom: 0 }} />
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '24px', alignItems: 'start' }}>
          {COLUMNS.map((col) => {
            const colTasks = tasksByStatus(col.status);
            return (
              <div key={col.status}>
                <div style={{
                  marginBottom: '16px', paddingBottom: '8px',
                }}>
                  <span style={{ fontSize: '0.9rem', fontWeight: 600 }}>{col.label}</span>
                </div>

                <div className="hide-scrollbar scrollable-column" style={{ display: 'flex', flexDirection: 'column', gap: '10px', maxHeight: 'calc(100vh - 320px)', overflowY: 'auto' }}>
                  {colTasks.map((task, index) => (
                    <div
                      key={task.id}
                      className="card"
                      onClick={() => onTaskClick(task.id)}
                      data-stagger-index={index}
                      style={{
                        padding: '16px',
                        cursor: 'pointer',
                        animationDelay: `${index * 30}ms`
                      }}
                    >
                      <div style={{ fontSize: '0.9rem', fontWeight: 500, color: 'var(--color-text)', lineHeight: 1.4 }}>
                        {task.priority && (
                          <div style={{
                            width: '12px', height: '12px', borderRadius: '9999px',
                            backgroundColor: priorityColor(task.priority).bg,
                            border: `1px solid ${priorityColor(task.priority).text}`,
                            display: 'inline-block',
                            marginRight: '8px',
                            verticalAlign: 'middle',
                            marginTop: '-2px',
                          }} title={task.priority} />
                        )}
                        {task.name}
                      </div>
                      {(task.code || task.deadline) && (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '4px', fontSize: '0.75rem', color: 'var(--color-text-secondary)', marginTop: '8px' }}>
                          {task.code && (
                            <p style={{ fontFamily: 'var(--font-mono)', margin: 0, fontWeight: 500 }}>
                              {task.code}
                            </p>
                          )}
                          {task.deadline && (
                            <span>{formatDateShort(task.deadline)}</span>
                          )}
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
