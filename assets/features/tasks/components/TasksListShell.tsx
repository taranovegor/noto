import { useNavigate } from 'react-router-dom';
import { SquarePen, Search } from 'lucide-react';
import { useAppSelector } from '../../../shared/store/hooks';
import { setTasksActiveSearch } from '../../../shared/store/uiSlice';
import { Toolbar } from '../../../shared/components';
import { PageShell } from '../../../shared/components/PageShell';
import { useSearchState, useMobileSearch } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
import { TasksList } from './TasksList';

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
    <PageShell title="Tasks">
      <Toolbar
        className="hide-on-mobile"
        value={searchInput}
        onChange={setSearchInput}
        onSearch={handleSearch}
        onClear={handleClear}
        placeholder="Search tasks..."
        hasActiveSearch={isSearchActive}
        actions={[{ icon: SquarePen, label: 'New task', onClick: () => navigate('/tasks/new') }]}
      />

      <TasksList />
    </PageShell>
  );
}
