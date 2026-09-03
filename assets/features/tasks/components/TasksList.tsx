import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useAppSelector, useAppDispatch } from '../../../shared/store/hooks';
import { setTasksSelectedProjectId } from '../../../shared/store/uiSlice';
import { useTasks } from '../store/api';
import { useProjects } from '../../projects/hooks/useProjects';
import { useInfiniteScroll, useIsDataStale } from '../../../shared/hooks';
import { parseError, shouldShowSkeleton } from '../../../shared/utils';
import { TaskRow } from './TaskRow';
import { TaskRowSkeleton } from './TaskRowSkeleton';
import { ProjectsFilterBar } from './ProjectsFilterBar';
import styles from './TasksList.module.css';

export function TasksList() {
  const navigate = useNavigate();
  const dispatch = useAppDispatch();
  const activeSearch = useAppSelector((state) => state.ui.tasksActiveSearch);
  const selectedProjectId = useAppSelector((state) => state.ui.tasksSelectedProjectId);

  const {
    data,
    isLoading,
    isFetching,
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage,
    error: tasksError,
  } = useTasks({ search: activeSearch, projectId: selectedProjectId });

  const isDataStale = useIsDataStale(
    `${activeSearch ?? ''}:${selectedProjectId ?? 'all'}`,
    isFetching,
  );

  const { data: projects = [], isLoading: projectsLoading } = useProjects();

  const tasks = React.useMemo(() => {
    const all = data?.pages.flat() ?? [];
    const seen = new Set<string>();
    const deduped = all.filter((t) => !seen.has(t.id) && seen.add(t.id));
    return deduped.sort((a, b) => {
      const aDone = a.status === 'done';
      const bDone = b.status === 'done';
      return aDone === bDone ? 0 : aDone ? 1 : -1;
    });
  }, [data?.pages]);

  const { sentinelRef } = useInfiniteScroll(
    hasNextPage ?? false,
    isFetchingNextPage,
    fetchNextPage,
  );

  const errorMessage = tasksError ? parseError(tasksError).message : null;

  const handleTaskClick = (id: string) => navigate(`/tasks/${id}`);
  const handleProjectClick = (id: string) =>
    dispatch(setTasksSelectedProjectId(selectedProjectId === id ? null : id));

  return (
    <>
      {errorMessage && (
        <div className="error-message" role="alert">
          {errorMessage}
        </div>
      )}

      <ProjectsFilterBar
        projects={projects}
        loading={projectsLoading}
        selectedProjectId={selectedProjectId}
        onToggle={handleProjectClick}
      />

      {shouldShowSkeleton(isLoading, isFetching, isFetchingNextPage, !!data && !isDataStale) ? (
        <div className={styles.list}>
          <TaskRowSkeleton />
        </div>
      ) : tasks.length === 0 ? (
        <div className="empty-state">
          <p>
            {activeSearch
              ? 'No tasks found.'
              : "No tasks in any project yet. Add the first one, and it'll show up here."}
          </p>
          {!activeSearch && (
            <button
              className={`btn btn-primary ${styles.emptyButton}`}
              onClick={() => navigate('/tasks/new')}
            >
              New task
            </button>
          )}
        </div>
      ) : (
        <div className={styles.list} role="list">
          {tasks.map((task, i) => (
            <TaskRow
              key={task.id}
              task={task}
              last={i === tasks.length - 1 && !isFetchingNextPage}
              onClick={handleTaskClick}
            />
          ))}
          {isFetchingNextPage && <TaskRowSkeleton count={3} />}
        </div>
      )}

      <div ref={sentinelRef} className={styles.observerSentinel} />
    </>
  );
}
