import { useNavigate } from 'react-router-dom';
import { useAppSelector } from '../../../shared/store/hooks';
import { setTasksActiveSearch } from '../../../shared/store/uiSlice';
import { SearchBar } from '../../../shared/components';
import { useSearchState } from '../../../shared/hooks';
import { useLayoutMode } from '../../../layout/LayoutContext';
import { TasksList } from './TasksList';
import styles from './TasksList.module.css';

export function TasksListShell() {
  const navigate = useNavigate();
  const activeSearch = useAppSelector((state) => state.ui.tasksActiveSearch);
  const {
    input: searchInput,
    setInput: setSearchInput,
    handleSearch,
    handleClear: handleClearSearch,
  } = useSearchState(activeSearch, setTasksActiveSearch);

  const isSearchActive = activeSearch !== null;
  useLayoutMode(isSearchActive ? 'scroll' : 'fixed');

  return (
    <div className={isSearchActive ? styles.shellSearch : styles.shell}>
      <div className={styles.header}>
        <h2 className={styles.headerTitle}>Tasks</h2>
        <button className="btn btn-primary" onClick={() => navigate('/tasks/new')}>
          New task
        </button>
      </div>

      <SearchBar
        value={searchInput}
        onChange={setSearchInput}
        onSearch={handleSearch}
        onClear={handleClearSearch}
        placeholder="Search tasks..."
        hasActiveSearch={activeSearch !== null}
      />

      <div className={styles.content}>
        <TasksList />
      </div>
    </div>
  );
}
