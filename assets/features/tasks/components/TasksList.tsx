import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useAppSelector, useAppDispatch } from '../../../shared/store/hooks';
import { setTasksSelectedProjectId } from '../../../shared/store/uiSlice';
import { useTasks } from '../store/api';
import { useProjects } from '../../projects/hooks/useProjects';
import { useInfiniteScroll } from '../../../shared/hooks';
import { parseError, isInitialOrRefetch } from '../../../shared/utils';
import { TaskKanban } from './TaskKanban';
import { TaskSearchResults } from './TaskSearchResults';
import { TasksSearchSkeleton } from './TasksSearchSkeleton';
import { ProjectsFilterBar } from './ProjectsFilterBar';
import styles from './TasksList.module.css';

export function TasksList() {
  const navigate = useNavigate();
  const dispatch = useAppDispatch();
  const activeSearch = useAppSelector((state) => state.ui.tasksActiveSearch);
  const selectedProjectId = useAppSelector((state) => state.ui.tasksSelectedProjectId);

  const isSearching = activeSearch !== null;

  const {
    data,
    isLoading: tasksLoading,
    isFetching,
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage,
    error: tasksError,
  } = useTasks({ search: activeSearch, projectId: selectedProjectId }, { skip: !isSearching });

  const { data: projects = [], isLoading: projectsLoading } = useProjects();

  const tasks = React.useMemo(() => {
    const all = data?.pages.flat() ?? [];
    const seen = new Set<string>();
    return all.filter((t) => !seen.has(t.id) && seen.add(t.id));
  }, [data?.pages]);

  const { sentinelRef } = useInfiniteScroll(
    hasNextPage ?? false,
    isFetchingNextPage,
    fetchNextPage,
  );

  const error = tasksError ? parseError(tasksError).message : null;

  const handleTaskClick = (id: string) => navigate(`/tasks/${id}`);
  const handleProjectClick = (id: string) =>
    dispatch(setTasksSelectedProjectId(selectedProjectId === id ? null : id));

  return (
    <>
      {error && (
        <div className="error-message" role="alert">
          {error}
        </div>
      )}

      <ProjectsFilterBar
        projects={projects}
        loading={projectsLoading}
        selectedProjectId={selectedProjectId}
        onToggle={handleProjectClick}
      />

      {isSearching ? (
        <>
          {isInitialOrRefetch(tasksLoading, isFetching, isFetchingNextPage) ? (
            <TasksSearchSkeleton />
          ) : tasks.length === 0 ? (
            <div className="empty-state">
              <p>No tasks found.</p>
            </div>
          ) : (
            <TaskSearchResults tasks={tasks} onTaskClick={handleTaskClick} />
          )}
          {isFetchingNextPage && (
            <div style={{ marginTop: 'var(--space-md)' }}>
              <TasksSearchSkeleton count={2} />
            </div>
          )}
          <div ref={sentinelRef} className={styles.observerSentinel} />
        </>
      ) : (
        <TaskKanban onTaskClick={handleTaskClick} />
      )}
    </>
  );
}
