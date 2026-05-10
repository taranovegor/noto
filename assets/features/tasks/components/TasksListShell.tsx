import { useNavigate } from 'react-router-dom';
import { SquarePen, Search } from 'lucide-react';
import { useAppSelector } from '../../../shared/store/hooks';
import { setTasksActiveSearch } from '../../../shared/store/uiSlice';
import { SearchBar } from '../../../shared/components';
import { useSearchState, useMobileSearch } from '../../../shared/hooks';
import { useLayoutMode } from '../../../layout/LayoutContext';
import { useActionBar } from '../../../layout/ActionBarContext';
import { TasksList } from './TasksList';
import styles from './TasksList.module.css';

export function TasksListShell() {
  const navigate = useNavigate();
  const activeSearch = useAppSelector((state) => state.ui.tasksActiveSearch);

  const {
    input: searchInput,
    setInput: setSearchInput,
    handleSearch,
    handleClear,
  } = useSearchState(activeSearch, setTasksActiveSearch);

  const {
    searchRef,
    searchOpen,
    searchValue,
    setSearchValue,
    openSearch,
    handleSubmit,
    handleClose,
  } = useMobileSearch(activeSearch, setTasksActiveSearch);

  const isSearchActive = activeSearch !== null;
  useLayoutMode(isSearchActive ? 'scroll' : 'fixed');

  useActionBar({
    buttons: [
      { icon: SquarePen, label: 'New task', primary: true, onPress: () => navigate('/tasks/new') },
      { icon: Search, label: 'Search', onPress: openSearch },
    ],
    input: searchOpen
      ? {
          ref: searchRef,
          value: searchValue,
          placeholder: 'Search tasks…',
          onChange: setSearchValue,
          onSubmit: handleSubmit,
          onClose: handleClose,
        }
      : null,
  });

  return (
    <div className={isSearchActive ? styles.shellSearch : styles.shell}>
      <div className={styles.header}>
        <h2 className={styles.headerTitle}>Tasks</h2>
        <button
          className={`btn btn-primary hide-on-mobile ${styles.headerBtn}`}
          onClick={() => navigate('/tasks/new')}
          aria-label="New task"
        >
          <SquarePen size={16} strokeWidth={1.75} />
        </button>
      </div>

      <SearchBar
        className="hide-on-mobile"
        value={searchInput}
        onChange={setSearchInput}
        onSearch={handleSearch}
        onClear={handleClear}
        placeholder="Search tasks..."
        hasActiveSearch={isSearchActive}
      />

      <div className={styles.content}>
        <TasksList />
      </div>
    </div>
  );
}
